<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Doctrack extends Model
{
    use HasFactory;
    protected $table = 'doctrack_slip';

    protected $fillable = [
        'docslip_id',
        'ctrl_no',
        'user_id',
        'update_by',
        'doc_type',
        'doc_title',
        'user_name',
        'doctrack_stat',
        'comments',
    ];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'update_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function doctrackFile()
    {
        return $this->hasOne(DoctrackFile::class, 'docslip_id', 'docslip_id');
    }

    public function routingSlips()
    {
        return $this->hasMany(RoutingSlip::class, 'rslip_id', 'docslip_id');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'new_destination');
    }

    // Add this relationship for logs
    public function logsTracking(): HasMany
    {
        return $this->hasMany(LogsTracking::class, 'docslip_id', 'docslip_id');
    }
}