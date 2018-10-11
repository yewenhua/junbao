<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use UtilService;
use JWTAuth;
use App\Http\Models\Tree;

class TreeController extends Controller
{
    const AJAX_SUCCESS = 0;
    const AJAX_FAIL = -1;
    const AJAX_NO_DATA = 10001;
    const AJAX_NO_AUTH = 99999;

    public function lists()
    {
        $userObj = JWTAuth::parseToken()->authenticate();
        $treeObj = new Tree();
        $data = $treeObj->lists();

        if ($data) {
            $res = array(
                'user' => $userObj,
                'data' => $data
            );
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $res);
        } else {
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }

    public function insert(Request $request){
        $userObj = JWTAuth::parseToken()->authenticate();
        $treeObj = new Tree();

        $label = $request->input('label');
        $parent_path = $request->input('parent_path');
        $sort = $request->input('sort');
        $img_url = $request->input('img_url');
        $is_open = $request->input('is_open');
        $is_root = $request->input('is_root');
        $content = $request->input('content');
        $description = $request->input('description');

        if(strpos($parent_path, '/') !== false){
            $pathArray = explode('/', $parent_path);
            $level = count($pathArray) + 1;
        }
        else{
            $level = 2;
        }

        $id = $treeObj->insert([
            'label' => $label,
            'level' => $level,
            'img_url' => $img_url,
            'is_root' => $is_root,
            'is_open' => $is_open,
            'content' => $content,
            'description' => $description,
            'sort' => $sort
        ]);

        if($id){
            $params = [];
            $params['updated_at'] = date('Y-m-d H:i:s', time());
            if($is_root){
                $path = $id;
            }
            else{
                $path = $parent_path."/".$id;
            }

            $row = $treeObj->rowById($id);
            if($row){
                $row->path = $path;
                $res = $row->save();
                if($res){
                    return UtilService::format_data(self::AJAX_SUCCESS, '插入成功', ['id'=>$id]);
                }
                else{
                    return UtilService::format_data(self::AJAX_FAIL, '插入失败', '');
                }
            }
            else{
                return UtilService::format_data(self::AJAX_FAIL, '插入失败', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '插入失败', '');
        }
    }

    public function update(Request $request){
        $userObj = JWTAuth::parseToken()->authenticate();
        $treeObj = new Tree();
        $label = $request->input('label');
        $sort = $request->input('sort');
        $id = $request->input('id');
        $img_url = $request->input('img_url');
        $is_open = $request->input('is_open');
        $content = $request->input('content');
        $description = $request->input('description');
        $row = $treeObj->rowById($id);
        if($row){
            $row->label = $label;
            $row->sort = $sort;
            $row->is_open = $is_open;
            $row->content = $content;
            $row->description = $description;
            if($img_url){
                $row->img_url = $img_url;
            }
            $res = $row->save();
            if($res){
                return UtilService::format_data(self::AJAX_SUCCESS, '修改成功', ['id'=>$id]);
            }
            else{
                return UtilService::format_data(self::AJAX_FAIL, '修改失败', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '数据出错', '');
        }
    }

    public function delete(Request $request){
        $userObj = JWTAuth::parseToken()->authenticate();
        $treeObj = new Tree();
        $id = $request->input('id');
        $row = $treeObj->rowById($id);
        if($row){
            $row->deleted_at = date('Y-m-d H:i:s', time());
            $res = $row->save();
            if($res){
                return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', ['id'=>$id]);
            }
            else{
                return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '数据出错', '');
        }
    }

    public function singlefile(Request $request)
    {
        $file = $request->file('file');
        $name = date('Ymd');
        $path = $file->store($name,'uploads');
        $path = '/uploads/'.$path;

        $bool = true;
        if($bool) {
            return UtilService::format_data(self::AJAX_SUCCESS, '保存成功', [
                "path" => $path
            ]);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '保存失败', []);
        }
    }

    public function column(Request $request){
        $level = $request->input('level');
        $obj = new Tree();
        $lists = $obj->column($level);
        if($lists){
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $lists);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }

    public function children(Request $request){
        $path = $request->input('path');
        $obj = new Tree();
        $lists = $obj->children($path);
        if($lists){
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $lists);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }

    public function home(){
        $level = 2;
        $obj = new Tree();
        $lists = $obj->column($level);
        if($lists){
            foreach ($lists as $key=>$item){
                $children = $obj->children($item['path']);
                $lists[$key]['children'] = $children ? $children : array();
            }
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $lists);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }
}
