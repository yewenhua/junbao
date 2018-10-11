<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;  //添加软删除

class Password extends Model
{
    use SoftDeletes;

    protected  $table = 'device_pwd';
    protected $fillable = ['device_id', 'sn', 'password', 'orderid']; //批量赋值
    protected  $dates = ['deleted_at'];  //添加软删除

    public function newestPwd($did){
        return $this->where('device_id', $did)
            ->whereNull('deleted_at')
            ->orderBy('id', 'desc')
            ->first();
    }

    public function pwdByOrderid($orderid){
        return $this->where('orderid', $orderid)
            ->whereNull('deleted_at')
            ->orderBy('id', 'desc')
            ->first();
    }
}
