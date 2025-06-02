<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Status extends Model
{
    use HasFactory;

    protected $fillable = [
        'status', // The name of the status
    ];

    public function documents()
    {
        return $this->hasMany(Document::class, 'doc_stat', 'id'); // Define inverse relationship
    }
    
}