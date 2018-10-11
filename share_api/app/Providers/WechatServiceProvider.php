<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\WechatService;

class WechatServiceProvider extends ServiceProvider {

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
        $this->app->singleton('WechatService', function () {
            return new WechatService();
        });
        */

        $this->app->bind('WechatService', function ($app) {
            return new WechatService();
        });
    }
}