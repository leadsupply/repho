<?php

namespace App\Jobs;

use App\Enums\PackageType;
use App\Models\Version;
use App\Services\GitHubPackageFetcher;
use App\Services\GitLabPackageFetcher;
use App\Services\GitPackageFetcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DownloadVersionDist implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public Version $version,
    ) {}

    public function handle(): void
    {
        $package = $this->version->package;
        $ref = $this->version->reference;

        $cachePath = config('repho.dist_cache_path')."/{$package->vendor()}/{$package->shortName()}/{$ref}.zip";

        if (file_exists($cachePath)) {
            return;
        }

        $fetcher = match ($package->type) {
            PackageType::GitHub => app(GitHubPackageFetcher::class),
            PackageType::GitLab => app(GitLabPackageFetcher::class),
            PackageType::Git => app(GitPackageFetcher::class),
        };

        $archive = $fetcher->getDistArchive($package, $ref);

        if ($archive === null) {
            return;
        }

        $cacheDir = dirname($cachePath);
        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        file_put_contents($cachePath, $archive);
    }
}
