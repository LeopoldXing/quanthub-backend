<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\LinkTagArticle;
use App\Models\Tag;
use App\Services\ArticleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArticleController extends Controller
{
    protected $articleService;

    public function __construct(ArticleService $articleService) {
        $this->articleService = $articleService;
    }

    /**
     * search article based on conditions
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchArticles(Request $request) {
        $validated = $request->validate([
            'keyword' => 'nullable|string|max:255',
            'categoryList' => 'nullable|array',
            'categoryList.*' => 'string|max:100',
            'tagList' => 'nullable|array',
            'tagList.*' => 'string|max:100',
            'sortStrategy' => 'nullable|in:publish_date,update_date,recommended',
            'sortDirection' => 'nullable|in:desc,asc,none',
            'contentType' => 'required|in:article,announcement'
        ]);

        $res = $this->articleService->searchArticles($validated);
        return response()->json($res, 200);
    }

    public function publishArticle(Request $request) {
        // 验证请求数据
        $validated = $request->validate([
            'authorId' => 'required|exists:quanthub_users,id',
            'title' => 'required|string|max:255',
            'subTitle' => 'nullable|string|max:255',
            'contentHtml' => 'required|string',
            'contentText' => 'required|string',
            'coverImageLink' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:100',
            'attachmentLink' => 'nullable|string|max:255'
        ]);
        $validated['status'] = 'published';

        $res = $this->articleService->createArticle($validated);

        return response()->json($res['response'], $res['status']);
    }

    public function updateArticle(Request $request) {
        $validated = $request->validate([
            'articleId' => 'required|exists:articles,id',
            'authorId' => 'required|exists:quanthub_users,id',
            'title' => 'required|string|max:255',
            'subTitle' => 'nullable|string|max:255',
            'contentHtml' => 'required|string',
            'contentText' => 'required|string',
            'coverImageLink' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:100',
            'attachmentLink' => 'nullable|string|max:255'
        ]);

        try {
            $res = $this->articleService->updateArticle($validated);
            response()->json($res, 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => 'Failed to update article', 'message' => $e->getMessage()], 500);
        }

        return response()->json($this->articleService->updateArticle($validated), 200);
    }

    public function getArticle($id) {
        try {
            return response()->json($this->articleService->getArticleById($id), 200);
        } catch (\Exception $e) {
            Log::error('Failed to get article', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to get article', 'message' => $e->getMessage()], 500);
        }
    }

    public function createDraft(Request $request) {
        // 验证请求数据
        $validated = $request->validate([
            'authorId' => 'required|exists:quanthub_users,id',
            'title' => 'required|string|max:255',
            'subTitle' => 'nullable|string|max:255',
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

    public function deleteArticle($id) {
        $this->articleService->deleteArticle($id);
    }

}
