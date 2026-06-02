<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Repository;
use App\Services\PackageSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncPackagesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_command_with_no_packages(): void
    {
        $this->artisan('package:sync')
            ->expectsOutput('No packages to sync.')
            ->assertExitCode(0);
    }

    public function test_sync_command_syncs_specific_package(): void
    {
        $package = Package::factory()->create(['name' => 'vendor/test']);

        $this->mock(PackageSynchronizer::class, function ($mock) {
            $mock->shouldReceive('sync')->once();
        });

        $this->artisan('package:sync', ['package' => 'vendor/test'])
            ->assertExitCode(0);
    }

    public function test_sync_command_fails_for_unknown_package(): void
    {
        $this->artisan('package:sync', ['package' => 'vendor/nonexistent'])
            ->expectsOutput('Package not found: vendor/nonexistent')
            ->assertExitCode(1);
    }

    public function test_add_command_creates_and_syncs_package(): void
    {
        $this->mock(PackageSynchronizer::class, function ($mock) {
            $mock->shouldReceive('sync')->once();
        });

        $this->artisan('package:add', ['url' => 'https://github.com/vendor/new-pkg'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('packages', [
            'name' => 'vendor/new-pkg',
            'type' => 'github',
        ]);
    }

    public function test_add_command_detects_gitlab_type(): void
    {
        $this->mock(PackageSynchronizer::class, function ($mock) {
            $mock->shouldReceive('sync')->once();
        });

        $this->artisan('package:add', ['url' => 'https://gitlab.com/vendor/pkg'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('packages', [
            'type' => 'gitlab',
        ]);
    }

    public function test_add_command_defaults_to_git_type(): void
    {
        $this->mock(PackageSynchronizer::class, function ($mock) {
            $mock->shouldReceive('sync')->once();
        });

        $this->artisan('package:add', ['url' => 'https://gitea.example.com/vendor/pkg'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('packages', [
            'type' => 'git',
        ]);
    }

    public function test_add_command_attaches_to_default_repo(): void
    {
        $repo = Repository::factory()->create(['slug' => 'default']);

        $this->mock(PackageSynchronizer::class, function ($mock) {
            $mock->shouldReceive('sync')->once();
        });

        $this->artisan('package:add', ['url' => 'https://github.com/vendor/repo-test'])
            ->assertExitCode(0);

        $package = Package::where('name', 'vendor/repo-test')->first();
        $this->assertTrue($repo->packages()->where('packages.id', $package->id)->exists());
    }

    public function test_add_command_attaches_to_specified_repo(): void
    {
        $repo = Repository::factory()->create(['slug' => 'custom']);

        $this->mock(PackageSynchronizer::class, function ($mock) {
            $mock->shouldReceive('sync')->once();
        });

        $this->artisan('package:add', [
            'url' => 'https://github.com/vendor/custom-pkg',
            '--repo' => ['custom'],
        ])->assertExitCode(0);

        $package = Package::where('name', 'vendor/custom-pkg')->first();
        $this->assertTrue($repo->packages()->where('packages.id', $package->id)->exists());
    }
}
