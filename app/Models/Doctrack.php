<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'is_archived',
        'archived_at',
        'archived_by',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
        'ctrl_no' => 'integer',
    ];

    /**
     * Archived transactions are kept in the database for the Archived History
     * Logbook, but they must not appear on the normal operational pages.
     * Use withoutGlobalScope('not_archived') to read them explicitly.
     */
    protected static function booted()
    {
        static::addGlobalScope('not_archived', function (Builder $builder) {
            $table = $builder->getModel()->getTable();

            $builder->where(function ($query) use ($table) {
                $query->where($table . '.is_archived', 0)
                    ->orWhereNull($table . '.is_archived');
            });
        });
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->withoutGlobalScope('not_archived')
            ->where($query->getModel()->getTable() . '.is_archived', 1);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where($query->getModel()->getTable() . '.is_archived', 0);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'update_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function archivedBy()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function doctrackFile()
    {
        return $this->hasOne(DoctrackFile::class, 'docslip_id', 'docslip_id');
    }

    /**
     * ✅ All files attached to the transaction (multiple files are supported)
     */
    public function doctrackFiles()
    {
        return $this->hasMany(DoctrackFile::class, 'docslip_id', 'docslip_id');
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
