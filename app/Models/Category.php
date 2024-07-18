<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'created_by', 'updated_by'];

    public function articles() {
        return $this->hasMany(Article::class, 'category_id');
    }
}
