<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;  //添加软删除

class Pricetpl extends Model
{
    use SoftDeletes;

    protected  $table = 'pricetpl';
    protected $fillable = ['ptype', 'uid', 'signal_id', 'price', 'name', 'description', 'is_admin']; //批量赋值
    protected  $dates = ['deleted_at'];  //添加软删除

    public function openList(){
        return $this->whereNull('deleted_at')->get();
    }

    public function findByType($type)
    {
        return $this->where('ptype', $type)->whereNull('deleted_at')->get();
    }

    //价格模板属于哪个设备
    public function devices(){
        return $this->belongsToMany('App\Http\Models\Device', 'device_ptpl', 'ptpl_id', 'device_id')->withTimestamps();
    }
}
