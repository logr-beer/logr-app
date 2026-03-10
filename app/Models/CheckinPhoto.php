<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckinPhoto extends Model
{
    protected $fillable = ['checkin_id', 'photo_path'];

    public function checkin(): BelongsTo
    {
        return $this->belongsTo(Checkin::class);
    }
}
