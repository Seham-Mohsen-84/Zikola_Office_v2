<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAiHocToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('AI-HOC-TOKEN');

        if (!$token) {
            return response()->json(['error' => 'Missing AI_HOC_TOKEN header'], 400);
        }

        if ($token !== config('services.ai_hoc.token')) {
            return response()->json(['error' => 'Invalid AI_HOC_TOKEN value'], 401);
        }

        return $next($request);
    }
}
