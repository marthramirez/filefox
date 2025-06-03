<?php

namespace App\Models;
use App\Models\Teams;

use Illuminate\Database\Eloquent\Model;

class TeamPermission extends Model
{
    protected $fillable = [
        'team_id',
        'folder_id',
        'document_id',
        'permission',
        'granted_by',
    ];

    public function team()
    {
        return $this->belongsTo(Teams::class);
    }

    public function document()
    {
        return $this->belongsTo(DocumentS::class, 'document_id');
    }

    public function folder()
    {
        return $this->belongsTo(Folders::class, 'folder_id');
    }


}

