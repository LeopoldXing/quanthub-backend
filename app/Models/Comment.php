<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = ['content', 'user_id', 'article_id', 'publish_datetime', 'status', 'created_by', 'updated_by'];

    public function user() {
        return $this->belongsTo(QuanthubUser::class, 'user_id');
    }

    public function article() {
        return $this->belongsTo(Article::class, 'article_id');
    }
}
