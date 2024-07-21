<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\DraftController;
use App\Http\Controllers\LikingController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ArticleController;

Route::post('/my/user', [UserController::class, 'createMyUser']);
Route::put('/profile', [UserController::class, 'updateProfile']);
Route::get('/profile', [UserController::class, 'getUserProfile']);

Route::get('/article/search', [ArticleController::class, 'searchArticles']);
Route::post('/article/publish', [ArticleController::class, 'publishArticle']);
Route::put('/article/update', [ArticleController::class, 'updateArticle']);
Route::get('/article/{id}', [ArticleController::class, 'getArticle']);
Route::delete('/article/{id}', [ArticleController::class, 'deleteArticle']);

Route::post('/draft/create', [DraftController::class, 'createDraft']);
Route::get('/draft/{articleId}', [DraftController::class, 'getDraftByArticleId']);

Route::get('/tag/{number}', [TagController::class, 'shuffleTags']);

Route::get('/comment/get/{articleId}', [CommentController::class, 'getCommentsByArticleId']);
Route::post('/comment/create', [CommentController::class, 'addComment']);
Route::delete('/comment/delete/{id}', [CommentController::class, 'deleteCommentById']);
Route::put('/comment/update', [CommentController::class, 'updateComment']);

Route::post('/like', [LikingController::class, 'likeArticle']);
Route::post('/cancel', [LikingController::class, 'cancelLikes']);
