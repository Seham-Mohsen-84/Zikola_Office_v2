<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth('api')->user();

        if (!in_array($user->role, ['admin', 'barista'])) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $orders = Order::with(['user','item'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($user->role === 'barista') {
            $orders = $orders->filter(function ($order) use ($user) {
                return $order->user && $order->user->room->branch->name === $user->room->branch->name;
            })->values();
        }

        return response()->json([
            'message' => 'All orders fetched successfully',
            'count' => $orders->count(),
            'orders' => $orders
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth('api')->user();

        if (!in_array($user->role, ['admin', 'employee'])) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $today=now()->startOfDay();
        $defaultLimit =5;

        if(!$user->updated_at || !$user->updated_at->isSameDay($today)){
            $user->update([
                'orders_limit'=>$defaultLimit,
                'updated_at'=>$today
            ]);
        }

        if ($user->orders_limit <= 0){
            return  response()->json([
                "message"=> 'Limit reached',
            ] , 403);
        }

        $validated = $request->validate([
            'item_id' => 'required|uuid|exists:items,id',
            'number_of_sugar_spoons' => 'nullable|integer|min:0',
            'order_notes' => 'nullable|string',
        ]);

        $validated['user_id'] = $user->id;

        $order = Order::create($validated);

        try {
            $payload = [
                'order_id' => $order->id,
                'item_name' => $order->item->name ?? null,
                'user_name' => $order->user->name ?? null,
                'room' => $order->user->room->name ?? null,
                'number_of_sugar_spoons' => $order->number_of_sugar_spoons,
                'order_notes' => $order->order_notes,
            ];

            Http::withHeaders([
                'AI-HOC-TOKEN' => config('services.ai_hoc.token'),
                'Accept' => 'application/json'
            ])->post('https://n8n.srv1033285.hstgr.cloud/webhook/4e2367b6-fb41-44e2-a2cd-25d73fc2628a', $payload);
        } catch (\Exception $e) {
            Log::error('AI HOC Trigger failed: ' . $e->getMessage());
        }

        $user->decrement('orders_limit');

        $baristas = User::where('role', 'barista')->get();

        foreach ($baristas as $barista) {

            if (!$barista) {
                return response()->json([
                    'message' => 'No Barista ',
                ], 400);
            }

            if (!$barista->fcm_token) continue;

            if ($barista->room->branch->name === $user->room->branch->name || $user->room->branch->name === 'all'){

                app(NotificationController::class)
                    ->sendNotificationV1(
                        $barista->fcm_token,
                        'New Order ' . $order->item->name,
                        'New Order From ' . $order->user->name
                    );
            }
        }

        return response()->json([
            'message' => 'Order created successfully',
            'data' => $order
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $order = Order::with(['user', 'item'])->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $user = auth('api')->user();

        if ($user->role === 'barista' || $user->role === 'admin'|| $order->user_id === $user->id) {
            return response()->json([
                'message' => 'Order details fetched successfully',
                'data' => $order,
            ]);
        }

        return response()->json(['message' => 'Access denied'], 403);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = auth('api')->user();

        if (!in_array($user->role, ['admin', 'employee'])) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->status !== 'waiting') {
            return response()->json(['message' => 'Cannot edit order unless it is waiting'], 403);
        }

        $validated = $request->validate([
            'item_id' => 'sometimes|exists:items,id',
            'number_of_sugar_spoons' => 'nullable|integer|min:0',
            'order_notes' => 'nullable|string',
        ]);

        $order->update($validated);

        $baristas = User::where('role', 'barista')->get();

        foreach ($baristas as $barista) {
            if (!$barista) {
                return response()->json([
                    'message' => 'No Barista ',
                ], 400);
            }

            if (!$barista->fcm_token) continue;

            if ($barista->room->branch->name === $user->room->branch->name || $user->room->branch->name === 'all') {
                app(NotificationController::class)
                    ->sendNotificationV1(
                        $barista->fcm_token,
                        'Updated Order ' . $order->item->name,
                        'Order Updated From ' . $order->user->name
                    );
            }
        }

        return response()->json([
            'message' => 'Order updated successfully',
            'data' =>$order
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = auth('api')->user();

        if (!in_array($user->role, ['admin', 'employee'])) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->voice && Storage::disk('public')->exists($order->voice)) {
            Storage::disk('public')->delete($order->voice);
        }


        if ($order->status !== 'waiting') {
            return response()->json(['message' => 'Cannot delete order unless it is waiting'], 403);
        }

        $order->delete();

        $baristas = User::where('role', 'barista')->get();

        foreach ($baristas as $barista) {
            if (!$barista) {
                return response()->json([
                    'message' => 'No Barista ',
                ], 400);
            }

            if (!$barista->fcm_token) continue;

            if ($barista->room->branch->name === $user->room->branch->name || $user->room->branch->name === 'all') {
                app(NotificationController::class)
                    ->sendNotificationV1(
                        $barista->fcm_token,
                        'Deleted Order ' . $order->item->name,
                        'Order Deleted From ' . $order->user->name
                    );
            }
        }

        $user->increment('orders_limit');

        return response()->json([
            'message' => 'Order deleted successfully'
        ]);
    }

    /**
     * Update order status (for barista only).
     */
    public function updateStatus(Request $request, $id)
    {
        $user = auth('api')->user();

        if (!in_array($user->role, ['admin', 'barista'])) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:waiting,onprogress,completed,cancelled'
        ]);

        $order->update(['status' => $validated['status']]);

        if($order->status === 'cancelled'){
            $order->user->increment('orders_limit');
        }


        if (!$order->user->fcm_token) {
            return response()->json([
                'message' => 'This User Don\'t have FCM Token',
            ]);
        }

        app(NotificationController::class)
            ->sendNotificationV1(
                $order->user->fcm_token,
                'Status updated order ' . $order->name,
                'Your Order ' . $order->item->name.' Status Is ' . $validated['status']
            );


        return response()->json([
            'message' => 'Order status updated successfully',
            'data' => $order
        ]);
    }

    /**
     * Display orders for the authenticated user.
     */
    public function myOrders()
    {
        $user = auth('api')->user();

        $orders = Order::with('item')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Your orders fetched successfully',
            'data' => $orders
        ]);
    }

    public function updateOrdersLimit(Request $request ,$id)
    {
        $admin = auth('api')->user();

        if ($admin->role !== 'admin') {
            return response()->json([
                'message' => 'Access denied. Only admin can update limits.'
            ], 403);
        }

        $validated = $request->validate([
            'orders_limit' => 'required|integer|min:0',
        ]);

        $user=User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }

        $user->update([
            'orders_limit' => $validated['orders_limit']
        ]);

        return response()->json([
            'message' => 'Order limit updated successfully for ' . $user->name,
            'data' => $user
        ], 200);
    }
    public function updateLimit(Request $request){
        $user = auth('api')->user();

        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Access denied. Only admin can update limits.'
            ], 403);
        }

        $validated = $request->validate([
            'orders_limit' => 'required|integer|min:0',
        ]);

        $employees = User::get();

        if ($employees->isEmpty()) {
            return response()->json([
                'message' => 'No Users Found.'
            ], 404);
        }

        foreach ($employees as $employee) {
            $employee->update([
                'orders_limit' => $validated['orders_limit']
            ]);
        }

        return response()->json([
            'message' => 'Order limits updated successfully for all Users.',
        ], 200);
    }

    public function ordersLimit()
    {
        $user = auth('api')->user();

        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Access denied. Only admin can update limits.'
            ], 403);
        }
        $orders_limit =User::where('role', 'employee')->first()->orders_limit;

        return response()->json([
            'orders_limit'=>$orders_limit,
        ]);
    }
    public function  deleteOrders()
    {
        $user = auth('api')->user();

        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Access denied. Only admin can update limits.'
            ], 403);
        }

        $orders = Order::get();

        if ($orders->isEmpty()) {
            return response()->json([
                'message' => 'No orders Found.'
            ], 404);
        }

        foreach ($orders as $order) {

            if (in_array($order->status, ['cancelled', 'completed'])) {

                if ($order->voice && Storage::disk('public')->exists($order->voice)) {
                    Storage::disk('public')->delete($order->voice);
                }
                $order->delete();

            }
        }

        return response()->json([
            'message'=>'Orders deleted successfully',
        ]);
    }
}
