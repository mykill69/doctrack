<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        'viewed_status',
        'viewed_at',
        'created_at'
    ];   

    /**
     * Routing history belongs to a transaction (routing_slip.rslip_id or
     * doctrack_slip.docslip_id). Once that transaction is archived, its log
     * rows must no longer be returned by the normal operational pages.
     */
    protected static function booted()
    {
        static::addGlobalScope('exclude_archived_routes', function (Builder $builder) {
            $builder->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('routing_slip')
                    ->whereColumn('routing_slip.rslip_id', 'logs.route_id')
                    ->where('routing_slip.is_archived', 1);
            })->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('doctrack_slip')
                    ->whereColumn('doctrack_slip.docslip_id', 'logs.route_id')
                    ->where('doctrack_slip.is_archived', 1);
            });
        });
    }

    public function scopeExcludeArchivedRoutes(Builder $query): Builder
    {
        return $query;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Log model
    public function document()
    {
        return $this->belongsTo(Document::class, 'route_id', 'route_id'); // Assuming 'route_id' is the foreign key
    }

    public function routingSlip()
    {
        return $this->belongsTo(RoutingSlip::class, 'route_id', 'rslip_id');
    }

    public function newUser()
    {
        return $this->belongsTo(User::class, 'new_user');
    }
}
