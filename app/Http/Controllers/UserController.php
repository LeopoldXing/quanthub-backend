<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QuanthubUser;

class UserController extends Controller
{
    public function createMyUser(Request $request) {
        // 验证请求数据
        $validated = $request->validate([
            'auth0Id' => 'required|string',
            'email' => 'required|email',
        ]);

        // 检查用户是否存在
        $user = QuanthubUser::where('username', $validated['auth0Id'])
            ->orWhere('email', $validated['email'])
            ->first();

        // 如果用户存在，返回已有用户信息
        if ($user) {
            return response()->json($user, 200);
        }

        // 如果用户不存在，创建新用户
        $user = QuanthubUser::create([
            'username' => $validated['email'],
            'password' => bcrypt('default_password'),
            'email' => $validated['email'],
            'role' => 'Registered User',
        ]);

        return response()->json($user, 201);
    }

    public function updateProfile(Request $request) {
        // 验证请求数据
        $validated = $request->validate([
            'user.id' => 'required|exists:quanthub_users,id',
            'user.email' => 'required|email',
            // 根据需要添加更多验证规则
        ]);

        // 查找并更新用户信息
        $user = QuanthubUser::findOrFail($validated['user']['id']);
        $user->update([
            'email' => $validated['user']['email'],
            // 根据需要更新更多字段
        ]);

        return response()->json($user, 200);
    }
}
