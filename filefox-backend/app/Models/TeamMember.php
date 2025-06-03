<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    protected $table = 'team_members';
    protected $fillable = [
        'user_id',
        'team_id',
        'granted_by',
        'role',
        'status'
    ];

     public function team()
    {
        return $this->belongsTo(Teams::class, 'team_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}

