<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tags extends Model
{
    protected $fillable = [
        'tag_name',
        'document_id',
    ];

    public function documents()
    {
        return $this->belongsToMany(Documents::class, 'document_tags', 'tag_id', 'document_id');
    }
}
