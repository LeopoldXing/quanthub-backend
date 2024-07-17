<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'content', 'user_id', 'publish_datetime', 'article_id', 'status', 'created_by', 'updated_by'
    ];

    public function user() {
        return $this->belongsTo(QuanthubUser::class);
    }

    public function article() {
        return $this->belongsTo(Article::class);
    }
}
