<?php

namespace App\Services;

use App\Models\Tag;

class TagService
{
    public function getRandomTags($number){
        $tagCount = Tag::count();
        $res = $tagCount <= $number ? Tag::all() : Tag::inRandomOrder()->take($number)->get();
        return $res;
    }
}
