<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class AiHocController extends Controller
{

    public function receiveVoice(Request $request){

        $validated = $request->validate([
            'id' => 'required|exists:orders,id',
            'voice' => 'required|file|mimes:mp3,wav,ogg,aac,m4a|max:20480', // 20MB max
        ]);

        $order = Order::find($validated['id']);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found',
                'order_id' => $validated['id'],
            ], 403);
        }

        $file = $request->file('voice');

        if (!$file->isValid()) {
            return response()->json([
                'message' => 'Uploaded file is not valid.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $filename = time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('voices/', $filename, 'public');

        if ($order->voice && Storage::disk('public')->exists($order->voice)) {
            Storage::disk('public')->delete($order->voice);
        }

        $order->update([
            'voice' => $path,
        ]);

        return response()->json([
            'message' => 'Voice saved successfully',
            'order_id' => $order->id,
            'voice_url' => $order->voice_url,
        ],200);
    }
}
