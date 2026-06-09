<?php

namespace Tests\Feature;

use App\Models\ProxySetting;
use App\Models\ProxyUpstream;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ComposerProxyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_proxy_returns_404_when_disabled(): void
    {
        ProxySetting::factory()->disabled()->create();

        $response = $this->getJson('/proxy/packages.json');

        $response->assertNotFound();
    }

    public function test_proxy_packages_json_returns_rewritten_metadata_url(): void
    {
        ProxySetting::factory()->enabled()->create();
        ProxyUpstream::factory()->create(['upstream_url' => 'https://repo.packagist.org']);

        Http::fake([
            'repo.packagist.org/packages.json' => Http::response([
                'metadata-url' => '/p2/%package%.json',
                'available-packages' => ['monolog/monolog'],
            ]),
        ]);

        $response = $this->getJson('/proxy/packages.json');

        $response->assertOk()
            ->assertJson([
                'metadata-url' => url('/proxy/p2/%package%.json'),
                'available-packages' => ['monolog/monolog'],
            ]);
    }

    public function test_proxy_packages_json_merges_multiple_upstreams(): void
    {
        ProxySetting::factory()->enabled()->create();
        ProxyUpstream::factory()->create([
            'name' => 'Packagist',
            'upstream_url' => 'https://repo.packagist.org',
            'sort_order' => 0,
        ]);
        ProxyUpstream::factory()->create([
            'name' => 'Private',
            'upstream_url' => 'https://private.example.com',
            'sort_order' => 1,
        ]);

        Http::fake([
            'repo.packagist.org/packages.json' => Http::response([
                'metadata-url' => '/p2/%package%.json',
                'available-packages' => ['monolog/monolog'],
            ]),
            'private.example.com/packages.json' => Http::response([
                'metadata-url' => '/p2/%package%.json',
                'available-packages' => ['acme/private-lib'],
            ]),
        ]);

        $response = $this->getJson('/proxy/packages.json');

        $response->assertOk();

        $packages = $response->json('available-packages');
        $this->assertContains('monolog/monolog', $packages);
        $this->assertContains('acme/private-lib', $packages);
    }

    public function test_proxy_package_metadata_uses_first_match(): void
    {
        ProxySetting::factory()->enabled()->create();
        ProxyUpstream::factory()->create([
            'name' => 'Primary',
            'upstream_url' => 'https://primary.example.com',
            'sort_order' => 0,
        ]);
        ProxyUpstream::factory()->create([
            'name' => 'Secondary',
            'upstream_url' => 'https://secondary.example.com',
            'sort_order' => 1,
        ]);

        Http::fake([
            'primary.example.com/p2/vendor/pkg.json' => Http::response([
                'minified' => 'composer/2.0',
                'packages' => [
                    'vendor/pkg' => [[
                        'name' => 'vendor/pkg',
                        'version' => '1.0.0',
                        'dist' => [
                            'type' => 'zip',
                            'url' => 'https://primary.example.com/dist/abc.zip',
                            'reference' => 'abc123',
                        ],
                    ]],
                ],
            ]),
            'secondary.example.com/p2/vendor/pkg.json' => Http::response([
                'minified' => 'composer/2.0',
                'packages' => [
                    'vendor/pkg' => [[
                        'name' => 'vendor/pkg',
                        'version' => '2.0.0',
                    ]],
                ],
            ]),
        ]);

        $response = $this->getJson('/proxy/p2/vendor/pkg.json');

        $response->assertOk()
            ->assertJsonFragment(['version' => '1.0.0']);

        // Should NOT contain version from secondary
        $this->assertStringNotContainsString('2.0.0', $response->content());
    }

    public function test_proxy_skips_disabled_upstreams(): void
    {
        ProxySetting::factory()->enabled()->create();
        ProxyUpstream::factory()->disabled()->create([
            'name' => 'Disabled',
            'upstream_url' => 'https://disabled.example.com',
            'sort_order' => 0,
        ]);
        ProxyUpstream::factory()->enabled()->create([
            'name' => 'Active',
            'upstream_url' => 'https://active.example.com',
            'sort_order' => 1,
        ]);

        Http::fake([
            'disabled.example.com/*' => Http::response('should not be called', 500),
            'active.example.com/packages.json' => Http::response([
                'metadata-url' => '/p2/%package%.json',
                'available-packages' => ['active/pkg'],
            ]),
        ]);

        $response = $this->getJson('/proxy/packages.json');

        $response->assertOk()
            ->assertJsonFragment(['available-packages' => ['active/pkg']]);
    }

    public function test_proxy_package_metadata_rewrites_dist_urls(): void
    {
        ProxySetting::factory()->enabled()->create();
        ProxyUpstream::factory()->create(['upstream_url' => 'https://repo.packagist.org']);

        Http::fake([
            'repo.packagist.org/p2/monolog/monolog.json' => Http::response([
                'minified' => 'composer/2.0',
                'packages' => [
                    'monolog/monolog' => [
                        [
                            'name' => 'monolog/monolog',
                            'version' => '3.0.0',
                            'dist' => [
                                'type' => 'zip',
                                'url' => 'https://api.github.com/repos/Seldaek/monolog/zipball/abc123',
                                'reference' => 'abc123',
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->getJson('/proxy/p2/monolog/monolog.json');

        $response->assertOk();

        $distUrl = $response->json('packages.monolog/monolog.0.dist.url');
        $this->assertStringStartsWith(url('/proxy/dists/'), $distUrl);
    }

    public function test_proxy_package_metadata_returns_404_when_no_upstream_has_it(): void
    {
        ProxySetting::factory()->enabled()->create();
        ProxyUpstream::factory()->create(['upstream_url' => 'https://repo.packagist.org']);

        Http::fake([
            'repo.packagist.org/p2/vendor/unknown.json' => Http::response(
                ['error' => 'Not found'],
                404,
            ),
        ]);

        $response = $this->getJson('/proxy/p2/vendor/unknown.json');

        $response->assertNotFound();
    }

    public function test_proxy_dist_fetches_and_caches(): void
    {
        ProxySetting::factory()->enabled()->create();

        $fakeZip = 'PK-fake-zip-content';
        $originalUrl = 'https://api.github.com/repos/Seldaek/monolog/zipball/abc123';
        $encoded = rtrim(base64_encode($originalUrl), '=');

        Http::fake([
            'api.github.com/*' => Http::response($fakeZip),
        ]);

        $response = $this->get("/proxy/dists/{$encoded}");

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/zip');

        // Second request should serve from cache without hitting upstream
        Http::fake([
            'api.github.com/*' => Http::response('should-not-reach', 500),
        ]);

        $response = $this->get("/proxy/dists/{$encoded}");

        $response->assertOk();
    }

    public function test_proxy_serves_cached_metadata_when_offline(): void
    {
        ProxySetting::factory()->enabled()->create();
        $upstream = ProxyUpstream::factory()->create(['upstream_url' => 'https://repo.packagist.org']);

        // First request: populate cache
        Http::fake([
            'repo.packagist.org/packages.json' => Http::response([
                'metadata-url' => '/p2/%package%.json',
                'available-packages' => ['cached/package'],
            ]),
        ]);

        $this->getJson('/proxy/packages.json')->assertOk();

        // Set TTL to 0 to force cache expiry
        ProxySetting::query()->update(['metadata_cache_ttl' => 0]);

        Http::fake([
            'repo.packagist.org/*' => Http::response('error', 500),
        ]);

        sleep(1);

        $response = $this->getJson('/proxy/packages.json');

        $response->assertOk()
            ->assertJsonFragment(['available-packages' => ['cached/package']]);
    }

    public function test_proxy_auth_none_allows_anonymous(): void
    {
        ProxySetting::factory()->enabled()->create();
        ProxyUpstream::factory()->create(['upstream_url' => 'https://repo.packagist.org']);

        Http::fake([
            'repo.packagist.org/packages.json' => Http::response([
                'metadata-url' => '/p2/%package%.json',
                'available-packages' => [],
            ]),
        ]);

        $response = $this->getJson('/proxy/packages.json');

        $response->assertOk();
    }

    public function test_proxy_auth_basic_returns_401_without_credentials(): void
    {
        ProxySetting::factory()->enabled()->basicAuth('user', 'pass')->create();

        $response = $this->getJson('/proxy/packages.json');

        $response->assertUnauthorized();
    }

    public function test_proxy_auth_basic_succeeds_with_correct_credentials(): void
    {
        ProxySetting::factory()->enabled()->basicAuth('user', 'pass')->create();
        ProxyUpstream::factory()->create(['upstream_url' => 'https://repo.packagist.org']);

        Http::fake([
            'repo.packagist.org/packages.json' => Http::response([
                'metadata-url' => '/p2/%package%.json',
                'available-packages' => [],
            ]),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Basic '.base64_encode('user:pass'),
        ])->getJson('/proxy/packages.json');

        $response->assertOk();
    }

    public function test_proxy_auth_basic_fails_with_wrong_credentials(): void
    {
        ProxySetting::factory()->enabled()->basicAuth('user', 'pass')->create();

        $response = $this->withHeaders([
            'Authorization' => 'Basic '.base64_encode('user:wrong'),
        ])->getJson('/proxy/packages.json');

        $response->assertUnauthorized();
    }

    public function test_proxy_auth_token_returns_401_without_token(): void
    {
        ProxySetting::factory()->enabled()->tokenAuth('secret-token')->create();

        $response = $this->getJson('/proxy/packages.json');

        $response->assertUnauthorized();
    }

    public function test_proxy_auth_token_succeeds_with_bearer_token(): void
    {
        ProxySetting::factory()->enabled()->tokenAuth('secret-token')->create();
        ProxyUpstream::factory()->create(['upstream_url' => 'https://repo.packagist.org']);

        Http::fake([
            'repo.packagist.org/packages.json' => Http::response([
                'metadata-url' => '/p2/%package%.json',
                'available-packages' => [],
            ]),
        ]);

        $response = $this->withToken('secret-token')
            ->getJson('/proxy/packages.json');

        $response->assertOk();
    }

    public function test_proxy_auth_token_succeeds_with_query_token(): void
    {
        ProxySetting::factory()->enabled()->tokenAuth('secret-token')->create();
        ProxyUpstream::factory()->create(['upstream_url' => 'https://repo.packagist.org']);

        Http::fake([
            'repo.packagist.org/packages.json' => Http::response([
                'metadata-url' => '/p2/%package%.json',
                'available-packages' => [],
            ]),
        ]);

        $response = $this->getJson('/proxy/packages.json?token=secret-token');

        $response->assertOk();
    }

    public function test_proxy_dist_returns_404_for_invalid_url(): void
    {
        ProxySetting::factory()->enabled()->create();

        $response = $this->get('/proxy/dists/not-valid-base64!!!');

        $response->assertNotFound();
    }

    public function test_proxy_returns_502_when_no_upstreams_configured(): void
    {
        ProxySetting::factory()->enabled()->create();

        $response = $this->getJson('/proxy/packages.json');

        $response->assertStatus(502);
    }

    protected function tearDown(): void
    {
        $cachePath = config('repho.proxy_cache_path');
        if (is_dir($cachePath)) {
            $this->removeDirectory($cachePath);
        }

        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }
}
