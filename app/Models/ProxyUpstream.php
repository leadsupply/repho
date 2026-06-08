<?php

namespace App\Models;

use App\Enums\RepositoryAuthType;
use Database\Factories\ProxyUpstreamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'enabled',
    'name',
    'upstream_url',
    'auth_type',
    'auth_username',
    'auth_password',
    'auth_token',
    'sort_order',
])]
class ProxyUpstream extends Model
{
    /** @use HasFactory<ProxyUpstreamFactory> */
    use HasFactory;

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
            'sort_order' => 'integer',
        ];
    }
}
