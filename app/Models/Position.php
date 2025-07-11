<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory;

     protected $table = 'logs_history'; // Define table name

    protected $fillable = [
        
        'position',
      
    ];
}
