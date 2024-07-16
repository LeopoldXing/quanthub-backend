<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QuanthubUser;

class UserController extends Controller
{
    public function createMyUser(Request $request) {
        $validated = $request->validate([
            'auth0Id' => 'required|string',
            'email' => 'required|email',
        ]);

        $user = QuanthubUser::create([
            'username' => $validated['auth0Id'],
            'email' => $validated['email'],
            'password' => bcrypt('default_password'),
        ]);

        return response()->json($user, 201);
    }

    public function updateProfile(Request $request) {
        $validated = $request->validate([
            'user.id' => 'required|exists:quanthub_users,id',
            'user.email' => 'required|email',
            // 根据需要添加更多验证规则
        ]);

        $user = QuanthubUser::findOrFail($validated['user']['id']);
        $user->update([
            'email' => $validated['user']['email'],
            // 根据需要更新更多字段
        ]);

        return response()->json($user, 200);
    }
}
