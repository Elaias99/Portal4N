<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Banco;
use App\Models\TipoCuenta;
use App\Services\Suscripciones\SuscripcionAjusteMensualService;
use Carbon\Carbon;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /*
         * El listado, los resúmenes y los servicios de PDF/ZIP resuelven los
         * mismos ajustes varias veces durante una solicitud. Compartir esta
         * instancia mantiene un único caché por ciclo HTTP o job.
         */
        $this->app->scoped(
            SuscripcionAjusteMensualService::class,
            fn () => new SuscripcionAjusteMensualService()
        );
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
