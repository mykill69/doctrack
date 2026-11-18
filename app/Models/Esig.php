<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Esig extends Model
{
    use HasFactory;

    protected $table = 'esig';

    // Define which fields are mass assignable
    protected $fillable = [
        'user_id',
        'esig_file',
        'is_encrypted',
        'mime_type',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    /**
     * Legacy signature uploaded before encryption was introduced.
     * It is no longer used and the user has to upload it again.
     */
    public function needsReupload(): bool
    {
        return !$this->is_encrypted;
    }
}
