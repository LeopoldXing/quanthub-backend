<?php

namespace App\Services;

use App\Models\Like;

class LikingService
{
    /**
     * @param $articleId
     * @return mixed
     */
    public function countArticleLikes($articleId): mixed {
        return Like::where('article_id', $articleId)->count();
    }

    /**
     * @param $articleId
     * @param $userId
     * @return bool
     */
    public function isThisArticleLiked($articleId, $userId): bool {
        $like = Like::where([
            ['article_id', '=', $articleId],
            ['user_id', '=', $userId],
        ])->first();

        return ($like && $like->type === 1);
    }

    /**
     * @param $articleId
     * @param $userId
     * @return bool
     */
    public function isThisArticleDisLiked($articleId, $userId): bool {
        $like = Like::where([
            ['article_id', '=', $articleId],
            ['user_id', '=', $userId],
        ])->first();

        return ($like && $like->type === 0);
    }

}
