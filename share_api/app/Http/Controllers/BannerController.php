<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use UtilService;
use JWTAuth;
use App\Http\Models\Banner;

class BannerController extends Controller
{
    const AJAX_SUCCESS = 0;
    const AJAX_FAIL = -1;
    const AJAX_NO_DATA = 10001;
    const AJAX_NO_AUTH = 99999;

    public function index(Request $request){
        $search = $request->input('search');
        $like = '%'.$search.'%';
        $lists = Banner::where('title', 'like', $like)
            //->where('isopen', 1)
            ->whereNull('deleted_at')
            ->orderBy('sort', 'asc')
            ->get();

        if($lists){
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $lists);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }

    public function store(Request $request){
        $id = $request->input('id');
        $title = $request->input('title');
        $sort = $request->input('sort');
        $url = $request->input('url');
        $img = $request->input('img');
        $isopen = $request->input('isopen');
        $this->validate(request(), [
            'title'=>'required|min:1',
            'sort'=>'required',
            'url'=>'required',
            'isopen'=>'required',
            'img'=>'required'
        ]);

        if($id){
            $obj = Banner::find($id);
            $obj->title = $title;
            $obj->url = $url;
            $obj->sort = $sort;
            $obj->isopen = $isopen;
            $obj->img = $img;
            $res = $obj->save();
        }
        else{
            $res = Banner::create(request(['title', 'url', 'sort', 'img', 'isopen']));
        }

        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function delete(Request $request){
        $id = $request->input('id');
        $this->validate(request(), [
            'id'=>'required|min:1'
        ]);

        $obj = Banner::find($id);
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
        $res = Banner::whereIn('id', $idarray)->delete();;
        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function home(){
        $lists = Banner::where('isopen', 1)
            ->whereNull('deleted_at')
            ->orderBy('sort', 'asc')
            ->get();
        if($lists){
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $lists);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }
}
