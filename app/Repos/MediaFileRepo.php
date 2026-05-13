<?php

declare(strict_types=1);

namespace App\Repos;

use App\Models\MediaFile;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class MediaFileRepo
{
    public function findById(int $id): ?MediaFile
    {
        return MediaFile::query()->find($id);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function findOrFail(string|int $id): MediaFile
    {
        return MediaFile::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): MediaFile
    {
        return MediaFile::query()->create($attributes);
    }
}
