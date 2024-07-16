<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'created_by', 'updated_by'
    ];

    public function articles() {
        return $this->belongsToMany(Article::class, 'link_tag_article', 'tag_id', 'article_id');
    }
}
