<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentVersions extends Model
{
     protected $fillable = [
        'document_id',
        'version_number',
        'file_name',
        'file_path',
    ];
}
