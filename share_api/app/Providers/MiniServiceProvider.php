<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\MiniService;

class MiniServiceProvider extends ServiceProvider {

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    public function register()
    {
        /*
         * 单例模式
        $this->app->singleton('MiniService', function () {
            return new MiniService();
        });
        */

        $this->app->bind('MiniService', function ($app) {
            return new MiniService();
        });
    }
}