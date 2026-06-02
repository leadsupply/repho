<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Version>
 */
class VersionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $major = fake()->numberBetween(0, 5);
        $minor = fake()->numberBetween(0, 20);
        $patch = fake()->numberBetween(0, 10);
        $version = "$major.$minor.$patch";

        return [
            'package_id' => Package::factory(),
            'version' => $version,
            'version_normalized' => "$version.0",
            'reference' => fake()->sha1(),
            'composer_json' => [
                'name' => 'vendor/package',
                'description' => fake()->sentence(),
                'type' => 'library',
                'require' => ['php' => '>=8.1'],
                'autoload' => ['psr-4' => ['Vendor\\Package\\' => 'src/']],
                'license' => 'MIT',
            ],
            'released_at' => fake()->dateTimeBetween('-2 years'),
        ];
    }

    public function dev(): static
    {
        return $this->state([
            'version' => 'dev-main',
            'version_normalized' => 'dev-main',
            'released_at' => now(),
        ]);
    }
}
