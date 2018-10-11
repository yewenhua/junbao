<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;  //添加软删除

class Maintenance extends Model
{
    use SoftDeletes;

    protected  $table = 'maintenance';
    protected $fillable = ['openid', 'username', 'status']; //批量赋值
    protected  $dates = ['deleted_at'];  //添加软删除

    public function getByOpenidAndStatus($openid, $status){
        return $this->where('openid', $openid)
            ->whereNull('deleted_at')
            ->where('status', $status)
            ->first();
    }

    public function getByOpenid($openid){
        return $this->where('openid', $openid)
            ->whereNull('deleted_at')
            ->first();
    }
}
