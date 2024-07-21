<?php

namespace App\Services;

use App\Models\Like;

class LikingService
{
    public function countArticleLikes($articleId) {
        $count = Like::where('article_id', $articleId)->count();
        return $count;
    }

    public function isThisArticleLiked($articleId, $userId) {
        return Like::where([
            ['article_id', '=', $articleId],
            ['user_id', '=', $userId],
        ])->exists();
    }
}
