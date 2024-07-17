<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'author_id', 'title', 'sub_title', 'content', 'category_id', 'rate', 'status', 'cover_image_link', 'publish_date', 'attachment_link', 'created_by', 'updated_by'
    ];

    public function author() {
        return $this->belongsTo(QuanthubUser::class, 'author_id');
    }

    public function category() {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function tags() {
        return $this->belongsToMany(Tag::class, 'link_tag_articles', 'article_id', 'tag_id');
    }
}
