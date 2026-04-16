<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CotizadorController;
use App\Http\Controllers\Api\CalendarioChile;
use App\Http\Controllers\Api\LatamTrackingApiController;
use App\Http\Controllers\SytemEvents;
use App\Http\Controllers\Api\AuthController;
use App\Http\Middleware\VerifyInternalKey;


Route::post('/cotizadores/geocodificar', [CotizadorController::class, 'geocodificar']);


Route::get('/chile/holidays/2026', [CalendarioChile::class, 'index']);

Route::post('/system-events', [SytemEvents::class, 'store'])
    ->middleware(VerifyInternalKey::class);

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->get('/me', function (\Illuminate\Http\Request $request) {
    return response()->json([
        'user' => [
            'id' => $request->user()->id,
            'name' => $request->user()->name,
            'email' => $request->user()->email,
        ],
    ]);
});

Route::middleware('auth:sanctum')->get('/latam/trackings', [LatamTrackingApiController::class, 'index']);