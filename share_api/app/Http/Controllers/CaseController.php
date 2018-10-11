<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use UtilService;
use JWTAuth;
use App\Http\Models\Cases;

class CaseController extends Controller
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

        $total = Cases::where('title', 'like', $like)
            ->orderBy('id', 'desc')
            ->get();

        $lists = Cases::where('title', 'like', $like)
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        if($lists){
            $res = array(
                'data'=>$lists,
                'total'=>count($total)
            );
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }

    public function store(Request $request){
        $id = $request->input('id');
        $title = $request->input('title');
        $description = $request->input('description');
        $content = $request->input('content');
        $img = $request->input('img');
        $type = $request->input('type');
        $this->validate(request(), [
            'title'=>'required|min:1',
            'description'=>'required',
            'content'=>'required',
            'type'=>'required',
            'img'=>'required'
        ]);

        if($id){
            $obj = Cases::find($id);
            $obj->title = $title;
            $obj->description = $description;
            $obj->content = $content;
            $obj->type = $type;
            $obj->img = $img;
            $res = $obj->save();
        }
        else{
            $res = Cases::create(request(['title', 'description', 'content', 'img', 'type']));
        }

        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function cases(){
        $cases = Cases::all();
        if($cases){
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $cases);
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

        $obj = Cases::find($id);
        $res = $obj->delete();
        if($obj && $res){
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
        $res = Cases::whereIn('id', $idarray)->delete();;
        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function home(){
        $lists = Cases::whereNull('deleted_at')
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();
        if($lists){
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $lists);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }
}
