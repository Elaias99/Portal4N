<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyInternalKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $internalKey = $request->header('X-INTERNAL-KEY');

        if (!$internalKey || $internalKey !== config('services.internal.key')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        return $next($request);
    }
}
