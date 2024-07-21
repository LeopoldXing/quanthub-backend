<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\QuanthubUser;
use Illuminate\Http\Request;

class LikingController extends Controller
{
    /**
     * handle request of user liking an article or cancel the liking
     *
     * @param Request $request
     * @return void
     */
    public function likeArticle(Request $request): void {
        $validated = $request->validate([
            'article_id' => 'required|exists:articles,id',
            'auth0_id' => 'required|exists:quanthub_users,auth0_id',
            'type' => 'required|boolean'
        ]);

        $user = QuanthubUser::where('auth0_id', $validated['auth0_id'])->first();

        $likes = Like::where([
            ['article_id', '=', $validated['article_id']],
            ['user_id', '=', $user->id]
        ])->get();

        if ($likes->isEmpty()) {
            Like::create([
                'user_id' => $user->id,
                'article_id' => $validated['article_id'],
                'type' => $validated['type']
            ]);
        } else {
            $likes->first()->update(['type' => $validated['type']]);
        }
    }

    /**
     * handle requests of user disliking an article
     *
     * @param Request $request
     * @return void
     */
    public function cancelLikes(Request $request): void {
        $validated = $request->validate([
            'article_id' => 'required|exists:articles,id',
            'auth0_id' => 'required|exists:quanthub_users,auth0_id'
        ]);

        $user = QuanthubUser::where('auth0_id', $validated['auth0_id'])->first();
        $likes = Like::where([
            ['article_id', '=', $validated['article_id']],
            ['user_id', '=', $user->id]
        ])->get();

        if (!$likes->isEmpty()) {
            Like::destroy($likes[0]->id);
        }
    }
}
