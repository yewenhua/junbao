<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mail;
use Illuminate\Support\Facades\Log;

class SendShipmentNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  OrderShipped  $event
     * @return void
     */
    public function handle(OrderShipped $event)
    {
        // 使用 $event->order 来访问 order ...

        $orderObj = $event->order;
        //$orderData = $orderObj['attributes'];
        //Log::info('hello world'.var_export($orderData['order_id'], true));
        Mail::send('mail', ['name'=>'OrderShipped '], function($message){
            $message->from('ye_goodluck@aliyun.com', 'cat');
            $message->subject('have a order');
            $message->to('2574522520@qq.com');
        });
    }
}
