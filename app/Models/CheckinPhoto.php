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

    public function checkin(): BelongsTo
    {
        return $this->belongsTo(Checkin::class);
    }
}
