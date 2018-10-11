<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;  //添加软删除

class Cases extends Model
{
    use SoftDeletes;

    protected  $table = 'cases';
    protected $fillable = ['title', 'description', 'content', 'img', 'type']; //批量赋值
    protected  $dates = ['deleted_at'];  //添加软删除
}
