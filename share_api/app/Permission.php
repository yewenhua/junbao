<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;  //添加软删除

class Permission extends Model
{
    use SoftDeletes;
    protected  $table = 'permissions';
    protected $fillable = ['name', 'desc']; //批量赋值
    protected  $dates = ['deleted_at'];  //添加软删除

    //权限属于哪个角色
    public function roles(){
        return $this->belongsToMany('App\Role', 'permission_role', 'permission_id', 'role_id')->withTimestamps();
    }
}
