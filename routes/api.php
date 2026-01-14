<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CotizadorController;
use App\Http\Controllers\Api\CalendarioChile;
use App\Http\Controllers\SytemEvents;
use App\Http\Middleware\VerifyInternalKey;


Route::post('/cotizadores/geocodificar', [CotizadorController::class, 'geocodificar']);


Route::get('/chile/holidays/2026', [CalendarioChile::class, 'index']);

Route::post('/system-events', [SytemEvents::class, 'store'])
    ->middleware(VerifyInternalKey::class);