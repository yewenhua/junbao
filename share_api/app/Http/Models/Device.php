<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;  //添加软删除

class Device extends Model
{
    use SoftDeletes;

    protected  $table = 'devices';
    protected $fillable = ['type', 'uid', 'brand', 'sn', 'isopen', 'category', 'address', 'location', 'active_time']; //批量赋值
    protected  $dates = ['deleted_at'];  //添加软删除

    public function findByDid($did)
    {
        return $this->where('id', $did)->whereNull('deleted_at')->where('isopen', 1)->first();
    }

    public function findBySn($sn)
    {
        return $this->where('sn', $sn)->whereNull('deleted_at')->where('isopen', 1)->first();
    }

    public function isSnExist($sn)
    {
        return $this->where('sn', $sn)->withTrashed()->first();
    }

    //设备的所有价格模板
    public function ptpls(){
        return $this->belongsToMany('App\Http\Models\Pricetpl', 'device_ptpl', 'device_id', 'ptpl_id')->withTimestamps();
    }

    //给设备分配价格模板
    public function grantPtpl($ptpl){
        return $this->ptpls()->save($ptpl);
    }

    //取消设备分配价格模板
    public function deletePtpl($ptpl){
        return $this->ptpls()->detach($ptpl);
    }
}
