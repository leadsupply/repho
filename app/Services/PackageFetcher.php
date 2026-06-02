<?php

namespace App\Services;

use App\Models\Package;

interface PackageFetcher
{
    /**
     * Fetch all versions (tags + dev branches) from the remote repository.
     *
     * @return array<int, array{version: string, version_normalized: string, reference: string, composer_json: array<string, mixed>, released_at: string|null}>
     */
    public function fetchVersions(Package $package): array;

    /**
     * Get the raw dist archive content for a specific version.
     *
     * @return resource|string|null
     */
    public function getDistArchive(Package $package, string $reference): mixed;
}
