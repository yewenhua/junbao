<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\WxpayService;

class WxpayServiceProvider extends ServiceProvider {

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
        $this->app->singleton('WxpayService', function () {
            return new WxpayService();
        });
        */

        $this->app->bind('WxpayService', function ($app) {
            return new WxpayService();
        });
    }
}