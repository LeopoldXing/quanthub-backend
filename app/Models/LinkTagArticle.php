<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LinkTagArticle extends Model
{
    use HasFactory;

    protected $table = 'link_tag_articles';

    protected $fillable = [
        'article_id', 'tag_id', 'created_by', 'updated_by'
    ];

    public function article() {
        return $this->belongsTo(Article::class);
    }

    public function tag() {
        return $this->belongsTo(Tag::class);
    }
}
