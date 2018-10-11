<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use UtilService;
use App\Role;

class RoleController extends Controller
{
    const AJAX_SUCCESS = 0;
    const AJAX_FAIL = -1;
    const AJAX_NO_DATA = 10001;
    const AJAX_NO_AUTH = 99999;

    public function index(Request $request){
        $page = $request->input('page');
        $limit = $request->input('num');
        $search = $request->input('search');
        $offset = ($page - 1) * $limit;
        $like = '%'.$search.'%';

        $total = \App\Role::where('name', 'like', $like)
            ->orderBy('id', 'desc')
            ->get();

        $roles = \App\Role::where('name', 'like', $like)
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        if($roles){
            $res = array(
                'data'=>$roles,
                'total'=>count($total)
            );
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }

    public function create(){

    }

    //保存角色
    public function store(Request $request){
        $id = $request->input('id');
        $name = $request->input('name');
        $desc = $request->input('desc');
        $this->validate(request(), [
            'name'=>'required|min:1',
            'desc'=>'required'
        ]);

        if($id){
            $role = \App\Role::find($id);
            $role->name = $name;
            $role->desc = $desc;
            $res = $role->save();
        }
        else{
            $res = \App\Role::create(request(['name', 'desc'])); //save 和 create 的不同之处在于 save 接收整个 Eloquent 模型实例而 create 接收原生 PHP 数组
        }

        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    //路由模型绑定admin实例
    public function permission(\App\Role $role){
        $permissions = \App\Permission::all(); // all permissions
        $myPermissions = $role->permissions; //带括号的是返回关联对象实例，不带括号是返回动态属性

        //compact 创建一个包含变量名和它们的值的数组
        $data = compact('permissions', 'myPermissions', 'role');
        return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $data);
    }

    //储存角色权限
    public function storePermission(\App\Role $role){
        //验证
        $detail = request('detail');
        //获取权限参数
        $permissions = \App\Permission::findMany(request('permissions'));
        //当前角色权限
        $myPermissions = $role->permissions;

        //要增加的角色
        $addPermissions = $permissions->diff($myPermissions);
        foreach ($addPermissions as $permission){
            $role->grantPermission($permission);

            $value = [];
            foreach ($detail as $item){
                foreach ($item['type'] as $v){
                    if($permission->id == $item['id']){
                        $key = $v.'_permission';
                        $value[$key] = 1;
                    }
                }
            }
            $role->grantPermissionDetail($value, $permission);
        }

        //要删除的角色
        $deletePermissions = $myPermissions->diff($permissions);
        foreach ($deletePermissions as $permission){
            $role->deletePermission($permission);
        }

        //要修改的具体角色权限
        foreach ($permissions as $permission) {
            foreach ($myPermissions as $myPermission) {
                if($permission->id == $myPermission->id){
                    $value = array(
                        'add_permission' => 0,
                        'delete_permission' => 0,
                        'update_permission' => 0,
                        'read_permission' => 0
                    );
                    foreach ($detail as $item){
                        foreach ($item['type'] as $v){
                            if($permission->id == $item['id']){
                                $key = $v.'_permission';
                                $value[$key] = 1;
                            }
                        }
                    }
                    $role->grantPermissionDetail($value, $permission);
                }
            }
        }

        return UtilService::format_data(self::AJAX_SUCCESS, '保存成功', []);
    }

    public function delete(Request $request){
        $id = $request->input('id');
        $this->validate(request(), [
            'id'=>'required|min:1'
        ]);

        $role = \App\Role::find($id);
        $res = $role->delete();
        if($role && $res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function lists(){
        $lists = Role::whereNull('deleted_at')->get();
        if($lists){
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $lists);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }
}
