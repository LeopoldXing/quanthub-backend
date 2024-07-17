<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Like;
use App\Models\LinkTagArticle;
use App\Models\QuanthubUser;
use App\Models\Tag;

class ArticleService
{
    public function queryArticle($id) {
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
