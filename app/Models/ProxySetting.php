<?php

namespace App\Models;

use App\Enums\RepositoryAuthType;
use Database\Factories\ProxySettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'enabled',
    'auth_type',
    'auth_username',
    'auth_password',
    'auth_token',
    'metadata_cache_ttl',
])]
class ProxySetting extends Model
{
    /** @use HasFactory<ProxySettingFactory> */
    use HasFactory;

    protected $table = 'proxy_settings';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'auth_type' => RepositoryAuthType::class,
            'auth_password' => 'encrypted',
            'auth_token' => 'encrypted',
            'metadata_cache_ttl' => 'integer',
        ];
    }

    public static function instance(): self
    {
        return self::firstOrCreate([], [
            'enabled' => false,
            'auth_type' => RepositoryAuthType::None,
            'metadata_cache_ttl' => 3600,
        ]);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
