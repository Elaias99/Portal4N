<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CotizadorController;
use App\Http\Controllers\Api\CalendarioChile;


Route::post('/cotizadores/geocodificar', [CotizadorController::class, 'geocodificar']);


Route::get('/chile/holidays/2026', [CalendarioChile::class, 'index']);

