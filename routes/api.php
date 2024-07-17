<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ArticleController;

Route::post('/my/user', [UserController::class, 'createMyUser']);
Route::put('/profile', [UserController::class, 'updateProfile']);
Route::get('/profile', [UserController::class, 'getUserProfile']);

Route::post('/article/publish', [ArticleController::class, 'createArticle']);
Route::put('/article/update', [ArticleController::class, 'updateArticle']);
Route::get('/article/{id}', [ArticleController::class, 'getArticle']);
