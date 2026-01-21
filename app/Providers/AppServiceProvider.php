<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Banco;
use App\Models\TipoCuenta;
use Carbon\Carbon;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('es');
        
        View::composer('cobranzas._modal_create_cobranza', function ($view) {
            $view->with([
                'bancos' => Banco::orderBy('nombre')->get(),
                'tipoCuentas' => TipoCuenta::orderBy('nombre')->get(),
            ]);
        });
    }

}
