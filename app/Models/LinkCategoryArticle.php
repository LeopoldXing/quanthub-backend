<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LinkCategoryArticle extends Model
{
    use HasFactory;

    protected $table = 'link_category_article';

    protected $fillable = [
        'category_id', 'article_id', 'created_by', 'updated_by'
    ];

    public function category() {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function article() {
        return $this->belongsTo(Article::class, 'article_id');
    }
}
