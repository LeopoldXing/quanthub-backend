<?php

use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ArticleController;

Route::post('/my/user', [UserController::class, 'createMyUser']);
Route::put('/profile', [UserController::class, 'updateProfile']);
Route::get('/profile', [UserController::class, 'getUserProfile']);

Route::get('/article/search', [ArticleController::class, 'searchArticle']);
Route::post('/article/publish', [ArticleController::class, 'publishArticle']);
Route::put('/article/update', [ArticleController::class, 'updateArticle']);
Route::get('/article/{id}', [ArticleController::class, 'getArticle']);
Route::post('/article/draft', [ArticleController::class, 'createDraft']);
Route::delete('/article/{id}', [ArticleController::class, 'deleteArticle']);

Route::get('/tag/{number}', [TagController::class, 'shuffleTags']);
