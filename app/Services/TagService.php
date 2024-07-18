<?php

namespace App\Services;

use App\Models\LinkTagArticle;
use App\Models\Tag;

class TagService
{
    /**
     * get designated number of random tags
     *
     * @param $number
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRandomTags($number) {
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
    public function saveTagList($tagList, $operator_id) {
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
    public function connectTagsToArticle($tagList, $articleId, $operator_id) {
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
    public function disconnectTagsFromArticle($articleId) {
        LinkTagArticle::where('article_id', $articleId)->delete();
    }
}
