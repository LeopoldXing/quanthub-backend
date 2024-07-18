<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = ['name', 'created_by', 'updated_by'];

    public function articles() {
        return $this->belongsToMany(Article::class, 'link_tag_articles', 'tag_id', 'article_id')
            ->using(LinkTagArticle::class)
            ->withPivot('created_by', 'updated_by');
    }

}
