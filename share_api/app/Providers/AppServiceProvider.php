<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

//任务失败的时候触发某个事件追加
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobFailed;

//add by laravel 5.4
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        ////任务失败的时候触发某个事件追加
        Queue::failing(function (JobFailed $event) {
            // $event->connectionName
            // $event->job
            // $event->exception
        });

        //laravel 5.4 改变了默认的数据库字符集，现在utf8mb4包括存储emojis支持。如果你运行MySQL v5.7.7或者更高版本，则不需要做任何事情。
        //当你试着在一些MariaDB或者一些老版本的的MySQL上运行 migrations 命令时，你可能会碰到下面这个错误：
        Schema::defaultStringLength(191);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
