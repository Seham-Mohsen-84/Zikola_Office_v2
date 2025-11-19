<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = JWTAuth::user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $users = User::withCount('orders')
            ->with('orders.item')
            ->paginate(20);

        return response()->json([
            'message' => 'All users fetched successfully',
            'data' => $users
        ]);
    }

    public function trashed()
    {
        $admin = JWTAuth::user();

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $trashedUsers = User::onlyTrashed()->get();

        return response()->json([
            'message' => 'Trashed users fetched successfully',
            'data' => $trashedUsers
        ]);
    }
    public function restore($id)
    {
        $user = JWTAuth::user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $user = User::onlyTrashed()->find($id);
        $user->restore();

        return response()->json([
            'message' => 'User restored successfully',
            'data' => $user
        ]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $admin = JWTAuth::user();

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:employee,barista',
            'room_id' => 'required|uuid|exists:rooms,id',

        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'password' => bcrypt($validated['password']),
            'role' => $validated['role'],
            'room_id' => $validated['room_id'],
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = JWTAuth::user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $targetUser = User::with(['orders.item'])
            ->withCount('orders')
            ->find($id);

        if (!$targetUser) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json([
            'message' => 'User details fetched successfully',
            'data' => $targetUser
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $admin = JWTAuth::user();

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->role === 'admin') {
            return response()->json(['message' => 'Cannot edit another admin'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id,
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'sometimes|in:employee,barista',
            'room_id' => 'sometimes|uuid|exists:rooms,id',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = JWTAuth::user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($targetUser->role === 'admin') {
            return response()->json(['message' => 'Cannot delete another admin'], 403);
        }

        $targetUser->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

}
