<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class LinkTagArticle extends Pivot
{
    protected $table = 'link_tag_articles';

    public $incrementing = true;  // Since you have a primary key `id` which auto-increments

    protected $fillable = ['article_id', 'tag_id', 'created_by', 'updated_by'];

    public function article() {
        return $this->belongsTo(Article::class, 'article_id');
    }

    public function tag() {
        return $this->belongsTo(Tag::class, 'tag_id');
    }
}
