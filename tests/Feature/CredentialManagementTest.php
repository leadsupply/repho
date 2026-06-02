<?php

namespace Tests\Feature;

use App\Models\Credential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CredentialManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_guests_cannot_access_credentials(): void
    {
        $response = $this->get('/credentials');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_view_credentials_index(): void
    {
        Credential::factory()->count(2)->create();

        $response = $this->actingAs($this->user)->get('/credentials');

        $response->assertOk();
    }

    public function test_authenticated_users_can_view_create_form(): void
    {
        $response = $this->actingAs($this->user)->get('/credentials/create');
        $response->assertOk();
    }

    public function test_authenticated_users_can_create_a_credential(): void
    {
        $response = $this->actingAs($this->user)->post('/credentials', [
            'name' => 'My GitHub Token',
            'type' => 'github',
            'token' => 'ghp_test_token_12345',
        ]);

        $response->assertRedirect('/credentials');
        $this->assertDatabaseHas('credentials', [
            'name' => 'My GitHub Token',
            'type' => 'github',
        ]);
    }

    public function test_token_is_stored_encrypted(): void
    {
        $this->actingAs($this->user)->post('/credentials', [
            'name' => 'Test Token',
            'type' => 'github',
            'token' => 'ghp_plaintext_token',
        ]);

        $credential = Credential::first();
        $this->assertEquals('ghp_plaintext_token', $credential->token);

        $rawToken = \DB::table('credentials')->where('id', $credential->id)->value('token');
        $this->assertNotEquals('ghp_plaintext_token', $rawToken);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->post('/credentials', []);

        $response->assertSessionHasErrors(['name', 'type', 'token']);
    }

    public function test_authenticated_users_can_delete_a_credential(): void
    {
        $credential = Credential::factory()->create();

        $response = $this->actingAs($this->user)->delete("/credentials/{$credential->id}");

        $response->assertRedirect('/credentials');
        $this->assertDatabaseMissing('credentials', ['id' => $credential->id]);
    }

    public function test_authenticated_users_can_view_edit_form(): void
    {
        $credential = Credential::factory()->create();

        $response = $this->actingAs($this->user)->get("/credentials/{$credential->id}/edit");

        $response->assertOk();
    }

    public function test_authenticated_users_can_update_a_credential(): void
    {
        $credential = Credential::factory()->github()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->user)->put("/credentials/{$credential->id}", [
            'name' => 'New Name',
            'type' => 'github',
        ]);

        $response->assertRedirect('/credentials');
        $this->assertDatabaseHas('credentials', [
            'id' => $credential->id,
            'name' => 'New Name',
        ]);
    }

    public function test_update_keeps_token_when_not_provided(): void
    {
        $credential = Credential::factory()->github()->create(['token' => 'original-token']);

        $this->actingAs($this->user)->put("/credentials/{$credential->id}", [
            'name' => $credential->name,
            'type' => 'github',
        ]);

        $credential->refresh();
        $this->assertEquals('original-token', $credential->token);
    }

    public function test_update_changes_token_when_provided(): void
    {
        $credential = Credential::factory()->github()->create(['token' => 'old-token']);

        $this->actingAs($this->user)->put("/credentials/{$credential->id}", [
            'name' => $credential->name,
            'type' => 'github',
            'token' => 'new-token',
        ]);

        $credential->refresh();
        $this->assertEquals('new-token', $credential->token);
    }

    public function test_update_validates_required_fields(): void
    {
        $credential = Credential::factory()->create();

        $response = $this->actingAs($this->user)->put("/credentials/{$credential->id}", []);

        $response->assertSessionHasErrors(['name', 'type']);
    }

    public function test_credential_with_base_url(): void
    {
        $this->actingAs($this->user)->post('/credentials', [
            'name' => 'Self-hosted GitLab',
            'type' => 'gitlab',
            'token' => 'glpat-test-token',
            'base_url' => 'https://gitlab.example.com',
        ]);

        $this->assertDatabaseHas('credentials', [
            'name' => 'Self-hosted GitLab',
            'type' => 'gitlab',
            'base_url' => 'https://gitlab.example.com',
        ]);
    }
}
