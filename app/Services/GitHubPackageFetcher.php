<?php

namespace App\Services;

use App\Models\Package;
use Composer\Semver\VersionParser;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class GitHubPackageFetcher implements PackageFetcher
{
    private VersionParser $versionParser;

    public function __construct()
    {
        $this->versionParser = new VersionParser;
    }

    public function fetchVersions(Package $package): array
    {
        [$owner, $repo] = $this->parseOwnerRepo($package->repository_url);
        $client = $this->buildClient($package);
        $versions = [];

        $tags = $this->fetchAllTags($client, $owner, $repo);

        foreach ($tags as $tag) {
            $tagName = $tag['name'];
            $composerJson = $this->fetchComposerJson($client, $owner, $repo, $tagName);

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
                'reference' => $tag['commit']['sha'],
                'composer_json' => $composerJson,
                'released_at' => null,
            ];
        }

        $devVersion = $this->fetchDevVersion($client, $owner, $repo);
        if ($devVersion !== null) {
            $versions[] = $devVersion;
        }

        return $versions;
    }

    public function getDistArchive(Package $package, string $reference): mixed
    {
        [$owner, $repo] = $this->parseOwnerRepo($package->repository_url);
        $client = $this->buildClient($package);

        $response = $client->withOptions(['allow_redirects' => false])
            ->get("https://api.github.com/repos/{$owner}/{$repo}/zipball/{$reference}");

        if ($response->redirect()) {
            $redirectUrl = $response->header('Location');

            return Http::withOptions(['sink' => null])->get($redirectUrl)->body();
        }

        if ($response->successful()) {
            return $response->body();
        }

        return null;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseOwnerRepo(string $url): array
    {
        $path = parse_url($url, PHP_URL_PATH);
        $path = trim($path, '/');
        $path = preg_replace('/\.git$/', '', $path);
        $parts = explode('/', $path);

        return [$parts[0], $parts[1]];
    }

    private function buildClient(Package $package): PendingRequest
    {
        $client = Http::baseUrl('https://api.github.com')
            ->accept('application/vnd.github.v3+json');

        if ($package->credential?->token) {
            $client = $client->withToken($package->credential->token);
        }

        return $client;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchAllTags(PendingRequest $client, string $owner, string $repo): array
    {
        $tags = [];
        $page = 1;

        do {
            $response = $client->get("/repos/{$owner}/{$repo}/tags", [
                'per_page' => 100,
                'page' => $page,
            ]);

            if (! $response->successful()) {
                if ($page === 1) {
                    throw new \RuntimeException("Failed to fetch tags from GitHub: HTTP {$response->status()} — {$response->body()}");
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
    private function fetchComposerJson(PendingRequest $client, string $owner, string $repo, string $ref): ?array
    {
        $response = $client->get("/repos/{$owner}/{$repo}/contents/composer.json", [
            'ref' => $ref,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $content = $response->json('content');
        if ($content === null) {
            return null;
        }

        $decoded = base64_decode($content);
        $json = json_decode($decoded, true);

        return is_array($json) ? $json : null;
    }

    /**
     * @return array{version: string, version_normalized: string, reference: string, composer_json: array<string, mixed>, released_at: string|null}|null
     */
    private function fetchDevVersion(PendingRequest $client, string $owner, string $repo): ?array
    {
        $response = $client->get("/repos/{$owner}/{$repo}");

        if (! $response->successful()) {
            return null;
        }

        $defaultBranch = $response->json('default_branch', 'main');

        $branchResponse = $client->get("/repos/{$owner}/{$repo}/branches/{$defaultBranch}");

        if (! $branchResponse->successful()) {
            return null;
        }

        $sha = $branchResponse->json('commit.sha');
        $composerJson = $this->fetchComposerJson($client, $owner, $repo, $defaultBranch);

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
