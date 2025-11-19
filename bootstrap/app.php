<?php

use App\Http\Middleware\JwtFromCookie;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\VerifyAiHocToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prependToGroup('api', [
            JwtFromCookie::class,
        ]);
        $middleware->alias([
            'jwt.cookie' => JwtFromCookie::class,
            'role' => RoleMiddleware::class,
            'verify.aihoc' => VerifyAiHocToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
