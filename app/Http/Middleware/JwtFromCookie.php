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
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info("---- JwtFromCookie MIDDLEWARE START ----");

        Log::info("ALL COOKIES: " . json_encode($request->cookies->all()));
        Log::info("BEARER TOKEN: " . $request->bearerToken());
        Log::info("AUTH HEADER RAW: " . $request->header('Authorization'));

        $token = $request->bearerToken()
            ?? $request->cookie(config('jwt.cookie_key_name'));

        if ($token) {

            Log::info("TOKEN FOUND = " . $token);

            $request->headers->set('Authorization', 'Bearer ' . $token);

            try {
                auth()->shouldUse('api');

                $user = JWTAuth::setToken($token)->authenticate();

                Log::info("AUTH RESULT = " . json_encode($user));

                if ($user) {

                    auth()->setUser($user);

                    $request->setUserResolver(fn() => $user);

                    Log::info("USER SET SUCCESSFULLY");
                } else {
                    Log::warning("AUTHENTICATE RETURNED NULL USER");
                }

            }catch (\Exception $e) {
                Log::warning("NO TOKEN FOUND IN COOKIE OR HEADER");
            }

        } Log::info("---- JwtFromCookie MIDDLEWARE END ----");

        return $next($request);
    }
}
