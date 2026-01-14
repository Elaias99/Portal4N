<?php

namespace App\Http\Controllers;

use App\Models\SystemEvents;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SytemEvents extends Controller
{
    /**
     * Store a new system event.
     */
    public function store(Request $request): JsonResponse
    {
        // Validación mínima y flexible
        $validated = $request->validate([
            'event_type'   => 'required|string|max:100',
            'source'       => 'required|string|max:50',
            'reference_id' => 'nullable|string|max:100',
            'payload'      => 'nullable|array',
        ]);

        SystemEvents::create([
            'event_type'   => $validated['event_type'],
            'source'       => $validated['source'],
            'reference_id' => $validated['reference_id'] ?? null,
            'payload'      => $validated['payload'] ?? null,
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
        ]);

        // Respuesta simple y rápida
        return response()->json([
            'success' => true
        ], 201);
    }
}
