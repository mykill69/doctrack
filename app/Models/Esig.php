<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Esig extends Model
{
    use HasFactory;

     protected $table = 'esig';

    // Define which fields are mass assignable
    protected $fillable = [
        'user_id',
        'esig_file',
    ];
}
