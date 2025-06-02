<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'doc_id',
        'route_id', 
        'action', 
        'status_update',
        'prev_file', 
        'new_file',
        'new_destination',
        'comments',
        'assigned_to',
        'created_at',
    ];   

     public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}
// Log model
public function document()
{
    return $this->belongsTo(Document::class, 'route_id', 'route_id'); // Assuming 'route_id' is the foreign key
}
}
