<?php

namespace App\Models;

use App\Enums\PackageType;
use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'repository_url', 'type', 'credential_id', 'download_dists', 'description', 'last_synced_at', 'sync_error', 'is_syncing', 'sync_progress'])]
class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PackageType::class,
            'download_dists' => 'boolean',
            'is_syncing' => 'boolean',
            'sync_progress' => 'integer',
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Version, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(Version::class);
    }

    /**
     * @return BelongsTo<Credential, $this>
     */
    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class);
    }

    /**
     * @return HasMany<SecurityAdvisory, $this>
     */
    public function securityAdvisories(): HasMany
    {
        return $this->hasMany(SecurityAdvisory::class);
    }

    /**
     * @return BelongsToMany<Repository, $this>
     */
    public function repositories(): BelongsToMany
    {
        return $this->belongsToMany(Repository::class);
    }

    public function vendor(): string
    {
        $vendor = explode('/', $this->name)[0];

        return basename($vendor);
    }

    public function shortName(): string
    {
        $name = explode('/', $this->name)[1] ?? $this->name;

        return basename($name);
    }
}
