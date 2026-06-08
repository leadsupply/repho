<?php

namespace Database\Factories;

use App\Enums\RepositoryAuthType;
use App\Models\ProxySetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProxySetting>
 */
class ProxySettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enabled' => false,
            'auth_type' => RepositoryAuthType::None,
            'auth_username' => null,
            'auth_password' => null,
            'auth_token' => null,
            'metadata_cache_ttl' => 3600,
        ];
    }

    public function enabled(): static
    {
        return $this->state([
            'enabled' => true,
        ]);
    }

    public function disabled(): static
    {
        return $this->state([
            'enabled' => false,
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
