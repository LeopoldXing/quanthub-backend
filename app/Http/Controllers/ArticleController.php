<?php

namespace App\Http\Controllers;

use App\services\ArticleService;
use Illuminate\Http\Request;
use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\LinkTagArticle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArticleController extends Controller
{
    protected $articleService;

    public function __construct(ArticleService $articleService) {
        $this->articleService = $articleService;
    }

    public function searchArticle(Request $request) {
        // 验证请求数据
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

        $res = $this->articleService->search($validated);
        return response()->json($res, 200);
    }

    public function publishArticle(Request $request) {
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
        $validated['status'] = 'published';

        $res = $this->articleService->createArticle($validated);

        return response()->json($res['response'], $res['status']);
    }

    public function updateArticle(Request $request) {
        // 验证请求数据
        $validated = $request->validate([
            'articleId' => 'required|exists:articles,id',
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

        DB::beginTransaction();

        try {
            $article = Article::findOrFail($validated['articleId']);

            // determine if the category exist
            $category = null;
            if (!empty($validated['category'])) {
                $category = Category::firstOrCreate(
                    ['name' => $validated['category']],
                    ['created_by' => $validated['authorId'], 'updated_by' => $validated['authorId']]
                );
            }

            $article->update([
                'title' => $validated['title'],
                'sub_title' => $validated['subTitle'] ?? null,
                'content' => $validated['contentHtml'],
                'category_id' => $category ? $category->id : null,
                'cover_image_link' => $validated['coverImageLink'] ?? null,
                'attachment_link' => $validated['attachmentLink'] ?? null,
                'updated_by' => $validated['authorId']
            ]);

            // 更新标签
            LinkTagArticle::where('article_id', $article->id)->delete();
            if (!empty($validated['tags'])) {
                foreach ($validated['tags'] as $tagName) {
                    $tag = Tag::firstOrCreate(
                        ['name' => $tagName],
                        ['created_by' => $validated['authorId'], 'updated_by' => $validated['authorId']]
                    );
                    LinkTagArticle::create([
                        'article_id' => $article->id,
                        'tag_id' => $tag->id,
                        'created_by' => $validated['authorId'],
                        'updated_by' => $validated['authorId']
                    ]);
                }
            }

            DB::commit();

            return response()->json($this->articleService->getArticleById($validated['articleId']), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update article', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update article', 'message' => $e->getMessage()], 500);
        }
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

}
