<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArchiveLog extends Model
{
    use HasFactory;

    public const TYPE_ROUTING_SLIP = 'routing_slip';
    public const TYPE_DOCUMENT_TRACKING = 'document_tracking';
    public const TYPE_BOTH = 'both';

    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_FAILED = 'failed';

    protected $table = 'archive_logs';

    protected $fillable = [
        'archive_year',
        'transaction_type',
        'records_count',
        'archived_by',
        'archived_at',
        'status',
        'message',
    ];

    protected $casts = [
        'archive_year' => 'integer',
        'records_count' => 'integer',
        'archived_at' => 'datetime',
    ];

    public function archivedBy()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function getTransactionTypeLabelAttribute(): string
    {
        return match ($this->transaction_type) {
            self::TYPE_ROUTING_SLIP => 'Routing Slip',
            self::TYPE_DOCUMENT_TRACKING => 'Document Tracking',
            self::TYPE_BOTH => 'Both',
            default => ucwords(str_replace('_', ' ', (string) $this->transaction_type)),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => '<span class="badge badge-success">Completed</span>',
            self::STATUS_SKIPPED => '<span class="badge badge-secondary">Skipped</span>',
            self::STATUS_FAILED => '<span class="badge badge-danger">Failed</span>',
            default => '<span class="badge badge-warning">Processing</span>',
        };
    }
}
