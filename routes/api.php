<?php

use App\Http\Controllers\Api\AiHocController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api','jwt.cookie');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api','jwt.cookie');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:api','jwt.cookie');
});

Route::prefix('users')->middleware(['auth:api','jwt.cookie', 'role:admin'])->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::get('/trashed', [UserController::class, 'trashed']);
    Route::post('/restore/{id}', [UserController::class, 'restore']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
});

Route::prefix('orders')->middleware(['auth:api','jwt.cookie'])->group(function () {
    Route::get('/', [OrderController::class, 'index'])->middleware('role:admin,barista');
    Route::post('/', [OrderController::class, 'store'])->middleware('role:admin,employee');
    Route::get('/my-orders', [OrderController::class, 'myOrders']);
    Route::get('/limit', [OrderController::class, 'ordersLimit'])->middleware('role:admin');
    Route::get('/{id}', [OrderController::class, 'show']);
    Route::delete('/deleteOrders', [OrderController::class, 'deleteOrders'])->middleware('role:admin');
    Route::put('/status/{id}', [OrderController::class, 'updateStatus'])->middleware('role:barista,admin');
    Route::put('/edit/{id}', [OrderController::class, 'update'])->middleware('role:admin,employee');
    Route::delete('/{id}', [OrderController::class, 'destroy'])->middleware('role:admin,employee');
    Route::put('/limit/{id}', [OrderController::class, 'updateOrdersLimit'])->middleware('role:admin');
    Route::put('/limit', [OrderController::class, 'updateLimit'])->middleware('role:admin');
});

Route::prefix('items')->middleware(['auth:api','jwt.cookie'])->group(function () {
    Route::get('/', [ItemController::class, 'index']);
    Route::get('/{id}', [ItemController::class, 'show']);
    Route::post('/', [ItemController::class, 'store'])->middleware('role:admin');
    Route::post('/{id}', [ItemController::class, 'update'])->middleware('role:admin');
    Route::delete('/{id}', [ItemController::class, 'destroy'])->middleware('role:admin');
});

Route::prefix('branches')->middleware(['auth:api', 'role:admin','jwt.cookie'])->group(function () {
    Route::get('/', [BranchController::class, 'index']);
    Route::post('/', [BranchController::class, 'store']);
    Route::get('/count', [RoomController::class, 'count']);
    Route::get('/{id}', [BranchController::class, 'show']);
    Route::put('/{id}', [BranchController::class, 'update']);
    Route::delete('/{id}', [BranchController::class, 'destroy']);
});

Route::prefix('rooms')->middleware(['auth:api', 'role:admin','jwt.cookie'])->group(function () {
    Route::get('/', [RoomController::class, 'index']);
    Route::post('/', [RoomController::class, 'store']);
    Route::get('/count', [RoomController::class, 'count']);
    Route::get('/{id}', [RoomController::class, 'show']);
    Route::put('/{id}', [RoomController::class, 'update']);
    Route::delete('/{id}', [RoomController::class, 'destroy']);
});

Route::middleware('verify.aihoc')->post('/ai/voice-callback', [AiHocController::class, 'receiveVoice']);


