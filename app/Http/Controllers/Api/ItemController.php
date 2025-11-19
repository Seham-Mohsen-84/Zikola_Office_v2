<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth('api')->user();

        $cacheKey = 'employees_items_list';

        if ($user->role === 'admin') {
            $items = Item::orderBy('created_at','desc')->get();

            return response()->json([
                'message' => 'All items fetched successfully',
                'data' => $items
            ]);
        }

        $items = Cache::tags(['items'])->remember($cacheKey, 60 * 60, function () {
            return Item::where('availability', 'available')
                ->orderBy('created_at')
                ->get();
        });

        return response()->json([
            'message' => 'Available items fetched successfully',
            'data' => $items
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
            'name' => 'required|string|max:255',
            'availability' => 'required|in:available,unavailable',
            'image' => 'required|image|mimes:jpg,jpeg,png,gif,webp',
        ]);

        $path = $request->file('image')->store('images', 'public');

        $item = Item::create([
            'name' => $validated['name'],
            'availability' => $validated['availability'],
            'image' => $path,
        ]);

        Cache::tags(['items'])->flush();

        $employees = User::where('role', 'employee')
            ->whereNotNull('fcm_token')
            ->get();

        if ($item->availability==="available") {

            foreach ($employees as $employee) {
                app(NotificationController::class)
                    ->sendNotificationV1(
                        $employee->fcm_token,
                        'New item: ' . $item->name,
                        'A new item has been added to the menu.'
                    );
            }

        }

        return response()->json([
            'message' => 'Item created successfully',
            'data' => $item
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        return response()->json([
            'message' => 'Item details fetched successfully',
            'data' => $item
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

        $item = Item::find($id);

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'availability' => 'sometimes|in:available,unavailable',
            'image' => 'sometimes|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($item->image && Storage::disk('public')->exists($item->image)) {
                Storage::disk('public')->delete($item->image);
            }

            $path = $request->file('image')->store('images', 'public');
            $validated['image'] = $path;
        }

        $item->update($validated);

        Cache::tags(['items'])->flush();

        $employees = User::where('role', 'employee')
            ->whereNotNull('fcm_token')
            ->get();

        if ($item->availability==="available") {

            foreach ($employees as $employee) {
                app(NotificationController::class)
                    ->sendNotificationV1(
                        $employee->fcm_token,
                        'New Availability: ' . $item->name,
                        'item '.$item->name . ' is available now '
                    );
            }

        }

        return response()->json([
            'message' => 'Item updated successfully',
            'data' => $item
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

        $item = Item::find($id);

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        if ($item->image && Storage::disk('public')->exists($item->image)) {
            Storage::disk('public')->delete($item->image);
        }

        $item->delete();

        Cache::tags(['items'])->flush();

        return response()->json([
            'message' => 'Item deleted successfully'
        ]);
    }

}
