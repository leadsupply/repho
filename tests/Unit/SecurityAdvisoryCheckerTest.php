<?php

namespace Tests\Unit;

use App\Services\SecurityAdvisoryChecker;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SecurityAdvisoryCheckerTest extends TestCase
{
    public function test_returns_advisories_for_vulnerable_package(): void
    {
        Http::fake([
            'packagist.org/api/security-advisories/*' => Http::response([
                'advisories' => [
                    'vendor/package' => [
                        [
                            'advisoryId' => 'PKSA-1234',
                            'title' => 'SQL Injection',
                            'link' => 'https://example.com/advisory',
                            'cve' => 'CVE-2024-1234',
                            'affectedVersions' => '>=1.0,<1.5',
                            'reportedAt' => '2024-01-15',
                            'severity' => 'high',
                        ],
                    ],
                ],
            ]),
        ]);

        $checker = new SecurityAdvisoryChecker;
        $advisories = $checker->check('vendor/package');

        $this->assertCount(1, $advisories);
        $this->assertSame('PKSA-1234', $advisories[0]['advisoryId']);
        $this->assertSame('SQL Injection', $advisories[0]['title']);
        $this->assertSame('CVE-2024-1234', $advisories[0]['cve']);
    }

    public function test_returns_empty_array_for_safe_package(): void
    {
        Http::fake([
            'packagist.org/api/security-advisories/*' => Http::response([
                'advisories' => [],
            ]),
        ]);

        $checker = new SecurityAdvisoryChecker;
        $advisories = $checker->check('vendor/safe-package');

        $this->assertEmpty($advisories);
    }

    public function test_returns_empty_array_when_api_fails(): void
    {
        Http::fake([
            'packagist.org/api/security-advisories/*' => Http::response(null, 500),
        ]);

        $checker = new SecurityAdvisoryChecker;
        $advisories = $checker->check('vendor/package');

        $this->assertEmpty($advisories);
    }

    public function test_returns_empty_array_when_package_not_in_response(): void
    {
        Http::fake([
            'packagist.org/api/security-advisories/*' => Http::response([
                'advisories' => [
                    'other/package' => [
                        [
                            'advisoryId' => 'PKSA-9999',
                            'title' => 'Some issue',
                            'link' => 'https://example.com',
                            'cve' => null,
                            'affectedVersions' => '>=1.0',
                            'reportedAt' => '2024-01-01',
                            'severity' => 'low',
                        ],
                    ],
                ],
            ]),
        ]);

        $checker = new SecurityAdvisoryChecker;
        $advisories = $checker->check('vendor/package');

        $this->assertEmpty($advisories);
    }

    public function test_returns_multiple_advisories(): void
    {
        Http::fake([
            'packagist.org/api/security-advisories/*' => Http::response([
                'advisories' => [
                    'vendor/package' => [
                        [
                            'advisoryId' => 'PKSA-1',
                            'title' => 'First issue',
                            'link' => 'https://example.com/1',
                            'cve' => 'CVE-2024-0001',
                            'affectedVersions' => '>=1.0,<1.5',
                            'reportedAt' => '2024-01-01',
                            'severity' => 'high',
                        ],
                        [
                            'advisoryId' => 'PKSA-2',
                            'title' => 'Second issue',
                            'link' => 'https://example.com/2',
                            'cve' => null,
                            'affectedVersions' => '>=2.0,<2.3',
                            'reportedAt' => '2024-02-01',
                            'severity' => 'medium',
                        ],
                    ],
                ],
            ]),
        ]);

        $checker = new SecurityAdvisoryChecker;
        $advisories = $checker->check('vendor/package');

        $this->assertCount(2, $advisories);
        $this->assertSame('PKSA-1', $advisories[0]['advisoryId']);
        $this->assertSame('PKSA-2', $advisories[1]['advisoryId']);
    }
}
