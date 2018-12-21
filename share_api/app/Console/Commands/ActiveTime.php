<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Device;
use App\Order;

class ActiveTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activetime:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'activetime update';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::table('devices')->whereNull('deleted_at')->whereNull('active_time')->orderBy('id', 'asc')->chunk(100, function($lists) {
            if($lists){
                foreach ($lists as $device) {
                    $order = Order::select(['pay_time'])->whereNull('deleted_at')->where('device_id', $device->id)->where('status', 1)->where('money', '>=', 1)->orderBy('id', 'asc')->first();
                    if($order){
                        DB::table('devices')->where('id', $device->id)->update([
                            'active_time' => $order->pay_time
                        ]);
                    }
                }
            }
            else{
                return false;
            }
        });
    }
}
