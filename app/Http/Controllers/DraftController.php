<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DraftController
{
    public function createDraft(Request $request) {
        // 验证请求数据
        $validated = $request->validate([
            'authorId' => 'required|exists:quanthub_users,id',
            'title' => 'required|string|max:255',
            'subTitle' => 'nullable|string|max:255',
            'type' => 'required|in:draft',
            'contentHtml' => 'required|string',
            'coverImageLink' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:100',
            'attachmentLink' => 'nullable|string|max:255'
        ]);
        $validated['status'] = 'draft';

        $res = $this->articleService->createArticle($validated);

        return response()->json($res['response'], $res['status']);
    }
}
