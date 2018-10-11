<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;  //添加软删除

class Discount extends Model
{
    use SoftDeletes;

    protected  $table = 'discount';
    protected $fillable = ['value', 'uid']; //批量赋值
    protected  $dates = ['deleted_at'];  //添加软删除

    public function findById($did)
    {
        return $this->where('id', $did)->whereNull('deleted_at')->first();
    }
}
