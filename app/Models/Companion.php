<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Companion extends Model
{
    protected $fillable = ['name', 'avatar_path'];

    public function checkins(): BelongsToMany
    {
        return $this->belongsToMany(Checkin::class);
    }
}
