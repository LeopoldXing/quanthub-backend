<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'author_id', 'title', 'sub_title', 'content', 'rate', 'status', 'publish_date', 'created_by', 'updated_by'
    ];

    public function author() {
        return $this->belongsTo(QuanthubUser::class, 'author_id');
    }

    public function comments() {
        return $this->hasMany(Comment::class, 'article_id');
    }

    public function categories() {
        return $this->belongsToMany(Category::class, 'link_category_article', 'article_id', 'category_id');
    }

    public function tags() {
        return $this->belongsToMany(Tag::class, 'link_tag_article', 'article_id', 'tag_id');
    }

    public function likes() {
        return $this->hasMany(Like::class, 'article_id');
    }
}
