<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamDocuments extends Model
{
    protected $fillable = [
        'team_id',
        'document_id',
        'granted_by',
        'permission',
    ];

    public function team()
    {
        return $this->belongsTo(Teams::class, 'team_id');
    }

    public function document()
    {
        return $this->belongsTo(Documents::class, 'document_id');
    }
}
