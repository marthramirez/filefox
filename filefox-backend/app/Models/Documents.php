<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Documents extends Model
{
    use SoftDeletes;
    
     protected $fillable = [
        'title',
        'description',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'is_archived',
        'user_id',
        'folder_id',
        'uploaded_by',
        'deleted_at',
    ];

    public function permissions()
    {
        return $this->morphMany(Permission::class, 'permissionable');
    }

    public function folder()
    {
        return $this->belongsTo(Folders::class);
    }
    
    public function owner()
    {
        return $this->belongsTo(User::class, 'uploaded_by'); 
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

}
