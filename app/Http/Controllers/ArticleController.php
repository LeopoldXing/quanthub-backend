<?php

namespace App\Http\Controllers;

use App\Services\ArticleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ArticleController extends Controller
{
    protected ArticleService $articleService;

    public function __construct(ArticleService $articleService) {
        $this->articleService = $articleService;
    }

    /**
     * search article based on conditions
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchArticles(Request $request): JsonResponse {
        $validated = $request->validate([
            'keyword' => 'nullable|string|max:255',
            'categoryList' => 'nullable|array',
            'categoryList.*' => 'string|max:100',
            'type' => 'nullable|string|in:article,announcement,draft',
            'tagList' => 'nullable|array',
            'tagList.*' => 'string|max:100',
            'sortStrategy' => 'nullable|in:publish_date,update_date,recommended',
            'sortDirection' => 'nullable|in:desc,asc,none',
        ]);

        $res = $this->articleService->searchArticles($validated);
        return response()->json($res, 200);
    }

    /**
     * get article by id
     *
     * @param $id
     * @return JsonResponse
     */
    public function getArticle($id): JsonResponse {
        try {
            return response()->json($this->articleService->getArticleById($id), 200);
        } catch (\Exception $e) {
            Log::error('Failed to get article', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to get article', 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * delete article by id
     *
     * @param $id
     * @return void
     */
    public function deleteArticle($id): void {
        $this->articleService->deleteArticle($id);
    }

    public function publishArticle(Request $request): JsonResponse {
        // 验证请求数据
        $validated = $request->validate([
            'authorId' => 'required|exists:quanthub_users,id',
            'title' => 'required|string|max:255',
            'subTitle' => 'nullable|string|max:255',
            'type' => 'required|in:article,announcement,draft',
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

    public function updateArticle(Request $request): JsonResponse {
        $validated = $request->validate([
            'articleId' => 'required|exists:articles,id',
            'authorId' => 'required|exists:quanthub_users,id',
            'title' => 'required|string|max:255',
            'subTitle' => 'nullable|string|max:255',
            'type' => 'required|in:article,announcement,draft',
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
}
