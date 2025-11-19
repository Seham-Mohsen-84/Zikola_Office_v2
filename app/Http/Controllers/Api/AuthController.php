<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('username', 'password');

        if (!$token =JWTAuth::attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid login details',
            ], 401);
        }

        $cookie = cookie(
            config('jwt.cookie_key_name'),
            $token,
            config('jwt.ttl'),
            '/',
            null,
            true,
            true,
            false,
            'None'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'token' => $token,
            'expires_in' => config('jwt.ttl'),
        ])->withCookie($cookie);
    }

    public function me()
    {
        $user = JWTAuth::user();

        return response()->json([
            'user' => $user,
        ]);
    }
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string',
            'username' => 'required|string|unique:users',
            'password' => 'required|string',
            'email'    => 'nullable|string|email|unique:users',
            'role'     => 'in:admin,employee,barista',
            'room_id'   => 'required|uuid|exists:rooms,id',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'email'    => $request->email,
            'role'     => $request->role,
            'room_id'   => $request->room_id,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'User registered successfully',
            'user'    => $user,
        ], 201);
    }

    public function logout()
    {
        JWTAuth::logout();

        $cookie = cookie()->forget(config('jwt.cookie_key_name'));

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully',
        ])->withCookie($cookie);
    }

    public function refresh()
    {
        $newToken = JWTAuth::refresh();

        $cookie = cookie(
            config('jwt.cookie_key_name'),
            $newToken,
            config('jwt.ttl'),
            '/',
            null,
            true,
            true,
            false,
            'Strict'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Token refreshed',
            'token' => $newToken,
        ])->withCookie($cookie);
    }

}
