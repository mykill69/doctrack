<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogsTracking extends Model
{
    use HasFactory;
    protected $table = 'logs_tracking';

    // Define which fields are mass assignable
    protected $fillable = [
        'docslip_id',
        'user_id',
        'update_by',
        'doc_title',
        'file_logs',
        'logs_status',
        'comments',
        'viewed_status',
        'viewed_at',
    ];
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'update_by');
    }

    // For the original user related to the document
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function doctrackFile()
{
    return $this->hasOne(DoctrackFile::class, 'docslip_id', 'docslip_id'); // Adjust foreign key if different
}
}
