<?php

namespace Database\Factories;

use App\Enums\PackageType;
use App\Models\Credential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Credential>
 */
class CredentialFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true).' token',
            'type' => fake()->randomElement(PackageType::cases()),
            'token' => fake()->sha256(),
            'base_url' => null,
        ];
    }

    public function github(): static
    {
        return $this->state([
            'type' => PackageType::GitHub,
            'base_url' => null,
        ]);
    }

    public function gitlab(string $baseUrl = 'https://gitlab.com'): static
    {
        return $this->state([
            'type' => PackageType::GitLab,
            'base_url' => $baseUrl,
        ]);
    }

    public function git(): static
    {
        return $this->state([
            'type' => PackageType::Git,
        ]);
    }
}
