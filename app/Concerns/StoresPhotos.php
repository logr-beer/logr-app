<?php

namespace App\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;

trait StoresPhotos
{
    /**
     * Validation rule for a single uploaded photo. Keep in sync with photoExtension().
     */
    protected function photoRule(): string
    {
        return 'image|mimes:jpg,jpeg,png,gif,webp|max:10240';
    }

    /**
     * Store a validated photo on the public disk under a random name with an
     * extension picked from an allow-list, never one derived from the upload.
     */
    protected function storePhoto(UploadedFile $photo, string $directory): string
    {
        return $photo->storeAs($directory, Str::random(40).'.'.$this->photoExtension($photo), 'public');
    }

    private function photoExtension(UploadedFile $photo): string
    {
        return match ($photo->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => throw new InvalidArgumentException('Unsupported photo type.'),
        };
    }
}
