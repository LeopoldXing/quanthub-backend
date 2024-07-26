<?php

namespace App\Http\Controllers;

use App\Services\TagService;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    protected TagService $tagService;

    public function __construct(TagService $tagService) {
        $this->tagService = $tagService;
    }

    public function shuffleTags($number): JsonResponse {
        $number = (int)$number;
        if ($number < 30) {
            $number = 30;
        }
        $res = $this->tagService->getRandomTags($number)->map(function ($item) {
            return $item->name;
        });
        return response()->json($res, 200);
    }

    public function getMyTags($number, $userId): JsonResponse {
        $number = (int)$number;

        $res = $this->tagService->getMyTags($number, $userId)
            ->map(function ($item) {
                return $item->name;
            })
            ->unique()
            ->shuffle()
            ->values();


        return response()->json($res, 200);
    }
}
