<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;  //添加软删除

class Banner extends Model
{
    use SoftDeletes;

    protected  $table = 'banner';
    protected $fillable = ['title', 'sort', 'url', 'img', 'isopen']; //批量赋值
    protected  $dates = ['deleted_at'];  //添加软删除
}
