<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

     protected $table = 'groups';

     protected $fillable = ['group_name']; // allows mass assignment

public function users()
{
    return $this->belongsToMany(User::class, 'group_user', 'group_id', 'user_id');
}

}

