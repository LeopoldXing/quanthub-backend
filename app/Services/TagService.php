<?php

namespace App\Services;

use App\Models\LinkTagArticle;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TagService
{
    /**
     * get designated number of random tags
     *
     * @param $number
     * @return Collection
     */
    public function getRandomTags($number): Collection {
        $tagCount = Tag::count();
        $res = $tagCount <= $number ? Tag::all() : Tag::inRandomOrder()->take($number)->get();
        return $res;
    }

    /**
     * create multiple tags if not exist
     *
     * @param $tagList
     * @param $operator_id
     * @return array
     */
    public function saveTagList($tagList, $operator_id): array {
        $res = [];
        if (!empty($tagList)) {
            foreach ($tagList as $tag) {
                $tag = Tag::firstOrCreate(
                    ['name' => $tag],
                    ['created_by' => $operator_id, 'updated_by' => $operator_id]
                );
                $res[] = $tag;
            }
        }
        return $res;
    }

    /**
     * create link between multiple tags and article
     *
     * @param $tagList
     * @param $articleId
     * @param $operator_id
     * @return array
     */
    public function connectTagsToArticle($tagList, $articleId, $operator_id): array {
        $savedTags = $this->saveTagList($tagList, $operator_id);
        foreach ($savedTags as $savedTag) {
            LinkTagArticle::create([
                'article_id' => $articleId,
                'tag_id' => $savedTag->id,
                'created_by' => $operator_id,
                'updated_by' => $operator_id
            ]);
        }
        return $savedTags;
    }

    /**
     * destroy the connection between tags and designated article
     *
     * @param $articleId
     * @return void
     */
    public function disconnectTagsFromArticle($articleId): void {
        LinkTagArticle::where('article_id', $articleId)->delete();
    }

    public function getMyTags(int $number, $userId): \Illuminate\Support\Collection {
        $myTags = DB::table('tags as t')
            ->distinct()
            ->join('link_tag_articles as lta', 'lta.tag_id', '=', 't.id')
            ->whereIn('lta.article_id', function ($query) use ($userId) {
                $query->select('id')
                    ->distinct()
                    ->from('articles')
                    ->where('author_id', $userId);
            })
            ->get();


        Log::info("我的标签: ", ['mytags' => $myTags]);

        return $myTags;
    }

}
