<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'author_id', 'title', 'sub_title', 'content', 'category_id', 'rate', 'status', 'type', 'is_announcement', 'draft_reference_id',
        'cover_image_link', 'publish_date', 'attachment_link', 'attachment_name', 'created_by', 'updated_by'
    ];

    public function author() {
        return $this->belongsTo(QuanthubUser::class, 'author_id');
    }

    public function category() {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function comments() {
        return $this->hasMany(Comment::class, 'article_id');
    }

    public function tags() {
        return $this->belongsToMany(Tag::class, 'link_tag_articles', 'article_id', 'tag_id')
            ->using(LinkTagArticle::class)
            ->withPivot('created_by', 'updated_by');
    }

    public function likes() {
        return $this->hasMany(Like::class, 'article_id');
    }
}
