<?php

namespace App\Models;

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
        'assigned_to',
        'assign_com',
        'document',
        'esig',
        'received_name',
        'route_status',
        'date_received',
    ];
}