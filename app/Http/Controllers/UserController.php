<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QuanthubUser;

class UserController extends Controller
{
    public function createMyUser(Request $request) {
        // 验证请求数据
        $validated = $request->validate([
            'auth0Id' => 'required|string|unique:quanthub_users,auth0_id',
            'email' => 'required|email|unique:quanthub_users,email',
        ]);

        // 检查用户是否存在
        $user = QuanthubUser::where('auth0_id', $validated['auth0Id'])
            ->orWhere('email', $validated['email'])
            ->first();

        // 如果用户存在，返回已有用户信息
        if ($user) {
            return response()->json($user, 200);
        }

        // 如果用户不存在，创建新用户并保存到数据库
        $user = QuanthubUser::create([
            'auth0_id' => $validated['auth0Id'],
            'username' => $validated['email'],  // 设置username为email
            'password' => bcrypt('default_password'),  // 需要根据实际情况进行处理
            'email' => $validated['email'],
            'role' => 'Registered User',  // 设置role字段为"Registered User"
        ]);

        return response()->json($user, 201);  // 将用户对象转换为JSON并返回
    }

    public function updateProfile(Request $request) {
        $validated = $request->validate([
            'user.id' => 'required|exists:quanthub_users,id',
            'user.email' => 'required|email',
        ]);

        $user = QuanthubUser::findOrFail($validated['user']['id']);
        $user->update([
            'email' => $validated['user']['email'],
        ]);

        return response()->json($user, 200);
    }

    public function getUserProfile(Request $request) {
        $validated = $request->validate([
            'id' => 'nullable|exists:quanthub_users,id',
            'auth0Id' => 'nullable|string',
            'email' => 'nullable|email',
        ]);

        // 查找用户信息
        $user = null;
        if (!empty($validated['auth0Id'])) {
            $user = QuanthubUser::where('auth0_id', $validated['auth0Id'])->first();
        } elseif (!empty($validated['email'])) {
            $user = QuanthubUser::where('email', $validated['email'])->first();
        } elseif (!empty($validated['id'])) {
            $user = QuanthubUser::find($validated['id']);
        }

        if ($user) {
            return response()->json($user, 200);
        } else {
            return response()->json(null, 204); // 无内容
        }
    }
}
