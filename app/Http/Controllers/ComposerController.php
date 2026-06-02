<?php

namespace App\Http\Controllers;

use App\Enums\PackageType;
use App\Models\DownloadStatistic;
use App\Models\Repository;
use App\Services\GitHubPackageFetcher;
use App\Services\GitLabPackageFetcher;
use App\Services\GitPackageFetcher;
use App\Services\MetadataBuilder;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ComposerController extends Controller
{
    public function __construct(
        private MetadataBuilder $metadataBuilder,
    ) {}

    public function packagesJson(Repository $repository): JsonResponse
    {
        return response()->json($this->metadataBuilder->buildPackagesJson($repository));
    }

    public function packageMetadata(Repository $repository, string $vendor, string $packageName): JsonResponse
    {
        $devOnly = false;
        if (str_ends_with($packageName, '~dev')) {
            $packageName = substr($packageName, 0, -4);
            $devOnly = true;
        }

        $fullName = "{$vendor}/{$packageName}";
        $package = $repository->packages()->where('name', $fullName)->first();

        if (! $package) {
            return response()->json(['error' => 'Package not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(
            $this->metadataBuilder->buildPackageMetadata($repository, $package, $devOnly)
        );
    }

    public function dist(Repository $repository, string $vendor, string $packageName, string $version, string $ref): BinaryFileResponse|Response
    {
        $fullName = "{$vendor}/{$packageName}";
        $package = $repository->packages()->where('name', $fullName)->first();

        if (! $package) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $versionId = $package->versions()->where('reference', $ref)->value('id');
        DownloadStatistic::recordDownload($package->id, $versionId);

        $cachePath = config('phacman.dist_cache_path')."/{$vendor}/{$packageName}/{$ref}.zip";

        if (file_exists($cachePath)) {
            return response()->download($cachePath, "{$packageName}-{$version}.zip", [
                'Content-Type' => 'application/zip',
            ]);
        }

        $fetcher = match ($package->type) {
            PackageType::GitHub => app(GitHubPackageFetcher::class),
            PackageType::GitLab => app(GitLabPackageFetcher::class),
            PackageType::Git => app(GitPackageFetcher::class),
        };

        $archive = $fetcher->getDistArchive($package, $ref);

        if ($archive === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $cacheDir = dirname($cachePath);
        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        file_put_contents($cachePath, $archive);

        return response()->download($cachePath, "{$packageName}-{$version}.zip", [
            'Content-Type' => 'application/zip',
        ]);
    }
}
