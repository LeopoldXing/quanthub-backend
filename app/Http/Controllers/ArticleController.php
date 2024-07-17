<?php

namespace App\Http\Controllers;

use App\Services\ArticleService;
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

    public function createArticle(Request $request) {
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

        DB::beginTransaction();

        try {
            // 获取或创建分类
            $categoryId = null;
            $categoryData = null;
            if (!empty($validated['category'])) {
                $category = Category::firstOrCreate(
                    ['name' => $validated['category']],
                    ['created_by' => $validated['authorId'], 'updated_by' => $validated['authorId']]
                );
                $categoryId = $category->id;
                $categoryData = ['id' => $category->id, 'name' => $category->name];
            }

            // 创建新文章并保存到数据库
            $article = Article::create([
                'author_id' => $validated['authorId'],
                'title' => $validated['title'],
                'sub_title' => $validated['subTitle'] ?? null,
                'content' => $validated['contentHtml'],
                'category_id' => $categoryId,
                'rate' => 0,
                'status' => 'published',
                'publish_date' => now(),
                'cover_image_link' => $validated['coverImageLink'] ?? null,
                'attachment_link' => $validated['attachmentLink'] ?? null,
                'created_by' => $validated['authorId'],
                'updated_by' => $validated['authorId']
            ]);

            // 处理标签
            $tagsData = [];
            if (!empty($validated['tags'])) {
                $tagIds = [];
                foreach ($validated['tags'] as $tagName) {
                    $tag = Tag::firstOrCreate(
                        ['name' => $tagName],
                        ['created_by' => $validated['authorId'], 'updated_by' => $validated['authorId']]
                    );
                    $tagIds[] = $tag->id;
                    $tagsData[] = ['id' => $tag->id, 'name' => $tag->name];
                }

                // 在link_tag_article表中插入数据
                foreach ($tagIds as $tagId) {
                    LinkTagArticle::create([
                        'article_id' => $article->id,
                        'tag_id' => $tagId,
                        'created_by' => $validated['authorId'],
                        'updated_by' => $validated['authorId']
                    ]);
                }
            }

            DB::commit();

            // 准备返回的数据
            $author = $article->author()->first();
            $response = [
                'id' => (string)$article->id,
                'title' => $article->title,
                'subtitle' => $article->sub_title,
                'tags' => $tagsData,
                'category' => $categoryData,
                'contentHtml' => $article->content,
                'comments' => [],
                'likes' => '0',
                'isLiking' => false,
                'views' => '1',
                'author' => [
                    'id' => (string)$author->id,
                    'username' => $author->username,
                    'role' => $author->role,
                    'avatarLink' => $author->avatarLink
                ],
                'publishTimestamp' => (int)$article->created_at->timestamp,
                'updateTimestamp' => (int)$article->updated_at->timestamp,
                'publishTillToday' => 'a few seconds ago'
            ];

            return response()->json($response, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create article', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create article', 'message' => $e->getMessage()], 500);
        }
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

            return response()->json($this->articleService->queryArticle($validated['articleId']), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update article', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update article', 'message' => $e->getMessage()], 500);
        }
    }

    public function getArticle($id) {
        try {
            return response()->json($this->articleService->queryArticle($id), 200);
        } catch (\Exception $e) {
            Log::error('Failed to get article', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to get article', 'message' => $e->getMessage()], 500);
        }
    }

}
