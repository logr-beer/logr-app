<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    protected $table = 'inventory';

    protected $fillable = [
        'beer_id', 'user_id', 'quantity', 'storage_location', 'purchase_location', 'is_gift', 'date_acquired', 'notes',
    ];

    protected $casts = [
        'date_acquired' => 'date',
        'is_gift' => 'boolean',
    ];

    public function beer(): BelongsTo
    {
        return $this->belongsTo(Beer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
