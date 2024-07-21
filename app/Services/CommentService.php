<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\QuanthubUser;
use Exception;
use Illuminate\Support\Facades\Log;

class CommentService
{
    /**
     * publish a new comment
     *
     * @param $content
     * @param $articleId
     * @param $auth0Id
     * @return array
     */
    public function createComment($content, $articleId, $auth0Id): array {
        try {
            $user = QuanthubUser::where('auth0_id', $auth0Id)->first();
            $comment = Comment::create([
                'content' => $content,
                'article_id' => $articleId,
                'user_id' => $user->id,
                'publish_datetime' => now(),
                'status' => 'published',
                'created_by' => $user->id,
                'updated_by' => $user->id
            ]);

            $response['data'] = $this->getCommentById($comment->id);
            $response['status'] = 201;

            return $response;
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            Log::error('Failed to search', ['error' => $exception->getMessage()]);
            return ['response' => ['error' => 'Failed to search', 'data' => $exception->getMessage()], 'status' => 500];
        }
    }

    /**
     * query a certain article's comments
     *
     * @param $articleId
     * @return array
     */
    public function getCommentsByArticleId($articleId): array {
        try {
            $comments = Comment::with('user')
                ->where('article_id', $articleId)
                ->orderBy('publish_datetime', 'desc')
                ->get();

            $commentData = [];
            if (!empty($comments)) {
                $commentData = $comments->map(function ($comment) {
                    return $this->constructCommentResponse($comment);
                })->toArray();
            }

            $response['data'] = $commentData;
            $response['status'] = 201;

            return $response;
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            Log::error('Failed to search', ['error' => $exception->getMessage()]);
            return ['response' => ['error' => 'Failed to search', 'data' => $exception->getMessage()], 'status' => 500];
        }
    }

    /**
     * get comment by id
     *
     * @param $id
     * @return array
     */
    public function getCommentById($id): array {
        $comment = Comment::with('user')->findOrFail($id);
        return $this->constructCommentResponse($comment);
    }

    private function constructCommentResponse($comment): array {
        return [
            'id' => $comment->id,
            'articleId' => $comment->article_id,
            'content' => $comment->content,
            'user' => $comment->user,
            'publishDatetime' => $comment->publish_datetime,
            'status' => $comment->status,
            'publishTillToday' => 'a few seconds ago'
        ];
    }

    public function deleteCommentById($id) {
        try {
            Comment::destroy($id);
            return ['data' => true, 'status' => 200];
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            Log::error('Failed to delete', ['error' => $exception->getMessage()]);
            return ['response' => ['error' => 'Failed to delete', 'data' => $exception->getMessage()], 'status' => 500];
        }
    }

    /**
     * update a given comment
     *
     * @param $id
     * @param $content
     * @param $operatorId
     * @return array
     */
    public function updateComment($id, $content, $operatorId): array {
        $comment = Comment::with('user')->findOrFail($id);
        $comment->update([
            'content' => $content,
            'updated_by' => $operatorId
        ]);
        return $this->constructCommentResponse($comment);
    }


}
