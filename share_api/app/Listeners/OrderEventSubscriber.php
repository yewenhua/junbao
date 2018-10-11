<?php

namespace App\Listeners;
use Mail;

class OrderEventSubscriber
{
    /**
     * 处理用户订单事件。
     */
    public function onOrderShipped($event) {
        Mail::send('mail', ['name'=>'event subscribe '], function($message){
            $message->from('ye_goodluck@aliyun.com', 'cat');
            $message->subject('have a subscribe');
            $message->to('2574522520@qq.com');
        });
    }

    /**
     * 为订阅者注册监听器。
     *
     * @param  Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'App\Events\OrderShipped',
            'App\Listeners\OrderEventSubscriber@onOrderShipped'
        );
    }

}