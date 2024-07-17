<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class QuanthubUser extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'auth0Id', 'username', 'password', 'email', 'phone_number', 'role', 'avatarLink', 'created_by', 'updated_by'
    ];
}
