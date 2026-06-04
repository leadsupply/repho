<?php

namespace App\Services;

use App\Enums\PackageType;
use App\Models\Package;
use App\Models\SecurityAdvisory;
use App\Models\Version;
use Illuminate\Support\Facades\File;

class PackageSynchronizer
{
    public function __construct(
        private GitHubPackageFetcher $gitHub,
        private GitLabPackageFetcher $gitLab,
        private GitPackageFetcher $git,
        private SecurityAdvisoryChecker $advisoryChecker,
    ) {}

    public function sync(Package $package): void
    {
        $fetcher = match ($package->type) {
            PackageType::GitHub => $this->gitHub,
            PackageType::GitLab => $this->gitLab,
            PackageType::Git => $this->git,
        };

        try {
            $package->update(['sync_progress' => 10]);

            $versions = $fetcher->fetchVersions($package);

            $previousReferences = $package->versions()->pluck('reference', 'id')->all();

            $package->update(['sync_progress' => 25]);

            $this->storeVersions($package, $versions);

            $package->update(['sync_progress' => 35]);

            if ($package->download_dists) {
                $this->downloadDists($package, $previousReferences, $fetcher);
            }

            $this->syncAdvisories($package);

            $description = $this->extractDescription($versions);

            $package->update([
                'last_synced_at' => now(),
                'sync_error' => null,
                'sync_progress' => 100,
                'description' => $description ?? $package->description,
            ]);
        } catch (\Throwable $e) {
            $package->update(['sync_error' => $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * @param  array<int, array{version: string, version_normalized: string, reference: string, composer_json: array<string, mixed>, released_at: string|null}>  $versions
     */
    private function storeVersions(Package $package, array $versions): void
    {
        if (empty($versions)) {
            return;
        }

        $rows = array_map(fn (array $v) => [
            'package_id' => $package->id,
            'version' => $v['version'],
            'version_normalized' => $v['version_normalized'],
            'reference' => $v['reference'],
            'composer_json' => json_encode($v['composer_json']),
            'released_at' => $v['released_at'],
        ], $versions);

        Version::upsert(
            $rows,
            ['package_id', 'version_normalized'],
            ['version', 'reference', 'composer_json', 'released_at'],
        );

        $normalizedVersions = array_column($versions, 'version_normalized');
        $package->versions()
            ->whereNotIn('version_normalized', $normalizedVersions)
            ->delete();
    }

    /**
     * @param  array<int, string>  $previousReferences
     */
    private function downloadDists(Package $package, array $previousReferences, PackageFetcher $fetcher): void
    {
        $currentVersions = $package->versions()->get();
        $previousReferenceValues = array_values($previousReferences);

        $newVersions = $currentVersions->filter(
            fn (Version $version) => ! in_array($version->reference, $previousReferenceValues),
        );

        $currentReferences = $currentVersions->pluck('reference')->all();
        $removedReferences = array_diff($previousReferenceValues, $currentReferences);

        $this->cleanUpRemovedDists($package, $removedReferences);

        if ($newVersions->isEmpty()) {
            return;
        }

        $completed = 0;
        $total = $newVersions->count();

        foreach ($newVersions as $version) {
            $this->downloadVersionDist($package, $version, $fetcher);
            $completed++;
            $progress = 35 + (int) round(($completed / $total) * 60);
            $package->update(['sync_progress' => $progress]);
        }
    }

    private function downloadVersionDist(Package $package, Version $version, PackageFetcher $fetcher): void
    {
        $cachePath = config('repho.dist_cache_path')."/{$package->vendor()}/{$package->shortName()}/{$version->reference}.zip";

        if (file_exists($cachePath)) {
            return;
        }

        $archive = $fetcher->getDistArchive($package, $version->reference);

        if ($archive === null) {
            return;
        }

        $cacheDir = dirname($cachePath);
        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        file_put_contents($cachePath, $archive);
    }

    /**
     * @param  array<int, string>  $removedReferences
     */
    private function cleanUpRemovedDists(Package $package, array $removedReferences): void
    {
        $basePath = config('repho.dist_cache_path')."/{$package->vendor()}/{$package->shortName()}";

        foreach ($removedReferences as $ref) {
            $path = "{$basePath}/{$ref}.zip";

            if (file_exists($path)) {
                File::delete($path);
            }
        }
    }

    private function syncAdvisories(Package $package): void
    {
        try {
            $advisories = $this->advisoryChecker->check($package->name);
        } catch (\Throwable) {
            return;
        }

        $existingIds = $package->securityAdvisories()->pluck('advisory_id')->all();

        foreach ($advisories as $advisory) {
            if (in_array($advisory['advisoryId'], $existingIds)) {
                continue;
            }

            SecurityAdvisory::create([
                'package_id' => $package->id,
                'advisory_id' => $advisory['advisoryId'],
                'title' => $advisory['title'],
                'link' => $advisory['link'],
                'cve' => $advisory['cve'],
                'affected_versions' => $advisory['affectedVersions'],
                'severity' => $advisory['severity'],
                'reported_at' => $advisory['reportedAt'],
            ]);
        }

        $activeIds = array_column($advisories, 'advisoryId');
        $package->securityAdvisories()
            ->whereNotIn('advisory_id', $activeIds)
            ->delete();
    }

    /**
     * @param  array<int, array{version: string, version_normalized: string, reference: string, composer_json: array<string, mixed>, released_at: string|null}>  $versions
     */
    private function extractDescription(array $versions): ?string
    {
        foreach ($versions as $version) {
            if (isset($version['composer_json']['description'])) {
                return $version['composer_json']['description'];
            }
        }

        return null;
    }
}
