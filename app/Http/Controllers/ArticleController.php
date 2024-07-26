<?php

namespace App\Http\Controllers;

use App\Services\ArticleService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ArticleController extends Controller
{
    protected ArticleService $articleService;

    public function __construct(ArticleService $articleService) {
        $this->articleService = $articleService;
    }

    /**
     * Recursively clean array data to ensure UTF-8 encoding
     *
     * @param mixed $data
     * @return mixed
     */
    function cleanArrayData($data): mixed {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->cleanArrayData($value);
            }
        } elseif (is_string($data)) {
            $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }

        return $data;
    }


    /**
     * search article based on conditions
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function searchArticles(Request $request): JsonResponse {
        // Extract and parse categoryList from the query string
        $categoryList = $request->input('categoryList');
        if (!is_array($categoryList)) {
            $categoryList = explode(',', $categoryList);
        }

        // Extract and parse tagList from the query string
        $tagList = $request->input('tagList');
        if (!is_array($tagList)) {
            $tagList = explode(',', $tagList);
        }

        // Prepare data for validation
        $data = [
            'keyword' => $request->input('keyword'),
            'categoryList' => $categoryList,
            'tagList' => $tagList,
            'type' => $request->input('type'),
            'isDraft' => $request->input('isDraft'),
            'sortStrategy' => $request->input('sortStrategy'),
            'sortDirection' => $request->input('sortDirection'),
        ];

        $validated = Validator::make($data, [
            'keyword' => 'nullable|string|max:255',
            'categoryList' => 'nullable|array',
            'categoryList.*' => 'string|max:100',
            'type' => 'required|string|in:article,announcement,all',
            'isDraft' => 'required',
            'tagList' => 'nullable|array',
            'tagList.*' => 'string|max:100',
            'sortStrategy' => 'nullable|in:publish_date,update_date,recommended',
            'sortDirection' => 'nullable|in:desc,asc,none',
        ])->validate();

        $res = $this->articleService->searchArticles($validated);
        $res = $this->cleanArrayData($res);
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
        } catch (Exception $e) {
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

    /**
     * publish article
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createArticle(Request $request): JsonResponse {
        Log::info("发布文章接收到的数据:", ['articleData' => $request]);
        $validated = $request->validate([
            'authorId' => 'required|exists:quanthub_users,id',
            'title' => 'required|string|max:255',
            'subTitle' => 'nullable|string|max:255',
            'type' => 'required|string|in:article,announcement',
            'isDraft' => 'required|boolean',
            'draftId' => 'nullable',
            'contentHtml' => 'required|string',
            'contentText' => 'required|string',
            'coverImageLink' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:100',
            'attachmentLink' => 'nullable|string|max:255',
            'attachmentName' => 'nullable|string|max:255'
        ]);
        $validated['status'] = 'published';
        Log::info("准备发布文章：", ['article' => $validated]);
        $res = $this->articleService->createArticle($validated);

        return response()->json($res['response'], $res['status']);
    }

    public function updateArticle(Request $request): JsonResponse {
        $validated = $request->validate([
            'articleId' => 'required|exists:articles,id',
            'authorId' => 'required|exists:quanthub_users,id',
            'title' => 'required|string|max:255',
            'subTitle' => 'nullable|string|max:255',
            'type' => 'required|in:article,announcement',
            'isDraft' => 'required|boolean',
            'contentHtml' => 'required|string',
            'contentText' => 'required|string',
            'coverImageLink' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:100',
            'attachmentLink' => 'nullable|string|max:255',
            'attachmentName' => 'nullable|string|max:255',
            'draftId' => 'nullable'
        ]);

        Log::info("验证通过的数据", ['data' => $validated]);

        try {
            $res = $this->articleService->updateArticle($validated);
            return response()->json($res, 200);
        } catch (Exception $e) {
            Log::error("update article failed", ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update article', 'message' => $e->getMessage()], 500);
        }
    }
}
