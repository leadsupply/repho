<?php

namespace Tests\Feature;

use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetPackageSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_specific_package_by_name(): void
    {
        $package = Package::factory()->syncing()->create(['name' => 'vendor/alpha']);

        $this->artisan('package:reset-sync', ['package' => 'vendor/alpha', '--force' => true])
            ->expectsOutput('Sync flag reset for [vendor/alpha].')
            ->assertExitCode(0);

        $this->assertFalse($package->fresh()->is_syncing);
    }

    public function test_reset_specific_package_by_id(): void
    {
        $package = Package::factory()->syncing()->create();

        $this->artisan('package:reset-sync', ['package' => $package->id, '--force' => true])
            ->assertExitCode(0);

        $this->assertFalse($package->fresh()->is_syncing);
    }

    public function test_reset_all_packages(): void
    {
        $packages = Package::factory()->syncing()->count(3)->create();

        $this->artisan('package:reset-sync', ['--force' => true])
            ->expectsOutput('Sync flag reset for 3 package(s).')
            ->assertExitCode(0);

        foreach ($packages as $package) {
            $this->assertFalse($package->fresh()->is_syncing);
        }
    }

    public function test_reset_all_only_affects_syncing_packages(): void
    {
        Package::factory()->syncing()->count(2)->create();
        Package::factory()->create();

        $this->artisan('package:reset-sync', ['--force' => true])
            ->expectsOutput('Sync flag reset for 2 package(s).')
            ->assertExitCode(0);
    }

    public function test_reset_all_with_no_syncing_packages(): void
    {
        Package::factory()->count(2)->create();

        $this->artisan('package:reset-sync', ['--force' => true])
            ->expectsOutput('No synced packages to reset.')
            ->assertExitCode(0);
    }

    public function test_fails_for_unknown_package(): void
    {
        $this->artisan('package:reset-sync', ['package' => 'vendor/nonexistent'])
            ->expectsOutput('Package not found: vendor/nonexistent')
            ->assertExitCode(1);
    }

    public function test_prompts_confirmation_for_specific_package(): void
    {
        $package = Package::factory()->syncing()->create(['name' => 'vendor/alpha']);

        $this->artisan('package:reset-sync', ['package' => 'vendor/alpha'])
            ->expectsConfirmation('Reset sync flag for [vendor/alpha]?', 'yes')
            ->assertExitCode(0);

        $this->assertFalse($package->fresh()->is_syncing);
    }

    public function test_cancels_when_confirmation_denied_for_specific_package(): void
    {
        $package = Package::factory()->syncing()->create(['name' => 'vendor/alpha']);

        $this->artisan('package:reset-sync', ['package' => 'vendor/alpha'])
            ->expectsConfirmation('Reset sync flag for [vendor/alpha]?', 'no')
            ->expectsOutput('Operation cancelled.')
            ->assertExitCode(0);

        $this->assertTrue($package->fresh()->is_syncing);
    }

    public function test_prompts_confirmation_for_all_packages(): void
    {
        Package::factory()->syncing()->count(2)->create();

        $this->artisan('package:reset-sync')
            ->expectsConfirmation('Reset sync flag for all 2 synced package(s)?', 'yes')
            ->assertExitCode(0);
    }

    public function test_cancels_when_confirmation_denied_for_all_packages(): void
    {
        $packages = Package::factory()->syncing()->count(2)->create();

        $this->artisan('package:reset-sync')
            ->expectsConfirmation('Reset sync flag for all 2 synced package(s)?', 'no')
            ->expectsOutput('Operation cancelled.')
            ->assertExitCode(0);

        foreach ($packages as $package) {
            $this->assertTrue($package->fresh()->is_syncing);
        }
    }
}
