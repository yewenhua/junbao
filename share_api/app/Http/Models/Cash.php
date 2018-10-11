<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;  //添加软删除

class Cash extends Model
{
    use SoftDeletes;
    protected  $table = 'cash';
    protected $fillable = ['money', 'uid', 'status']; //批量赋值
    protected  $dates = ['deleted_at'];  //添加软删除

    public function findByUid($uid)
    {
        return $this->where('uid', $uid)->whereNull('deleted_at')->get();
    }
}
