<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
use Illuminate\Database\Eloquent\SoftDeletes;  //添加软删除

class Role extends Model
{
    use SoftDeletes;
    protected  $table = 'roles';
    protected $fillable = ['name', 'desc']; //批量赋值
    protected  $dates = ['deleted_at'];  //添加软删除

    //角色的所有权限
    public function permissions(){
        return $this->belongsToMany('App\Permission', 'permission_role', 'role_id', 'permission_id')->withPivot('add_permission', 'delete_permission', 'update_permission', 'read_permission')->withTimestamps();
    }

    public function users(){
        return $this->belongsToMany('App\User', 'role_user', 'role_id', 'user_id')->withTimestamps();
    }

    //给角色分配权限
    public function grantPermission($permission){
        return $this->permissions()->save($permission);
    }

    public function grantPermissionDetail($value, $permission){
        return DB::table('permission_role')
            ->where('role_id', '=', $this->id)
            ->where('permission_id', '=', $permission->id)
            ->update($value);
    }

    //取消角色分配权限
    public function deletePermission($permission){
        return $this->permissions()->detach($permission);
    }

    //是否有某项权限
    public function hasPermission($permission){
        return $this->permissions->contains($permission);
    }
}
