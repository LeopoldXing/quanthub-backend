<?php

namespace App\Http\Controllers;

use App\Services\DraftService;
use Illuminate\Http\Request;

class DraftController
{
    protected DraftService $draftService;

    public function __construct(DraftService $draftService) {
        $this->draftService = $draftService;
    }

    public function createDraft(Request $request) {
        $validated = $request->validate([
            'id' => 'nullable',
            'authorId' => 'required|exists:quanthub_users,id',
            'title' => 'required|string|max:255',
            'subTitle' => 'nullable|string|max:255',
            'type' => 'required|string',
            'contentHtml' => 'required|string',
            'coverImageLink' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:100',
            'attachmentLink' => 'nullable|string|max:255',
            'referenceId' => 'nullable|string|max:255'
        ]);
        $validated['status'] = 'draft';

        $res = $this->draftService->saveDraft($validated);

        return response()->json($res['data'], $res['status']);
    }

    public function getDraftByArticleId($articleId) {
        $res = $this->draftService->getDraftByArticleId($articleId);
        return response()->json($res['data'], $res['status']);
    }

    public function updateDraft(Request $request, $id) {}
}
