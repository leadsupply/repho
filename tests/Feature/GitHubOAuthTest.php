<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GitHubOAuthTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_redirect_sends_to_github(): void
    {
        config([
            'services.github.client_id' => 'test-client-id',
            'services.github.client_secret' => 'test-client-secret',
        ]);

        $response = $this->actingAs($this->user)->get(route('github.redirect'));

        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertStringContainsString('github.com/login/oauth/authorize', $location);
        $this->assertStringContainsString('client_id=test-client-id', $location);
        $this->assertStringContainsString('scope=repo', $location);
        $this->assertStringContainsString('state=', $location);
    }

    public function test_callback_exchanges_code_for_token(): void
    {
        config([
            'services.github.client_id' => 'test-client-id',
            'services.github.client_secret' => 'test-client-secret',
        ]);

        Http::fake([
            'github.com/login/oauth/access_token' => Http::response([
                'access_token' => 'gho_test_token_123',
                'token_type' => 'bearer',
                'scope' => 'repo',
            ]),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['github_oauth_state' => 'test-state'])
            ->get(route('github.callback', [
                'code' => 'test-code',
                'state' => 'test-state',
            ]));

        $response->assertRedirect(route('credentials.create'));
        $response->assertSessionHas('github_token', 'gho_test_token_123');
    }

    public function test_callback_rejects_invalid_state(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['github_oauth_state' => 'correct-state'])
            ->get(route('github.callback', [
                'code' => 'test-code',
                'state' => 'wrong-state',
            ]));

        $response->assertRedirect(route('credentials.create'));
        $response->assertSessionHas('error');
        $response->assertSessionMissing('github_token');
    }

    public function test_callback_handles_denied_authorization(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['github_oauth_state' => 'test-state'])
            ->get(route('github.callback', [
                'error' => 'access_denied',
                'state' => 'test-state',
            ]));

        $response->assertRedirect(route('credentials.create'));
        $response->assertSessionHas('error');
    }

    public function test_create_page_receives_github_token_from_session(): void
    {
        config(['services.github.client_id' => 'test-client-id']);

        $response = $this->actingAs($this->user)
            ->withSession(['github_token' => 'gho_flashed_token'])
            ->get(route('credentials.create'));

        $response->assertOk();
    }

    public function test_create_page_shows_oauth_disabled_when_no_client_id(): void
    {
        config(['services.github.client_id' => null]);

        $response = $this->actingAs($this->user)
            ->get(route('credentials.create'));

        $response->assertOk();
    }

    public function test_guests_cannot_access_oauth_routes(): void
    {
        $this->get(route('github.redirect'))->assertRedirect('/login');
        $this->get(route('github.callback'))->assertRedirect('/login');
    }
}
