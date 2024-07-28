<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\QuanthubUser;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function createMyUser(Request $request): JsonResponse {
        $validated = $request->validate([
            'auth0Id' => 'required|string|unique:quanthub_users,auth0_id',
            'email' => 'required|email|unique:quanthub_users,email',
        ]);

        // check if the user exists
        $user = QuanthubUser::where('auth0_id', $validated['auth0Id'])
            ->orWhere('email', $validated['email'])
            ->first();

        // if the user exists, return their info
        if ($user) {
            return response()->json($user, 200);
        }

        // if the user doesn't exist, create user in the database
        $user = QuanthubUser::create([
            'auth0_id' => $validated['auth0Id'],
            'username' => $validated['email'],
            'password' => bcrypt('default_password'),
            'email' => $validated['email'],
            'role' => 'Registered User',
        ]);

        return response()->json($user, 201);
    }

    public function updateProfile(Request $request): JsonResponse {
        $validated = $request->validate([
            'auth0Id' => 'required|exists:quanthub_users,auth0_id',
            'email' => 'required|email',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'description' => 'nullable|string',
            'phoneNumber' => 'nullable|string',
            'role' => 'required|string'
        ]);

        $user = QuanthubUser::where('auth0_id', $validated['auth0Id'])->first();
        $user->update([
            'email' => $validated['email'],
            'username' => $validated['username'],
            'password' => bcrypt($validated['password']),
            'description' => $validated['description'],
            'phone_number' => $validated['phoneNumber'],
            'role' => $validated['role']
        ]);

        return response()->json($user, 200);
    }

    public function getUserProfile(Request $request): JsonResponse {
        $validated = $request->validate([
            'id' => 'nullable',
            'auth0Id' => 'nullable|string',
            'email' => 'nullable|email',
        ]);

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
            return response()->json(null, 204);
        }
    }

    public function updateAvatarLink(Request $request): void {
        $validated = $request->validate([
            'id' => 'nullable|string',
            'auth0Id' => 'required|string|unique:quanthub_users,auth0_id',
            'avatarLink' => 'nullable|string'
        ]);

        DB::table('quanthub_users')->where('auth0_id', $validated['auth0Id'])->update(['avatar_link' => $validated['avatarLink']]);
    }
}
