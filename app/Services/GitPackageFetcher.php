<?php

namespace App\Services;

use App\Models\Package;
use Composer\Semver\VersionParser;
use Illuminate\Support\Facades\Process;

class GitPackageFetcher implements PackageFetcher
{
    private VersionParser $versionParser;

    public function __construct()
    {
        $this->versionParser = new VersionParser;
    }

    public function fetchVersions(Package $package): array
    {
        $repoPath = $this->ensureCloned($package);
        $versions = [];

        $tagsResult = Process::path($repoPath)->run('git tag');

        if (! $tagsResult->successful()) {
            throw new \RuntimeException('Failed to list tags: '.$tagsResult->errorOutput());
        }

        $tags = array_filter(explode("\n", trim($tagsResult->output())));

        foreach ($tags as $tagName) {
            $composerJson = $this->readComposerJson($repoPath, $tagName);

            if ($composerJson === null) {
                continue;
            }

            try {
                $normalized = $this->versionParser->normalize($tagName);
            } catch (\UnexpectedValueException) {
                continue;
            }

            $sha = $this->getReference($repoPath, $tagName);
            if ($sha === null) {
                continue;
            }

            $versions[] = [
                'version' => $tagName,
                'version_normalized' => $normalized,
                'reference' => $sha,
                'composer_json' => $composerJson,
                'released_at' => $this->getTagDate($repoPath, $tagName),
            ];
        }

        $devVersion = $this->fetchDevVersion($repoPath);
        if ($devVersion !== null) {
            $versions[] = $devVersion;
        }

        return $versions;
    }

    public function getDistArchive(Package $package, string $reference): mixed
    {
        $repoPath = $this->getRepoPath($package);

        if (! is_dir($repoPath)) {
            return null;
        }

        $result = Process::path($repoPath)
            ->run(['git', 'archive', '--format=zip', '--', $reference]);

        if (! $result->successful()) {
            return null;
        }

        return $result->output();
    }

    private function getRepoPath(Package $package): string
    {
        return config('phacman.git_clone_path').'/'.$package->id;
    }

    private function ensureCloned(Package $package): string
    {
        $repoPath = $this->getRepoPath($package);
        $repoUrl = $this->buildAuthenticatedUrl($package);

        if (is_dir($repoPath)) {
            $result = Process::path($repoPath)->run('git fetch --tags --prune');

            if (! $result->successful()) {
                throw new \RuntimeException('Failed to fetch: '.$result->errorOutput());
            }

            return $repoPath;
        }

        $parentDir = dirname($repoPath);
        if (! is_dir($parentDir)) {
            mkdir($parentDir, 0755, true);
        }

        $result = Process::run(['git', 'clone', '--bare', '--', $repoUrl, $repoPath]);

        if (! $result->successful()) {
            throw new \RuntimeException('Failed to clone: '.$result->errorOutput());
        }

        return $repoPath;
    }

    private function buildAuthenticatedUrl(Package $package): string
    {
        $url = $package->repository_url;

        if ($package->credential?->token) {
            $parsed = parse_url($url);
            $scheme = $parsed['scheme'] ?? 'https';
            $host = $parsed['host'] ?? '';
            $path = $parsed['path'] ?? '';
            $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';

            return "{$scheme}://oauth2:{$package->credential->token}@{$host}{$port}{$path}";
        }

        return $url;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readComposerJson(string $repoPath, string $ref): ?array
    {
        $result = Process::path($repoPath)->run(['git', 'show', $ref.':composer.json']);

        if (! $result->successful()) {
            return null;
        }

        $json = json_decode($result->output(), true);

        return is_array($json) ? $json : null;
    }

    private function getReference(string $repoPath, string $ref): ?string
    {
        $result = Process::path($repoPath)->run(['git', 'rev-parse', $ref.'^{}']);

        if (! $result->successful()) {
            return null;
        }

        return trim($result->output());
    }

    private function getTagDate(string $repoPath, string $tag): ?string
    {
        $result = Process::path($repoPath)->run(['git', 'log', '-1', '--format=%aI', '--', $tag]);

        if (! $result->successful()) {
            return null;
        }

        return trim($result->output()) ?: null;
    }

    /**
     * @return array{version: string, version_normalized: string, reference: string, composer_json: array<string, mixed>, released_at: string|null}|null
     */
    private function fetchDevVersion(string $repoPath): ?array
    {
        $result = Process::path($repoPath)->run('git symbolic-ref HEAD');

        if (! $result->successful()) {
            return null;
        }

        $headRef = trim($result->output());
        $branch = str_replace('refs/heads/', '', $headRef);

        $sha = $this->getReference($repoPath, 'HEAD');
        if ($sha === null) {
            return null;
        }

        $composerJson = $this->readComposerJson($repoPath, 'HEAD');
        if ($composerJson === null) {
            return null;
        }

        return [
            'version' => "dev-{$branch}",
            'version_normalized' => "dev-{$branch}",
            'reference' => $sha,
            'composer_json' => $composerJson,
            'released_at' => null,
        ];
    }
}
