<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Like;
use App\Models\LinkTagArticle;
use App\Models\QuanthubUser;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArticleService
{
    protected $elasticsearch;

    public function __construct(ElasticsearchService $elasticsearch) {
        $this->elasticsearch = $elasticsearch;
    }

    public function search($condition) {
        try {
            $res = $this->elasticsearch->search($condition);
            $articleOverview = [];
            foreach ($res as $item) {
                $currentArticle = $this->getArticleById($item['id']);
                $currentArticle['commentsCount'] = $currentArticle['comments']->count();
                $currentArticle['description'] = $item['source']['content'];
                $articleOverview[] = $currentArticle;
            }
            return $articleOverview;
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            Log::error('Failed to search', ['error' => $exception->getMessage()]);
            return ['response' => ['error' => 'Failed to search', 'message' => $exception->getMessage()], 'status' => 500];
        }
    }

    public function createArticle($data) {
        DB::beginTransaction();

        try {
            // 获取或创建分类
            $categoryId = null;
            $categoryData = null;
            if (!empty($data['category'])) {
                $category = Category::firstOrCreate(
                    ['name' => $data['category']],
                    ['created_by' => $data['authorId'], 'updated_by' => $data['authorId']]
                );
                $categoryId = $category->id;
                $categoryData = ['id' => $category->id, 'name' => $category->name];
            }

            // 创建新文章并保存到数据库
            $article = Article::create([
                'author_id' => $data['authorId'],
                'title' => $data['title'],
                'sub_title' => $data['subTitle'] ?? null,
                'content' => $data['contentHtml'],
                'category_id' => $categoryId,
                'rate' => 0,
                'status' => $data['status'] ?? 'published',
                'publish_date' => now(),
                'cover_image_link' => $data['coverImageLink'] ?? null,
                'attachment_link' => $data['attachmentLink'] ?? null,
                'created_by' => $data['authorId'],
                'updated_by' => $data['authorId']
            ]);

            // 处理标签
            $tagsData = [];
            $tagNameList = [];
            if (!empty($data['tags'])) {
                $tagIds = [];
                foreach ($data['tags'] as $tagName) {
                    $tag = Tag::firstOrCreate(
                        ['name' => $tagName],
                        ['created_by' => $data['authorId'], 'updated_by' => $data['authorId']]
                    );
                    $tagIds[] = $tag->id;
                    $tagsData[] = ['id' => $tag->id, 'name' => $tag->name];
                    $tagNameList[] = $tag->name;
                }

                // 在link_tag_article表中插入数据
                foreach ($tagIds as $tagId) {
                    LinkTagArticle::create([
                        'article_id' => $article->id,
                        'tag_id' => $tagId,
                        'created_by' => $data['authorId'],
                        'updated_by' => $data['authorId']
                    ]);
                }
            }

            // add to elasticsearch
            $author = QuanthubUser::findOrFail($data['authorId']);
            $this->elasticsearch->indexArticle([
                'index' => 'quanthub-articles',
                'id' => $article->id,
                'author' => [
                    'id' => $author->id,
                    'username' => $author->username,
                    'email' => $author->email,
                    'role' => $author->role
                ],
                'title' => $article->title,
                'sub_title' => $article->sub_title,
                'content' => $data['contentText'],
                'type' => $article->author->role === 'admin' ? 'announcement' : 'article',
                'category' => $data['category'],
                'tags' => $tagNameList,
                'status' => $data['status'] ?? 'published',
                'publish_date' => now(),
                'cover_image_link' => $data['coverImageLink'] ?? null,
                'attachment_link' => $data['attachmentLink'] ?? null,
                'created_by' => $data['authorId'],
                'updated_by' => $data['authorId']
            ]);

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

            return ['response' => $response, 'status' => 201];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create article', ['error' => $e->getMessage()]);
            return ['response' => ['error' => 'Failed to create article', 'message' => $e->getMessage()], 'status' => 500];
        }
    }

    public function getArticleById($id) {
        $article = Article::findOrFail($id);
        $author = QuanthubUser::findOrFail($article->author_id);
        $comments = Comment::where('article_id', $article->id)->get();
        $likes = Like::where('article_id', $article->id)->count();
        $isLiking = Like::where('article_id', $article->id)->where('user_id', $author->id)->count();
        $tags = LinkTagArticle::where('article_id', $article->id)->get()->map(function ($tagArticle) {
            return Tag::find($tagArticle->tag_id);
        });
        $category = Category::find($article->category_id);
        $category_data = null;
        if (!empty($category)) {
            $category_data = ['id' => $category->id, 'name' => $category->name];
        }

        $response = [
            'id' => (string)$article->id,
            'title' => $article->title,
            'subtitle' => $article->sub_title,
            'tags' => $tags->map(function ($tag) {
                return ['id' => (string)$tag->id, 'name' => $tag->name];
            }),
            'category' => $category_data,
            'contentHtml' => $article->content,
            'coverImageLink' => $article->cover_image_link,
            'rate' => 0,
            'comments' => $comments ? $comments->map(function ($comment, $author) {
                return ['id' => $comment->id,
                    'articleId' => $comment->article_id,
                    'content' => $comment->content,
                    'user' => ['id' => $author->id,
                        'auth0Id' => $author->auth0Id,
                        'username' => $author->username,
                        'role' => $author->role,
                        'avatarLink' => $author->avatarLink],
                    'publishTillToday' => '3 days ago',
                    'status' => 'normal'
                ];
            }) : null,
            'likes' => $likes,
            'isLiking' => $isLiking > 0,
            'views' => 1,
            'author' => ['id' => $author->id, 'username' => $author->username, 'role' => $author->role, 'avatarLink' => $author->avatarLink],
            'publishTimestamp' => (int)$article->created_at->timestamp,
            'updateTimestamp' => (int)$article->updated_at->timestamp,
            'publishTillToday' => '3 days ago',
            'updateTillToday' => 'yesterday'
        ];
        return $response;
    }
}
