<?php

namespace App\Services;

use App\Models\Package;
use Composer\Semver\VersionParser;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class GitLabPackageFetcher implements PackageFetcher
{
    private VersionParser $versionParser;

    public function __construct()
    {
        $this->versionParser = new VersionParser;
    }

    public function fetchVersions(Package $package): array
    {
        $projectPath = $this->parseProjectPath($package->repository_url);
        $client = $this->buildClient($package);
        $versions = [];

        $tags = $this->fetchAllTags($client, $projectPath);

        foreach ($tags as $tag) {
            $tagName = $tag['name'];
            $composerJson = $this->fetchComposerJson($client, $projectPath, $tagName);

            if ($composerJson === null) {
                continue;
            }

            try {
                $normalized = $this->versionParser->normalize($tagName);
            } catch (\UnexpectedValueException) {
                continue;
            }

            $versions[] = [
                'version' => $tagName,
                'version_normalized' => $normalized,
                'reference' => $tag['commit']['id'],
                'composer_json' => $composerJson,
                'released_at' => $tag['commit']['committed_date'] ?? null,
            ];
        }

        $devVersion = $this->fetchDevVersion($client, $projectPath);
        if ($devVersion !== null) {
            $versions[] = $devVersion;
        }

        return $versions;
    }

    public function getDistArchive(Package $package, string $reference): mixed
    {
        $projectPath = $this->parseProjectPath($package->repository_url);
        $client = $this->buildClient($package);
        $encodedPath = urlencode($projectPath);

        $response = $client->get("/api/v4/projects/{$encodedPath}/repository/archive.zip", [
            'sha' => $reference,
        ]);

        if ($response->successful()) {
            return $response->body();
        }

        return null;
    }

    private function parseProjectPath(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $path = trim($path, '/');

        return preg_replace('/\.git$/', '', $path);
    }

    private function getBaseUrl(Package $package): string
    {
        if ($package->credential?->base_url) {
            return rtrim($package->credential->base_url, '/');
        }

        $scheme = parse_url($package->repository_url, PHP_URL_SCHEME) ?? 'https';
        $host = parse_url($package->repository_url, PHP_URL_HOST) ?? 'gitlab.com';

        return "{$scheme}://{$host}";
    }

    private function buildClient(Package $package): PendingRequest
    {
        $baseUrl = $this->getBaseUrl($package);
        $client = Http::baseUrl($baseUrl);

        if ($package->credential?->token) {
            $client = $client->withHeader('PRIVATE-TOKEN', $package->credential->token);
        }

        return $client;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchAllTags(PendingRequest $client, string $projectPath): array
    {
        $encodedPath = urlencode($projectPath);
        $tags = [];
        $page = 1;

        do {
            $response = $client->get("/api/v4/projects/{$encodedPath}/repository/tags", [
                'per_page' => 100,
                'page' => $page,
            ]);

            if (! $response->successful()) {
                if ($page === 1) {
                    throw new \RuntimeException("Failed to fetch tags from GitLab: HTTP {$response->status()} — {$response->body()}");
                }

                break;
            }

            $batch = $response->json();
            if (empty($batch)) {
                break;
            }

            $tags = array_merge($tags, $batch);
            $page++;
        } while (count($batch) === 100);

        return $tags;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchComposerJson(PendingRequest $client, string $projectPath, string $ref): ?array
    {
        $encodedPath = urlencode($projectPath);

        $response = $client->get("/api/v4/projects/{$encodedPath}/repository/files/composer.json/raw", [
            'ref' => $ref,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $json = json_decode($response->body(), true);

        return is_array($json) ? $json : null;
    }

    /**
     * @return array{version: string, version_normalized: string, reference: string, composer_json: array<string, mixed>, released_at: string|null}|null
     */
    private function fetchDevVersion(PendingRequest $client, string $projectPath): ?array
    {
        $encodedPath = urlencode($projectPath);

        $response = $client->get("/api/v4/projects/{$encodedPath}");

        if (! $response->successful()) {
            return null;
        }

        $defaultBranch = $response->json('default_branch', 'main');

        $branchResponse = $client->get("/api/v4/projects/{$encodedPath}/repository/branches/{$defaultBranch}");

        if (! $branchResponse->successful()) {
            return null;
        }

        $sha = $branchResponse->json('commit.id');
        $composerJson = $this->fetchComposerJson($client, $projectPath, $defaultBranch);

        if ($composerJson === null) {
            return null;
        }

        return [
            'version' => "dev-{$defaultBranch}",
            'version_normalized' => "dev-{$defaultBranch}",
            'reference' => $sha,
            'composer_json' => $composerJson,
            'released_at' => null,
        ];
    }
}
