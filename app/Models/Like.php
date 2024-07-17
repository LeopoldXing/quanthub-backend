<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'article_id', 'created_by', 'updated_by'
    ];

    public function user() {
        return $this->belongsTo(QuanthubUser::class, 'user_id');
    }

    public function article() {
        return $this->belongsTo(Article::class, 'article_id');
    }
}
