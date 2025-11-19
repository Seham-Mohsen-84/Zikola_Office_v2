<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchController extends Controller
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

        $branches = Branch::with('rooms')->get();

        return response()->json([
            'message' => 'All branches fetched successfully',
            'data' => $branches
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
            'name' => 'required|string|max:255|unique:branches,name',
        ]);

        $branch = Branch::create($validated);

        return response()->json([
            'message' => 'Branch created successfully',
            'data' => $branch
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

        $branch = Branch::with('rooms')->find($id);

        if (!$branch) {
            return response()->json(['message' => 'Branch not found'], 404);
        }

        return response()->json([
            'message' => 'Branch details fetched successfully',
            'data' => $branch
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

        $branch = Branch::find($id);

        if (!$branch) {
            return response()->json(['message' => 'Branch not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:branches,name,' . $branch->id,
        ]);

        $branch->update($validated);

        return response()->json([
            'message' => 'Branch updated successfully',
            'data' => $branch
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

        $branch = Branch::find($id);

        if (!$branch) {
            return response()->json(['message' => 'Branch not found'], 404);
        }


        if ($branch->rooms()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete branch with rooms. Delete rooms first.'
            ], 403);
        }

        $branch->delete();

        return response()->json([
            'message' => 'Branch deleted successfully'
        ]);
    }

    public function count()
    {
        $count =Branch::count();

        return response()->json([
            'message' => 'Branches count fetched successfully',
            'count' => $count
        ]);
    }

}
