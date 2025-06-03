<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teams extends Model
{
    protected $fillable = [
        'team_name',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->belongsToMany(
            User::class,
            'team_members',     
            'team_id',           
            'user_id'             
        )
        ->withPivot(['granted_by', 'role', 'status'])
        ->withTimestamps();
    }


    public function permissions()
    {
        return $this->hasMany(TeamPermission::class);
    }

}
