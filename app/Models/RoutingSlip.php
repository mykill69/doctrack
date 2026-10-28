<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoutingSlip extends Model
{
    use HasFactory;

    protected $table = 'routing_slip'; // Define your table name if it’s not pluralized

    // Specify the fields that are mass assignable
    protected $fillable = [
        'rslip_id',
        'op_ctrl',
        'user_id',
        'pres_dept',
        'source',
        'subject',
        'trans_remarks',
        'other_remarks',
        'r_destination',
        'routed_to',
        'assigned_to',
        'assign_com',
        'document',
        'esig',
        'received_name',
        'route_status',
        'date_received',
        'is_archived',
        'archived_at',
        'archived_by',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
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

    public function archivedBy()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    /**
     * Files uploaded for this exact routing slip version.
     */
    public function files()
    {
        return $this->hasMany(RoutingSlipFile::class, 'routing_slip_id');
    }

    /**
     * Every file uploaded for the routing slip transaction (all versions).
     */
    public function allFiles()
    {
        return $this->hasMany(RoutingSlipFile::class, 'rslip_id', 'rslip_id');
    }
}
