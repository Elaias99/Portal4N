<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SessionDebugMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Log antes de procesar el request
        Log::info('➡️ [SessionDebug] Incoming request', [
            'method'   => $request->method(),
            'path'     => $request->path(),
            'full_url' => $request->fullUrl(),
            'session_flash' => session()->get('_flash'),   // solo el sistema de flashes
            'session_keys'  => array_keys(session()->all()) // nombres de claves en sesión

        ]);

        $response = $next($request);

        // Log después de procesar el request (y antes de enviar la respuesta)
        Log::info('⬅️ [SessionDebug] Outgoing response', [
            'method'   => $request->method(),
            'path'     => $request->path(),
            'full_url' => $request->fullUrl(),
            'session_flash' => session()->get('_flash'),   // solo el sistema de flashes
            'session_keys'  => array_keys(session()->all()) // nombres de claves en sesión

        ]);

        return $response;
    }
}
