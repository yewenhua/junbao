<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected  $table = 'orders';
    protected $fillable = [
        'orderid', 'openid', 'money', 'signal_id', 'tpl_name', 'status', 'pay_no', 'pay_time', 'device_id', 'cash_status', 'uid'
    ];

    public function findByOpenid($openid)
    {
        return $this->where('openid', $openid)->whereNull('deleted_at')->get();
    }

    public function findByOrderid($orderid)
    {
        return $this->where('orderid', $orderid)->whereNull('deleted_at')->first();
    }

    public function findByStatus($status)
    {
        return $this->where('status', $status)->whereNull('deleted_at')->get();
    }

    public function chnageStatus($status, $orderid)
    {
        return $this->where('orderid', $orderid)->whereNull('deleted_at')
            ->update([
                "status"=>$status
            ]);
    }

    public function findByDid($did)
    {
        return $this->where('device_id', $did)->whereNull('deleted_at')->get();
    }

    public function findTakeStatus($status)
    {
        return $this->where('cash_status', $status)->whereNull('deleted_at')->get();
    }
}
