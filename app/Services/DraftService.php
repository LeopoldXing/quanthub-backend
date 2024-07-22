<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\QuanthubUser;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use function PHPUnit\Framework\isEmpty;

class DraftService
{
    protected CategoryService $categoryService;
    protected TagService $tagService;
    protected CommentService $commentService;
    protected ElasticsearchService $elasticsearchService;

    public function __construct(CategoryService      $categoryService,
                                TagService           $tagService,
                                CommentService       $commentService,
                                ElasticsearchService $elasticsearchService) {
        $this->categoryService = $categoryService;
        $this->tagService = $tagService;
        $this->commentService = $commentService;
        $this->elasticsearchService = $elasticsearchService;
    }

    public function saveDraft($data) {
        try {
            $hasReference = array_key_exists('referenceId', $data) && !empty($data['referenceId'])
                && Article::where('draft_reference_id', $data['referenceId'])->exists();
            $hasDraftId = array_key_exists('id', $data) && !empty($data['id']) && Article::where([
                    ['id', '=', $data['id']],
                    ['type', '=', 'draft']
                ])->exists();
            $savedDraft = null;
            if (!$hasDraftId && !$hasReference) {
                /*  new articles being saved 1st time  */
                Log::info("新文章第一次创建", ['draft' => $data]);
                $savedDraft = $this->createDraft($data);
            } else if ($hasDraftId && !$hasReference) {
                /*  new articles being saved 2nd or more times  */
                Log::info("新文章第二次创建", ['draft' => $data]);
                $savedDraft = $this->updateDraft($data);
            } else if (!$hasDraftId && $hasReference) {
                /*  existed articles being saved 1st time  */
                Log::info("旧文章第一次创建", ['draft' => $data]);
                $savedDraft = $this->createDraft($data);
            } else if ($hasDraftId && $hasReference) {
                /*  existed articles being saved 2nd or more times  */
                Log::info("旧文章第二次创建", ['draft' => $data]);
                $savedDraft = $this->updateDraft($data);
            }

            if (!empty($savedDraft)) {
                return ['data' => $savedDraft, 'status' => 200];
            } else {
                Log::error("保存草稿失败：", ['$savedDraft' => $savedDraft]);
                throw new Exception('Draft not saved');
            }
        } catch (Exception $e) {
            Log::error('Failed to create article', ['error' => $e->getMessage()]);
            return ['data' => ['error' => 'Failed to create article', 'message' => $e->getMessage()], 'status' => 500];
        }
    }

    public function getDraftByArticleId($articleId): array {
        try {
            $article = Article::with(['author', 'likes', 'tags'])->findOrFail($articleId);
            $draft = Article::with(['author', 'category', 'tags'])->where('draft_reference_id', $article->id)->first();

            $response = null;
            if (!empty($draft)) {
                $response = $this->constructDraftResponse($draft);
            }

            return ['data' => $response, 'status' => 200];
        } catch (Exception $e) {
            Log::error('Failed to get draft', ['error' => $e->getMessage()]);
            return ['data' => ['error' => 'Failed to get draft', 'message' => $e->getMessage()]];
        }
    }

    public function deleteDraft($draftId): void {
        Article::find($draftId)->delete();
    }

    public function constructDraftResponse($draft): array {
        $author = $draft->author;
        $tags = $draft->tags ? $draft->tags : [];
        if (!empty($draft->category)) {
            $category = $draft->category;
        } else {
            $category = Category::firstOrCreate(
                ['name' => 'unknown'],
                ['created_by' => $author->id, 'updated_by' => $author->id]
            );
        }

        $response = [
            'id' => $draft->id,
            'title' => $draft->title,
            'subtitle' => $draft->sub_title,
            'tags' => $tags ? $tags->map(function ($tag) {
                return $tag->name;
            }) : [],
            'category' => $category ? $category->name : "unknown",
            'contentHtml' => $draft->content,
            'coverImageLink' => $draft->cover_image_link,
            'rate' => 0,
            'type' => 'draft',
            'comments' => [],
            'likes' => 0,
            'isLiking' => false,
            'views' => 1,
            'author' => [
                'id' => $draft->author->id,
                'username' => $draft->author->username,
                'role' => $draft->author->role,
                'avatarLink' => $draft->author->avatar_link
            ],
            'referenceId' => $draft->draft_reference_id,
            'draftId' => $draft->id,
            'publishTimestamp' => (int)$draft->created_at->timestamp,
            'updateTimestamp' => (int)$draft->updated_at->timestamp,
            'publishTillToday' => '3 days ago',
            'updateTillToday' => 'yesterday'
        ];

        return $response;
    }

    private function createDraft($data) {
        DB::beginTransaction();
        $author = QuanthubUser::findOrFail($data['authorId']);

        // delete existing draft
        if (isset($data['referenceId'])) {
            Article::where([
                ['draft_reference_id', $data['referenceId']],
                ['type', '=', 'draft']
            ])->delete();
        }

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
            'status' => 'draft',
            'type' => 'draft',
            'cover_image_link' => $data['coverImageLink'] ?? null,
            'attachment_link' => $data['attachmentLink'] ?? null,
            'draft_reference_id' => $data['referenceId'] ?? null,
            'created_by' => $author->id,
            'updated_by' => $author->id
        ]);

        // link tags and this draft
        $tagList = $this->tagService->connectTagsToArticle($data['tags'], $article->id, $author->id);
        $tagNameList = [];
        foreach ($tagList as $tag) {
            $tagNameList[] = $tag->name;
        }

        // add to elasticsearch
        $this->elasticsearchService->createArticleDoc([
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

        DB::commit();

        return $article;
    }

    public function getDraftById($id) {
        $draft = Article::with('category', 'tags', 'author')->findOrFail($id);
        return $draft;
    }

    private function updateDraft($data) {
        DB::beginTransaction();

        $article = Article::with(['author'])->findOrFail($data['id']);

        // save new category
        if (array_key_exists('category', $data) && !empty($data['category'])) {
            $category = $this->categoryService->saveCategory($data['category'], $article->id);
        } else {
            $category = $this->categoryService->saveCategory("unknown", $article->id);
        }

        $article->update([
            'title' => $data['title'],
            'sub_title' => $data['subTitle'] ?? null,
            'content' => $data['contentHtml'],
            'category_id' => $category->id,
            'cover_image_link' => $data['coverImageLink'] ?? null,
            'attachment_link' => $data['attachmentLink'] ?? null,
            'updated_by' => $data['authorId']
        ]);

        // update tags
        $this->tagService->disconnectTagsFromArticle($article->id);
        $savedTags = $this->tagService->connectTagsToArticle($data['tags'], $article->id, $article->author->id);

        // commit changes
        DB::commit();

        return $this->getDraftById($article->id);
    }
}
