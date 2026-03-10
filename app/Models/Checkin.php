<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Checkin extends Model
{
    protected $fillable = [
        'user_id', 'beer_id', 'venue_id', 'rating', 'notes', 'serving_type', 'location', 'untappd_id', 'created_at', 'updated_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function beer(): BelongsTo
    {
        return $this->belongsTo(Beer::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(CheckinPhoto::class);
    }

    public function companions(): BelongsToMany
    {
        return $this->belongsToMany(Companion::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
