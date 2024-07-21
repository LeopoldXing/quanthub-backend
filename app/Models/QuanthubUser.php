<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuanthubUser extends Model
{
    protected $table = 'quanthub_users';
    protected $fillable = [
        'auth0_id', 'username', 'password', 'email', 'phone_number', 'role', 'avatar_link',
        'created_by', 'updated_by'
    ];

    public function articles() {
        return $this->hasMany(Article::class, 'author_id');
    }

    public function comments() {
        return $this->hasMany(Comment::class, 'user_id');
    }

    public function likes() {
        return $this->hasMany(Like::class, 'user_id');
    }
}
