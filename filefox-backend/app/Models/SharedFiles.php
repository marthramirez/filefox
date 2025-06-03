<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SharedItem extends Model
{
    protected $fillable = [
        'item_type',
        'item_id',
        'shared_with',
        'shared_by',
        'permission',
    ];

    public function sharedWith(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with');
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }
    
    public function item(): MorphTo
    {
        return $this->morphTo(null, 'item_type', 'item_id');
    }
}
