<?php

namespace App\Console\Commands;

use App\Models\Package;
use App\Services\PackageSynchronizer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('package:sync {package? : Package name or ID to sync}')]
#[Description('Synchronize packages from their remote repositories')]
class SyncPackages extends Command
{
    public function handle(PackageSynchronizer $synchronizer): int
    {
        if ($identifier = $this->argument('package')) {
            $package = Package::where('id', $identifier)
                ->orWhere('name', $identifier)
                ->first();

            if (! $package) {
                $this->error("Package not found: {$identifier}");

                return self::FAILURE;
            }

            return $this->syncPackage($synchronizer, $package);
        }

        $packages = Package::all();

        if ($packages->isEmpty()) {
            $this->info('No packages to sync.');

            return self::SUCCESS;
        }

        $failed = 0;
        $bar = $this->output->createProgressBar($packages->count());
        $bar->start();

        foreach ($packages as $package) {
            try {
                $synchronizer->sync($package);
                $this->line(" <info>Synced:</info> {$package->name}");
            } catch (\Throwable $e) {
                $failed++;
                $this->line(" <error>Failed:</error> {$package->name} - {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($failed > 0) {
            $this->warn("{$failed} package(s) failed to sync.");

            return self::FAILURE;
        }

        $this->info('All packages synced successfully.');

        return self::SUCCESS;
    }

    private function syncPackage(PackageSynchronizer $synchronizer, Package $package): int
    {
        $this->info("Syncing {$package->name}...");

        try {
            $synchronizer->sync($package);
            $versionCount = $package->versions()->count();
            $this->info("Synced successfully. {$versionCount} version(s) found.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Sync failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
