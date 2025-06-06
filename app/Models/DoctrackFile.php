<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctrackFile extends Model
{
    use HasFactory;

    protected $table = 'doctrack_file';

    // Define which fields are mass assignable
    protected $fillable = [
        'docslip_id',
        'file',
    ];
}
