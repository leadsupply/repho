<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\SecurityAdvisory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SecurityAdvisory>
 */
class SecurityAdvisoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'package_id' => Package::factory(),
            'advisory_id' => 'PKSA-'.fake()->unique()->bothify('####-####'),
            'title' => fake()->sentence(),
            'link' => fake()->url(),
            'cve' => 'CVE-'.fake()->year().'-'.fake()->numerify('#####'),
            'affected_versions' => '>='.fake()->numerify('#.#.#').',<'.fake()->numerify('#.#.#'),
            'severity' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'reported_at' => fake()->dateTimeBetween('-1 year'),
        ];
    }
}
