<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_user_with_arguments(): void
    {
        $this->artisan('user:create', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            '--password' => 'password123',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->artisan('user:create', [
            'name' => 'Jane',
            'email' => 'taken@example.com',
            '--password' => 'password123',
        ])->assertExitCode(1);
    }

    public function test_fails_with_short_password(): void
    {
        $this->artisan('user:create', [
            'name' => 'Jane',
            'email' => 'jane@example.com',
            '--password' => 'short',
        ])->assertExitCode(1);
    }

    public function test_password_is_hashed(): void
    {
        $this->artisan('user:create', [
            'name' => 'Jane',
            'email' => 'jane@example.com',
            '--password' => 'password123',
        ])->assertExitCode(0);

        $user = User::where('email', 'jane@example.com')->first();
        $this->assertNotEquals('password123', $user->password);
        $this->assertTrue(\Hash::check('password123', $user->password));
    }
}
