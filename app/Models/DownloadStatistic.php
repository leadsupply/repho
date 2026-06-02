<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

#[Fillable(['package_id', 'version_id', 'date', 'downloads'])]
class DownloadStatistic extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * @return BelongsTo<Version, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class);
    }

    public static function recordDownload(int $packageId, ?int $versionId = null): void
    {
        self::upsert(
            [
                [
                    'package_id' => $packageId,
                    'version_id' => $versionId,
                    'date' => now()->toDateString(),
                    'downloads' => 1,
                ],
            ],
            ['package_id', 'version_id', 'date'],
            ['downloads' => DB::raw('downloads + 1')],
        );
    }
}
