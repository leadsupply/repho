<?php

namespace Database\Factories;

use App\Enums\RepositoryAuthType;
use App\Models\Repository;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Repository>
 */
class RepositoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'auth_type' => RepositoryAuthType::None,
            'auth_username' => null,
            'auth_password' => null,
            'auth_token' => null,
        ];
    }

    public function public(): static
    {
        return $this->state([
            'auth_type' => RepositoryAuthType::None,
        ]);
    }

    public function basicAuth(string $username = 'user', string $password = 'secret'): static
    {
        return $this->state([
            'auth_type' => RepositoryAuthType::Basic,
            'auth_username' => $username,
            'auth_password' => $password,
        ]);
    }

    public function tokenAuth(string $token = 'test-token-123'): static
    {
        return $this->state([
            'auth_type' => RepositoryAuthType::Token,
            'auth_token' => $token,
        ]);
    }
}
