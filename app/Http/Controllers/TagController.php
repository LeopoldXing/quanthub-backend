<?php

namespace App\Http\Controllers;

use App\services\TagService;

class TagController extends Controller
{
    protected $tagService;

    public function __construct(TagService $tagService) {
        $this->tagService = $tagService;
    }

    public function shuffleTags($number) {
        $number = (int)$number;
        if ($number < 30) {
            $number = 30;
        }
        $res = $this->tagService->getRandomTags($number);
        return response()->json($res, 200);
    }
}
