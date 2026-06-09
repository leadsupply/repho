<?php

namespace App\Services;

use App\Enums\RepositoryAuthType;
use App\Models\ProxySetting;
use App\Models\ProxyUpstream;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class ComposerProxyService
{
    private ProxySetting $settings;

    /** @var Collection<int, ProxyUpstream> */
    private Collection $upstreams;

    public function __construct()
    {
        $this->settings = ProxySetting::instance();
        $this->upstreams = ProxyUpstream::query()
            ->where('enabled', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Merge packages.json from all enabled upstreams.
     *
     * @return array<string, mixed>
     */
    public function getPackagesJson(): array
    {
        $allPackages = [];
        $hasResponse = false;

        foreach ($this->upstreams as $upstream) {
            $cachePath = $this->metadataCachePath($upstream, 'packages.json');
            $data = $this->fetchWithCache($upstream, $cachePath, '/packages.json');

            if ($data === null) {
                continue;
            }

            $hasResponse = true;
            $packages = $data['available-packages'] ?? [];
            $allPackages = array_merge($allPackages, $packages);
        }

        if (! $hasResponse) {
            return [];
        }

        return [
            'metadata-url' => url('/proxy/p2/%package%.json'),
            'available-packages' => array_values(array_unique($allPackages)),
        ];
    }

    /**
     * Get package metadata from the first upstream that has it (priority order).
     *
     * @return array<string, mixed>|null
     */
    public function getPackageMetadata(string $vendor, string $package): ?array
    {
        $upstreamPath = "/p2/{$vendor}/{$package}.json";

        foreach ($this->upstreams as $upstream) {
            $cachePath = $this->metadataCachePath($upstream, "p2/{$vendor}/{$package}.json");
            $data = $this->fetchWithCache($upstream, $cachePath, $upstreamPath);

            if ($data !== null) {
                $this->rewriteDistUrls($data);

                return $data;
            }
        }

        return null;
    }

    public function getDistFile(string $encodedUrl): ?string
    {
        $originalUrl = base64_decode($encodedUrl, true);

        if ($originalUrl === false || ! filter_var($originalUrl, FILTER_VALIDATE_URL)) {
            return null;
        }

        $hash = hash('sha256', $originalUrl);
        $cachePath = $this->distCachePath("{$hash}.zip");

        if (file_exists($cachePath)) {
            return $cachePath;
        }

        try {
            $response = Http::withOptions(['timeout' => 120])->get($originalUrl);

            if ($response->failed()) {
                return null;
            }

            $cacheDir = dirname($cachePath);
            if (! is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }

            file_put_contents($cachePath, $response->body());

            return $cachePath;
        } catch (ConnectionException) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchWithCache(ProxyUpstream $upstream, string $cachePath, string $upstreamPath): ?array
    {
        $hasCache = file_exists($cachePath);
        $cacheExpired = ! $hasCache || $this->isCacheExpired($cachePath);

        if ($cacheExpired) {
            try {
                $response = $this->buildUpstreamClient($upstream)
                    ->get(rtrim($upstream->upstream_url, '/').$upstreamPath);

                if ($response->successful()) {
                    $cacheDir = dirname($cachePath);
                    if (! is_dir($cacheDir)) {
                        mkdir($cacheDir, 0755, true);
                    }

                    file_put_contents($cachePath, $response->body());

                    return $response->json();
                }
            } catch (ConnectionException) {
                // Fall through to serve stale cache if available
            }
        }

        if ($hasCache) {
            $contents = file_get_contents($cachePath);

            return $contents !== false ? json_decode($contents, true) : null;
        }

        return null;
    }

    private function buildUpstreamClient(ProxyUpstream $upstream): PendingRequest
    {
        $client = Http::withOptions(['timeout' => 30])
            ->acceptJson();

        return match ($upstream->auth_type) {
            RepositoryAuthType::None => $client,
            RepositoryAuthType::Basic => $client->withBasicAuth(
                (string) $upstream->auth_username,
                (string) $upstream->auth_password,
            ),
            RepositoryAuthType::Token => $client->withToken(
                (string) $upstream->auth_token,
            ),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function rewriteDistUrls(array &$data): void
    {
        if (! isset($data['packages']) || ! is_array($data['packages'])) {
            return;
        }

        foreach ($data['packages'] as &$versions) {
            if (! is_array($versions)) {
                continue;
            }

            foreach ($versions as &$version) {
                if (! is_array($version)) {
                    continue;
                }

                if (isset($version['dist']['url']) && is_string($version['dist']['url'])) {
                    $encoded = base64_encode($version['dist']['url']);
                    $version['dist']['url'] = url('/proxy/dists/'.rtrim($encoded, '='));
                }
            }
        }
    }

    private function isCacheExpired(string $cachePath): bool
    {
        $mtime = filemtime($cachePath);

        if ($mtime === false) {
            return true;
        }

        return (time() - $mtime) > $this->settings->metadata_cache_ttl;
    }

    private function metadataCachePath(ProxyUpstream $upstream, string $relativePath): string
    {
        return config('repho.proxy_cache_path')."/metadata/{$upstream->id}/".$relativePath;
    }

    private function distCachePath(string $relativePath): string
    {
        return config('repho.proxy_cache_path').'/dists/'.$relativePath;
    }
}
