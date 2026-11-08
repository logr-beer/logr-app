<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CheckinPhoto extends Model
{
    protected $fillable = ['checkin_id', 'photo_path'];

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return str_starts_with($this->photo_path, 'http') ? $this->photo_path : Storage::url($this->photo_path);
    }

    /**
     * Delete a stored photo file unless a beer or check-in photo still points at it
     * (RSS imports reuse a check-in photo as the beer photo).
     */
    public static function deleteFileIfUnused(?string $path): void
    {
        if (! $path || str_starts_with($path, 'http')) {
            return;
        }

        if (static::where('photo_path', $path)->exists() || Beer::where('photo_path', $path)->exists()) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    public function checkin(): BelongsTo
    {
        return $this->belongsTo(Checkin::class);
    }
}
