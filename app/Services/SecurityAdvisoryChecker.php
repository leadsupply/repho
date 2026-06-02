<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class SecurityAdvisoryChecker
{
    /**
     * Check a package for known security advisories via the Packagist API.
     *
     * @return array<int, array{advisoryId: string, title: string, link: string, cve: string|null, affectedVersions: string, reportedAt: string, severity: string|null}>
     *
     * @throws ConnectionException
     */
    public function check(string $packageName): array
    {
        $response = Http::get('https://packagist.org/api/security-advisories/', [
            'packages' => [$packageName],
        ]);

        if ($response->failed()) {
            return [];
        }

        /** @var array<string, array<int, array{advisoryId: string, title: string, link: string, cve: string|null, affectedVersions: string, reportedAt: string, severity: string|null}>> $advisories */
        $advisories = $response->json('advisories', []);

        return $advisories[$packageName] ?? [];
    }
}
