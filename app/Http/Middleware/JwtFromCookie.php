<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class JwtFromCookie
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info("JwtFromCookie MIDDLEWARE IS RUNNING");

        if ($token = $request->cookie(config('jwt.cookie_key_name'))) {

            Log::info("TOKEN FROM COOKIE = " . $token);

            try {
                $user = JWTAuth::setToken($token)->authenticate();

                if ($user) {

                    // لازم Laravel يعرف مين اليوزر
                    auth()->setUser($user);

                    // لازم Laravel يستخدم الجارد api
                    auth()->shouldUse('api');

                    $request->setUserResolver(function () use ($user) {
                        return $user;
                    });

                    // ⚠️ هنا بالظبط نحط اللوجز الـ 3
                    Log::info("AUTH USER AFTER SET = " . json_encode(auth()->user()));
                    Log::info("REQUEST->user() AFTER SET = " . json_encode($request->user()));
                    Log::info("JWTAuth::user() AFTER SET = " . json_encode(JWTAuth::user()));
                }

            } catch (\Exception $e) {
                Log::info("JWT ERROR: " . $e->getMessage());
            }

        } else {
            Log::info("NO COOKIE FOUND !!!");
        }

        return $next($request);
    }
}
