<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteDocument extends Model
{
    use HasFactory;

    protected $table = 'route_documents';

    protected $fillable = [
        'route_id',
        'destination_1',
        'destination_2',
        'destination_3',
        'destination_4',
        'destination_5',
        'destination_6',
        'destination_7',
        'destination_8',
        'destination_9',
        'destination_10',
    ];

     public function document()
    {
        return $this->belongsTo(Document::class, 'route_id', 'route_id'); // Links back to Document using route_id
    }
}
