<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctrack extends Model
{
    use HasFactory;
    protected $table = 'doctrack_slip';

    // Define which fields are mass assignable
    protected $fillable = [
        'docslip_id',
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