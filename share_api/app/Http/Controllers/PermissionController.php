<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use UtilService;

class PermissionController extends Controller
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

        $total = \App\Permission::where('name', 'like', $like)
            ->orderBy('id', 'desc')
            ->get();

        $permissions = \App\Permission::where('name', 'like', $like)
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        if($permissions){
            $res = array(
                'data'=>$permissions,
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

    public function store(Request $request){
        $id = $request->input('id');
        $name = $request->input('name');
        $desc = $request->input('desc');
        $this->validate(request(), [
            'name'=>'required|min:1',
            'desc'=>'required'
        ]);

        if($id){
            $permission = \App\Permission::find($id);
            $permission->name = $name;
            $permission->desc = $desc;
            $res = $permission->save();
        }
        else{
            $res = \App\Permission::create(request(['name', 'desc']));
        }

        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function permissions(){
        $permissions = \App\Permission::all();
        if($permissions){
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $permissions);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }

    public function delete(Request $request){
        $id = $request->input('id');
        $this->validate(request(), [
            'id'=>'required|min:1'
        ]);

        $permission = \App\Permission::find($id);
        $res = $permission->delete();
        if($permission && $res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function batchdelete(Request $request){
        $idstring = $request->input('idstring');
        $this->validate(request(), [
            'idstring'=>'required|min:1'
        ]);

        $idarray = explode(',', $idstring);
        $res = \App\Permission::whereIn('id', $idarray)->delete();;
        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }
}
