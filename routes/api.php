<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CotizadorController;

Route::post('/cotizadores/geocodificar', [CotizadorController::class, 'geocodificar']);
