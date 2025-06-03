<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLogs extends Model
{
     protected $fillable = [
        'user_id',
        'document_id',
        'action',
    ];
}
