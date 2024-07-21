<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\QuanthubUser;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use function PHPUnit\Framework\isEmpty;

class ArticleService
{
    protected ElasticsearchService $elasticsearch;
    protected CategoryService $categoryService;
    protected TagService $tagService;
    protected CommentService $commentService;
    protected LikingService $likesService;

    public function __construct(ElasticsearchService $elasticsearch,
                                CategoryService      $categoryService,
                                TagService           $tagService,
                                CommentService       $commentService,
                                LikingService        $likesService) {
        $this->elasticsearch = $elasticsearch;
        $this->categoryService = $categoryService;
        $this->tagService = $tagService;
        $this->commentService = $commentService;
        $this->likesService = $likesService;
    }

    /**
     * conditional search articles in elasticsearch
     *
     * @param $condition
     * @return array
     */
    public function searchArticles($condition): array {
        try {
            $res = $this->elasticsearch->conditionalSearch($condition);
            $articleOverview = [];
            foreach ($res as $item) {
                $articleId = $item['id'];
                $description = $item['source']['content'];
                if (strlen($description) > 350) {
                    $description = substr($description, 0, 350) . '...';
                }
                $currentArticle = $this->getArticleOverviewData($articleId, $description);
                $articleOverview[] = $currentArticle;
            }

            return $articleOverview;
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            Log::error('Failed to search', ['error' => $exception->getMessage()]);
            return ['response' => ['error' => 'Failed to search', 'message' => $exception->getMessage()], 'status' => 500];
        }
    }

    /**
     * create new article
     *
     * @param $data
     * @return array
     */
    public function createArticle($data): array {
        DB::beginTransaction();

        try {
            $author = QuanthubUser::findOrFail($data['authorId']);

            // create category if not exist
            if (isset($data['category'])) {
                $category = $this->categoryService->saveCategory($data['category'], $author->id);
            } else {
                $category = $this->categoryService->saveCategory("unknown", $author->id);
            }


            // create article and persis in mysql database
            $article = Article::create([
                'author_id' => $author->id,
                'title' => $data['title'],
                'sub_title' => $data['subTitle'] ?? null,
                'content' => $data['contentHtml'],
                'category_id' => $category->id,
                'rate' => 0,
                'status' => $data['status'] ?? 'published',
                'type' => isEmpty($data['type']) ? 'article' : $data['type'],
                'publish_date' => now(),
                'cover_image_link' => $data['coverImageLink'] ?? null,
                'attachment_link' => $data['attachmentLink'] ?? null,
                'draft_reference_id' => $data['referenceId'] ?? null,
                'created_by' => $author->id,
                'updated_by' => $author->id
            ]);

            // link tags and this article
            $tagList = $this->tagService->connectTagsToArticle($data['tags'], $article->id, $author->id);
            $tagNameList = [];
            foreach ($tagList as $tag) {
                $tagNameList[] = $tag->name;
            }

            // add to elasticsearch
            if ($data['type'] != 'draft') {
                $this->elasticsearch->createArticleDoc([
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
                    'type' => $article->type,
                    'category' => $category->name,
                    'tags' => $tagNameList,
                    'status' => $data['status'] ?? 'published',
                    'publish_date' => now(),
                    'cover_image_link' => $data['coverImageLink'] ?? null,
                    'attachment_link' => $data['attachmentLink'] ?? null,
                    'created_by' => $author->id,
                    'updated_by' => $author->id
                ]);
            }

            DB::commit();

            /*  delete related draft  */
            if (isset($data['draftId'])) {
                Article::destroy($data['draftId']);
            }

            // prepare response
            $response = [
                'id' => (string)$article->id,
                'title' => $article->title,
                'subtitle' => $article->sub_title,
                'tags' => $tagNameList,
                'category' => $category->name,
                'contentHtml' => $article->content,
                'type' => $article->type,
                'comments' => [],
                'likes' => '0',
                'isLiking' => false,
                'views' => '1',
                'author' => [
                    'id' => (string)$author->id,
                    'username' => $author->username,
                    'role' => $author->role,
                    'avatarLink' => $author->avatar_link
                ],
                'publishTimestamp' => (int)$article->created_at->timestamp,
                'updateTimestamp' => (int)$article->updated_at->timestamp,
                'publishTillToday' => 'a few seconds ago'
            ];

            return ['response' => $response, 'status' => 201];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create article', ['error' => $e->getMessage()]);
            return ['response' => ['error' => 'Failed to create article', 'message' => $e->getMessage()], 'status' => 500];
        }
    }

    public function updateArticle($articleData) {
        DB::beginTransaction();

        try {
            $article = Article::with(['author'])->findOrFail($articleData['articleId']);

            // save new category
            $category = $this->categoryService->saveCategory($articleData['category'], $article->id);

            $article->update([
                'title' => $articleData['title'],
                'sub_title' => $articleData['subTitle'] ?? null,
                'content' => $articleData['contentHtml'],
                'category_id' => $category->id,
                'cover_image_link' => $articleData['coverImageLink'] ?? null,
                'attachment_link' => $articleData['attachmentLink'] ?? null,
                'updated_by' => $articleData['authorId']
            ]);

            // update tags
            $this->tagService->disconnectTagsFromArticle($article->id);
            $savedTags = $this->tagService->connectTagsToArticle($articleData['tags'], $article->id, $article->author->id);
            $tagNameList = [];
            foreach ($savedTags as $tag) {
                $tagNameList[] = $tag->name;
            }

            /*  update elasticsearch  */
            $this->elasticsearch->updateArticleDoc([
                'index' => 'quanthub-articles',
                'id' => $article->id,
                'author' => [
                    'id' => $article->author->id,
                    'username' => $article->author->username,
                    'email' => $article->author->email,
                    'role' => $article->author->role
                ],
                'title' => $article->title,
                'sub_title' => $article->sub_title,
                'content' => $articleData['contentText'],
                'type' => $article->type,
                'category' => $category->name,
                'tags' => $tagNameList,
                'status' => $articleData['status'] ?? 'published',
                'publish_date' => $articleData['publishDate'] ?? now(),
                'cover_image_link' => $articleData['coverImageLink'] ?? null,
                'attachment_link' => $articleData['attachmentLink'] ?? null,
                'created_by' => $article->author->id,
                'updated_by' => $article->author->id
            ]);

            // commit changes
            DB::commit();

            /*  delete related draft  */
            if (isset($articleData['draftId'])) {
                Article::destroy($articleData['draftId']);
            }

            return $this->getArticleById($article->id);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update article', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * get article data by its id
     *
     * @param $id numeric
     * @return array response
     */
    public function getArticleById(float|int|string $id): array {
        $article = Article::with(['author', 'likes', 'tags'])->findOrFail($id);
        $author = $article->author;
        $likes = $article->likes ? $article->likes : [];
        $isLiking = false;
        foreach ($likes as $like) {
            if ($like->author_id === $author->id) {
                $isLiking = true;
                break;
            }
        }
        $tags = $article->tags ? $article->tags : [];
        $category = null;
        if ($article->category) {
            $category = $article->category;
        } else {
            $category = Category::firstOrCreate(
                ['name' => 'unknown'],
                ['created_by' => $author->id, 'updated_by' => $author->id]
            );
        }

        $commentData = $this->commentService->getCommentsByArticleId($article->id)['data'];

        $response = [
            'id' => $id,
            'title' => $article->title,
            'subtitle' => $article->sub_title,
            'tags' => $tags ? $tags->map(function ($tag) {
                return $tag->name;
            }) : [],
            'category' => $category ? $category->name : "unknown",
            'contentHtml' => $article->content,
            'coverImageLink' => $article->cover_image_link,
            'rate' => 0,
            'type' => $article->type,
            'comments' => $commentData,
            'likes' => $likes->count(),
            'isLiking' => $this->likesService->isThisArticleLiked($id, $article->author->id),
            'views' => 1,
            'author' => [
                'id' => $article->author->id,
                'username' => $article->author->username,
                'role' => $article->author->role,
                'avatarLink' => $article->author->avatar_link
            ],
            'publishTimestamp' => (int)$article->created_at->timestamp,
            'updateTimestamp' => (int)$article->updated_at->timestamp,
            'publishTillToday' => '3 days ago',
            'updateTillToday' => 'yesterday'
        ];

        return $response;
    }

    public function getArticleOverviewData($articleId, $description) {
        $article = Article::with(['author', 'likes', 'tags'])->findOrFail($articleId);
        $author = $article->author;
        $likes = $article->likes ? $article->likes : [];
        $tags = $article->tags ? $article->tags : [];
        if ($article->category) {
            $category = $article->category;
        } else {
            $category = Category::firstOrCreate(
                ['name' => 'unknown'],
                ['created_by' => $author->id, 'updated_by' => $author->id]
            );
        }

        $commentData = $this->commentService->getCommentsByArticleId($article->id)['data'];

        $response = [
            'id' => $articleId,
            'title' => $article->title,
            'subtitle' => $article->sub_title,
            'tags' => $tags ? $tags->map(function ($tag) {
                return $tag->name;
            }) : [],
            'category' => $category->name,
            'description' => $description,
            'coverImageLink' => $article->cover_image_link,
            'rate' => 0,
            'type' => $article->type,
            'commentsCount' => count($commentData),
            'likes' => $likes->count(),
            'views' => 1,
            'author' => [
                'id' => $article->author->id,
                'username' => $article->author->username,
                'role' => $article->author->role,
                'avatarLink' => $article->author->avatar_link
            ],
            'publishTimestamp' => (int)$article->created_at->timestamp,
            'updateTimestamp' => (int)$article->updated_at->timestamp,
            'publishTillToday' => '3 days ago',
            'updateTillToday' => 'yesterday'
        ];

        return $response;
    }

    /**
     * delete the designated article both in mysql and es
     *
     * @param $id
     * @return void
     */
    public function deleteArticle($id): void {
        Article::destroy($id);
        $this->elasticsearch->deleteArticleById('quanthub-articles', $id);
    }
}
