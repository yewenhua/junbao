<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;  //添加软删除

class Ptype extends Model
{
    use SoftDeletes;

    protected  $table = 'ptype';
    protected $fillable = ['name', 'isopen']; //批量赋值
    protected  $dates = ['deleted_at'];  //添加软删除

    public function openList(){
        return $this->whereNull('deleted_at')->where('isopen', 1)->get();
    }
}
