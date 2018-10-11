<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use UtilService;
use JWTAuth;
use App\Http\Models\News;

class NewsController extends Controller
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

        $total = News::where('title', 'like', $like)
            ->orderBy('id', 'desc')
            ->get();

        $lists = News::where('title', 'like', $like)
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
        $this->validate(request(), [
            'title'=>'required|min:1',
            'description'=>'required',
            'content'=>'required',
            'img'=>'required'
        ]);

        if($id){
            $obj = News::find($id);
            $obj->title = $title;
            $obj->description = $description;
            $obj->content = $content;
            $obj->img = $img;
            $res = $obj->save();
        }
        else{
            $res = News::create(request(['title', 'description', 'content', 'img']));
        }

        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function news(){
        $news = News::all();
        if($news){
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $news);
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

        $obj = News::find($id);
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
        $res = News::whereIn('id', $idarray)->delete();;
        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function home(){
        $lists = News::whereNull('deleted_at')
            ->orderBy('id', 'desc')
            ->limit(4)
            ->get();
        if($lists){
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $lists);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }

    public function detail(Request $request){
        $id = $request->input('id');
        $data = News::where('id', $id)
            ->whereNull('deleted_at')
            ->first();
        if($data){
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $data);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }
}
