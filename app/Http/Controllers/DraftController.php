<?php

namespace App\Http\Controllers;

use App\Services\DraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DraftController
{
    protected DraftService $draftService;

    public function __construct(DraftService $draftService) {
        $this->draftService = $draftService;
    }

    public function createDraft(Request $request): JsonResponse {
        Log::info("接受的草稿数据：", ['request' => $request]);
        $validated = $request->validate([
            'id' => 'nullable',
            'authorId' => 'required|exists:quanthub_users,id',
            'title' => 'nullable|string|max:255',
            'subTitle' => 'nullable|string|max:255',
            'type' => 'required|string',
            'contentHtml' => 'required|string',
            'contentText' => 'required|string',
            'coverImageLink' => 'nullable|string|max:255',
            'category' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:100',
            'attachmentLink' => 'nullable',
            'referenceId' => 'nullable'
        ]);
        $validated['status'] = 'draft';

        Log::info("准备创建/更新草稿：", ['data' => $validated]);

        $res = $this->draftService->saveDraft($validated);

        return response()->json($res['data'], $res['status']);
    }

    public function getDraftByArticleId($articleId): JsonResponse {
        $res = $this->draftService->getDraftByArticleId($articleId);
        return response()->json($res['data'], $res['status']);
    }

    public function getDraftById($draftId): JsonResponse {
        $res = $this->draftService->getDraftById($draftId);
        return response()->json($this->draftService->constructDraftResponse($res), 200);
    }
}
