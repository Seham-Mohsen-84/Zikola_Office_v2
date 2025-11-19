<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth('api')->user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $rooms = Room::with('branch')->get();

        return response()->json([
            'message' => 'All rooms fetched successfully',
            'data' => $rooms,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth('api')->user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'branch_id' => 'required|uuid|exists:branches,id',
        ]);

        $room = Room::create($validated);

        return response()->json([
            'message' => 'Room created successfully',
            'data' => $room
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = auth('api')->user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $room = Room::with('branch')->find($id);

        if (!$room) {
            return response()->json(['message' => 'Room not found'], 404);
        }

        return response()->json([
            'message' => 'Room details fetched successfully',
            'data' => $room
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = auth('api')->user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $room = Room::find($id);

        if (!$room) {
            return response()->json(['message' => 'Room not found'], 404);
        }

        $validated = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'branch_id' => 'sometimes|uuid|exists:branches,id',
        ]);

        $room->update($validated);

        return response()->json([
            'message' => 'Room updated successfully',
            'data' => $room
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = auth('api')->user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $room = Room::find($id);

        if (!$room) {
            return response()->json(['message' => 'Room not found'], 404);
        }

        $room->delete();

        return response()->json([
            'message' => 'Room deleted successfully'
        ]);
    }

    public function count()
    {
        $count =Room::count();

        return response()->json([
            'message' => 'Rooms count fetched successfully',
            'count' => $count
        ]);
    }

}
