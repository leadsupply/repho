<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\SecurityAdvisory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_displays_security_advisories(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();
        SecurityAdvisory::factory()->for($package)->count(3)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('advisories', 3)
            ->where('advisories.0.package_name', $package->name)
        );
    }

    public function test_dashboard_limits_advisories_to_10(): void
    {
        $user = User::factory()->create();
        SecurityAdvisory::factory()->count(15)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('advisories', 10)
        );
    }

    public function test_dashboard_shows_empty_advisories_when_none_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('advisories', 0)
        );
    }
}
