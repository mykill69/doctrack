<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignLogs extends Model
{
    use HasFactory;

    protected $table = 'assign_logs';

    // Define which fields are mass assignable
    protected $fillable = [
        'new_user',
        'doc_id',
        'route_id',
        'assn_code',
        'assigned_to',
    ];
}
