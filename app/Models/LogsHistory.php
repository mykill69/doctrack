<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogsHistory extends Model
{
    use HasFactory;

    protected $table = 'logs_history'; // Define table name

    protected $fillable = [
        
        'doc_id',
        'action',
        'status_update'
    ];

    /**
     * Relationship with Logs table
     */
    public function log()
    {
        return $this->belongsTo(Log::class, 'log_id');
    }

    /**
     * Relationship with Documents table
     */
    public function document()
    {
        return $this->belongsTo(Document::class, 'doc_id');
    }
}