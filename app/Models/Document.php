<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'file_name',
        'subject',
        'purpose',
        'department',
        'doc_stat',
        'doc_type',
        'route_id', 
    ];

    // Relationship to the status model
    public function status()
    {
        return $this->belongsTo(Status::class, 'doc_stat', 'id'); // Assuming doc_stat is the foreign key
    }

    // Relationship to route_documents
    public function routeDocuments()
    {
        return $this->hasMany(RouteDocument::class, 'route_id', 'route_id'); // Link using route_id field
         return $this->hasMany(RouteDocument::class, 'document_id', 'id'); // Assuming RouteDocument has document_id
    }
    public function logs()
{
    return $this->hasMany(Log::class, 'doc_id'); // Adjust if the foreign key is different
    return $this->hasMany(Log::class, 'route_id', 'route_id');
}
public function routingSlip()
{
    return $this->hasOne(RoutingSlip::class, 'rslip_id', 'route_id');
}
}