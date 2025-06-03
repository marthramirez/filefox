<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Folders extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'folder_name',
        'parent_id',
        'user_id',
    ];

    public function documents()
    {
        return $this->hasMany(Documents::class, 'folder_id', 'id');
    }

    public function parent()
    {
        return $this->belongsTo(Folders::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Folders::class, 'parent_id');
    }

    public function permissions()
    {
        return $this->morphMany(Permission::class, 'permissionable');
    }
    public function folder()
    {
        return $this->belongsTo(Folders::class, 'folder_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }


}

