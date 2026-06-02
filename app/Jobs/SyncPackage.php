<?php

namespace App\Jobs;

use App\Models\Package;
use App\Services\PackageSynchronizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncPackage implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public Package $package,
    ) {}

    public function handle(PackageSynchronizer $synchronizer): void
    {
        try {
            $synchronizer->sync($this->package);
        } catch (\Throwable) {
            // sync_error is stored on the package by the synchronizer
        } finally {
            $this->package->update(['is_syncing' => false, 'sync_progress' => 0]);
        }
    }
}
