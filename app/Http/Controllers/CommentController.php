<?php

namespace App\Http\Controllers;

use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    protected CommentService $commentService;

    public function __construct(CommentService $commentService) {
        $this->commentService = $commentService;
    }

    /**
     * create new comment
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addComment(Request $request): JsonResponse {
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'articleId' => 'required|exists:articles,id',
            'operatorId' => 'required|exists:quanthub_users,auth0_id'
        ]);

        $response = $this->commentService->createComment($validated['content'], $validated['articleId'], $validated['operatorId']);

        return response()->json($response['data'], $response['status']);
    }

    /**
     * get all comments by a given article
     *
     * @param $articleId
     * @return JsonResponse
     */
    public function getCommentsByArticleId($articleId): JsonResponse {
        $response = $this->commentService->getCommentsByArticleId($articleId);
        return response()->json($response['data'], $response['status']);
    }

    /**
     * delete comment by id
     *
     * @param $id
     * @return JsonResponse
     */
    public function deleteCommentById($id): JsonResponse {
        $response = $this->commentService->deleteCommentById($id);
        return response()->json($response['data'], $response['status']);
    }

    /**
     * update comment
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateComment(Request $request): JsonResponse {
        $validated = $request->validate([
            'id' => 'required|exists:comments,id',
            'content' => 'required|string|max:5000',
            'operatorId' => 'required|exists:quanthub_users,auth0_id'
        ]);

        $response = $this->commentService->updateComment($validated['id'], $validated['content'], $validated['operatorId']);
        Log::info("更新评论", ['comment' => $response]);

        return response()->json($response, 200);
    }
}
