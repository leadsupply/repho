<?php

namespace App\Models;

use App\Enums\RepositoryAuthType;
use Database\Factories\RepositoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'auth_type', 'auth_username', 'auth_password', 'auth_token'])]
class Repository extends Model
{
    /** @use HasFactory<RepositoryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'auth_type' => RepositoryAuthType::class,
            'auth_password' => 'encrypted',
            'auth_token' => 'encrypted',
        ];
    }

    /**
     * @return BelongsToMany<Package, $this>
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class);
    }

    public function isPublic(): bool
    {
        return $this->auth_type === RepositoryAuthType::None;
    }
}
