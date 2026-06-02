<?php

namespace Tests\Feature;

use App\Jobs\SyncPackage;
use App\Models\Credential;
use App\Models\Package;
use App\Models\Repository;
use App\Models\User;
use App\Services\PackageSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PackageManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_guests_cannot_access_packages_index(): void
    {
        $response = $this->get('/packages');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_view_packages_index(): void
    {
        Package::factory()->synced()->count(3)->create();

        $response = $this->actingAs($this->user)->get('/packages');

        $response->assertOk();
    }

    public function test_authenticated_users_can_view_create_form(): void
    {
        $response = $this->actingAs($this->user)->get('/packages/create');
        $response->assertOk();
    }

    public function test_authenticated_users_can_add_a_package(): void
    {
        Queue::fake();

        $repo = Repository::factory()->create();

        $response = $this->actingAs($this->user)->post('/packages', [
            'repository_url' => 'https://github.com/vendor/package',
            'type' => 'github',
            'repository_ids' => [$repo->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('packages', [
            'repository_url' => 'https://github.com/vendor/package',
            'type' => 'github',
            'is_syncing' => true,
        ]);

        Queue::assertPushed(SyncPackage::class);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->post('/packages', []);

        $response->assertSessionHasErrors(['repository_url', 'type', 'repository_ids']);
    }

    public function test_authenticated_users_can_view_package_details(): void
    {
        $package = Package::factory()->synced()->create();

        $response = $this->actingAs($this->user)->get("/packages/{$package->id}");

        $response->assertOk();
    }

    public function test_authenticated_users_can_delete_a_package(): void
    {
        $package = Package::factory()->create();

        $response = $this->actingAs($this->user)->delete("/packages/{$package->id}");

        $response->assertRedirect('/packages');
        $this->assertDatabaseMissing('packages', ['id' => $package->id]);
    }

    public function test_authenticated_users_can_sync_a_package(): void
    {
        Queue::fake();

        $package = Package::factory()->create();

        $response = $this->actingAs($this->user)->post("/packages/{$package->id}/sync");

        $response->assertRedirect();
        $this->assertTrue($package->fresh()->is_syncing);
        Queue::assertPushed(SyncPackage::class);
    }

    public function test_package_can_be_created_with_credential(): void
    {
        Queue::fake();

        $credential = Credential::factory()->github()->create();
        $repo = Repository::factory()->create();

        $response = $this->actingAs($this->user)->post('/packages', [
            'repository_url' => 'https://github.com/vendor/private-package',
            'type' => 'github',
            'credential_id' => $credential->id,
            'repository_ids' => [$repo->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('packages', [
            'credential_id' => $credential->id,
        ]);
    }

    public function test_authenticated_users_can_view_edit_form(): void
    {
        $package = Package::factory()->create();

        $response = $this->actingAs($this->user)->get("/packages/{$package->id}/edit");

        $response->assertOk();
    }

    public function test_authenticated_users_can_update_a_package(): void
    {
        $package = Package::factory()->create([
            'repository_url' => 'https://github.com/vendor/old-package',
            'type' => 'github',
            'download_dists' => false,
        ]);

        $response = $this->actingAs($this->user)->put("/packages/{$package->id}", [
            'repository_url' => 'https://github.com/vendor/new-package',
            'type' => 'gitlab',
            'download_dists' => true,
        ]);

        $response->assertRedirect("/packages/{$package->id}");
        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'repository_url' => 'https://github.com/vendor/new-package',
            'type' => 'gitlab',
            'download_dists' => true,
        ]);
    }

    public function test_update_validates_required_fields(): void
    {
        $package = Package::factory()->create();

        $response = $this->actingAs($this->user)->put("/packages/{$package->id}", []);

        $response->assertSessionHasErrors(['repository_url', 'type']);
    }

    public function test_update_can_change_credential(): void
    {
        $credential = Credential::factory()->github()->create();
        $package = Package::factory()->create(['credential_id' => null]);

        $response = $this->actingAs($this->user)->put("/packages/{$package->id}", [
            'repository_url' => $package->repository_url,
            'type' => $package->type->value,
            'credential_id' => $credential->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'credential_id' => $credential->id,
        ]);
    }

    public function test_package_can_be_assigned_to_repositories(): void
    {
        Queue::fake();

        $repo1 = Repository::factory()->create();
        $repo2 = Repository::factory()->create();

        $response = $this->actingAs($this->user)->post('/packages', [
            'repository_url' => 'https://github.com/vendor/multi-repo',
            'type' => 'github',
            'repository_ids' => [$repo1->id, $repo2->id],
        ]);

        $response->assertRedirect();

        $package = Package::where('repository_url', 'https://github.com/vendor/multi-repo')->first();
        $this->assertCount(2, $package->repositories);
    }

    public function test_can_attach_repository_to_package(): void
    {
        $package = Package::factory()->create();
        $repo = Repository::factory()->create();

        $response = $this->actingAs($this->user)->post("/packages/{$package->id}/repositories", [
            'repository_id' => $repo->id,
        ]);

        $response->assertRedirect();
        $this->assertTrue($package->repositories()->where('repositories.id', $repo->id)->exists());
    }

    public function test_attach_repository_requires_valid_repository_id(): void
    {
        $package = Package::factory()->create();

        $response = $this->actingAs($this->user)->post("/packages/{$package->id}/repositories", [
            'repository_id' => 9999,
        ]);

        $response->assertSessionHasErrors('repository_id');
    }

    public function test_can_detach_repository_from_package(): void
    {
        $package = Package::factory()->create();
        $repo = Repository::factory()->create();
        $package->repositories()->attach($repo->id);

        $response = $this->actingAs($this->user)->delete("/packages/{$package->id}/repositories/{$repo->id}");

        $response->assertRedirect();
        $this->assertFalse($package->repositories()->where('repositories.id', $repo->id)->exists());
    }

    public function test_show_page_includes_available_repositories(): void
    {
        $package = Package::factory()->create();
        $attachedRepo = Repository::factory()->create();
        $availableRepo = Repository::factory()->create();
        $package->repositories()->attach($attachedRepo->id);

        $response = $this->actingAs($this->user)->get("/packages/{$package->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('packages/show')
            ->has('availableRepositories', 1)
            ->where('availableRepositories.0.id', $availableRepo->id)
        );
    }

    public function test_cannot_sync_package_that_is_already_syncing(): void
    {
        Queue::fake();

        $package = Package::factory()->syncing()->create();

        $response = $this->actingAs($this->user)->post("/packages/{$package->id}/sync");

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Package is already syncing.');
        Queue::assertNotPushed(SyncPackage::class);
    }

    public function test_cannot_edit_package_that_is_syncing(): void
    {
        $package = Package::factory()->syncing()->create();

        $response = $this->actingAs($this->user)->get("/packages/{$package->id}/edit");

        $response->assertRedirect("/packages/{$package->id}");
        $response->assertSessionHas('error', 'Cannot edit while the package is syncing.');
    }

    public function test_cannot_update_package_that_is_syncing(): void
    {
        $package = Package::factory()->syncing()->create();

        $response = $this->actingAs($this->user)->put("/packages/{$package->id}", [
            'repository_url' => 'https://github.com/vendor/new-url',
            'type' => 'gitlab',
        ]);

        $response->assertRedirect("/packages/{$package->id}");
        $response->assertSessionHas('error', 'Cannot update while the package is syncing.');
    }

    public function test_sync_job_clears_is_syncing_on_success(): void
    {
        $package = Package::factory()->syncing()->create(['sync_progress' => 50]);

        $this->mock(PackageSynchronizer::class, function ($mock) {
            $mock->shouldReceive('sync')->once();
        });

        (new SyncPackage($package))->handle(app(PackageSynchronizer::class));

        $fresh = $package->fresh();
        $this->assertFalse($fresh->is_syncing);
        $this->assertSame(0, $fresh->sync_progress);
    }

    public function test_sync_job_clears_is_syncing_on_failure(): void
    {
        $package = Package::factory()->syncing()->create(['sync_progress' => 50]);

        $this->mock(PackageSynchronizer::class, function ($mock) {
            $mock->shouldReceive('sync')->once()->andThrow(new \RuntimeException('Sync failed'));
        });

        (new SyncPackage($package))->handle(app(PackageSynchronizer::class));

        $fresh = $package->fresh();
        $this->assertFalse($fresh->is_syncing);
        $this->assertSame(0, $fresh->sync_progress);
    }

    public function test_show_page_includes_is_syncing_status(): void
    {
        $package = Package::factory()->syncing()->create();

        $response = $this->actingAs($this->user)->get("/packages/{$package->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('packages/show')
            ->where('package.is_syncing', true)
        );
    }
}
