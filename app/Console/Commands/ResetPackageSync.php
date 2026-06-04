<?php

namespace App\Console\Commands;

use App\Models\Package;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('package:reset-sync {package? : Package name or ID to reset} {--force : Skip confirmation}')]
#[Description('Reset the sync flag for one or all packages')]
class ResetPackageSync extends Command
{
    public function handle(): int
    {
        if ($identifier = $this->argument('package')) {
            $package = Package::where('id', $identifier)
                ->orWhere('name', $identifier)
                ->first();

            if (! $package) {
                $this->error("Package not found: {$identifier}");

                return self::FAILURE;
            }

            if (! $this->option('force') && ! $this->confirm("Reset sync flag for [{$package->name}]?")) {
                $this->info('Operation cancelled.');

                return self::SUCCESS;
            }

            $package->update(['is_syncing' => false]);
            $this->info("Sync flag reset for [{$package->name}].");

            return self::SUCCESS;
        }

        $count = Package::where('is_syncing', true)->count();

        if ($count === 0) {
            $this->info('No synced packages to reset.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Reset sync flag for all {$count} synced package(s)?")) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        Package::where('is_syncing', true)->update(['is_syncing' => false]);
        $this->info("Sync flag reset for {$count} package(s).");

        return self::SUCCESS;
    }
}
