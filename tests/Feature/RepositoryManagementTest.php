<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepositoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_guests_cannot_access_repositories(): void
    {
        $this->get('/repositories')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_view_repositories_index(): void
    {
        Repository::factory()->count(2)->create();

        $response = $this->actingAs($this->user)->get('/repositories');

        $response->assertOk();
    }

    public function test_authenticated_users_can_create_a_public_repository(): void
    {
        $response = $this->actingAs($this->user)->post('/repositories', [
            'name' => 'My Repo',
            'slug' => 'my-repo',
            'auth_type' => 'none',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repositories', [
            'slug' => 'my-repo',
            'auth_type' => 'none',
        ]);
    }

    public function test_authenticated_users_can_create_a_basic_auth_repository(): void
    {
        $response = $this->actingAs($this->user)->post('/repositories', [
            'name' => 'Private Repo',
            'slug' => 'private-repo',
            'auth_type' => 'basic',
            'auth_username' => 'admin',
            'auth_password' => 'secret123',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repositories', [
            'slug' => 'private-repo',
            'auth_type' => 'basic',
            'auth_username' => 'admin',
        ]);
    }

    public function test_authenticated_users_can_create_a_token_auth_repository(): void
    {
        $response = $this->actingAs($this->user)->post('/repositories', [
            'name' => 'Token Repo',
            'slug' => 'token-repo',
            'auth_type' => 'token',
            'auth_token' => 'my-secret-token',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repositories', [
            'slug' => 'token-repo',
            'auth_type' => 'token',
        ]);
    }

    public function test_slug_must_be_unique(): void
    {
        Repository::factory()->create(['slug' => 'taken']);

        $response = $this->actingAs($this->user)->post('/repositories', [
            'name' => 'Another',
            'slug' => 'taken',
            'auth_type' => 'none',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_slug_must_be_lowercase_alphanumeric(): void
    {
        $response = $this->actingAs($this->user)->post('/repositories', [
            'name' => 'Bad Slug',
            'slug' => 'Bad Slug!',
            'auth_type' => 'none',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_basic_auth_requires_username_and_password(): void
    {
        $response = $this->actingAs($this->user)->post('/repositories', [
            'name' => 'Missing Auth',
            'slug' => 'missing-auth',
            'auth_type' => 'basic',
        ]);

        $response->assertSessionHasErrors(['auth_username', 'auth_password']);
    }

    public function test_cannot_delete_default_repository(): void
    {
        $default = Repository::factory()->create(['slug' => 'default']);

        $response = $this->actingAs($this->user)->delete("/repositories/{$default->id}");

        $response->assertRedirect();
        $this->assertDatabaseHas('repositories', ['slug' => 'default']);
    }

    public function test_can_delete_non_default_repository(): void
    {
        $repo = Repository::factory()->create(['slug' => 'deletable']);

        $response = $this->actingAs($this->user)->delete("/repositories/{$repo->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('repositories', ['slug' => 'deletable']);
    }

    public function test_can_attach_package_to_repository(): void
    {
        $repo = Repository::factory()->create();
        $package = Package::factory()->create();

        $response = $this->actingAs($this->user)->post("/repositories/{$repo->id}/packages", [
            'package_id' => $package->id,
        ]);

        $response->assertRedirect();
        $this->assertTrue($repo->packages()->where('packages.id', $package->id)->exists());
    }

    public function test_can_detach_package_from_repository(): void
    {
        $repo = Repository::factory()->create();
        $package = Package::factory()->create();
        $repo->packages()->attach($package->id);

        $response = $this->actingAs($this->user)->delete("/repositories/{$repo->id}/packages/{$package->id}");

        $response->assertRedirect();
        $this->assertFalse($repo->packages()->where('packages.id', $package->id)->exists());
    }

    public function test_can_update_repository(): void
    {
        $repo = Repository::factory()->create(['name' => 'Old Name', 'slug' => 'old-slug']);

        $response = $this->actingAs($this->user)->put("/repositories/{$repo->id}", [
            'name' => 'New Name',
            'slug' => 'new-slug',
            'auth_type' => 'none',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repositories', ['slug' => 'new-slug', 'name' => 'New Name']);
    }
}
