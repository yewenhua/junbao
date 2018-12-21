<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Models\Discount;
use JWTAuth;
use UtilService;

class SettingController extends Controller
{
    const AJAX_SUCCESS = 0;
    const AJAX_FAIL = -1;
    const AJAX_NO_DATA = 10001;
    const AJAX_NO_AUTH = 99999;

    public function index(Request $request){
        $uid = $request->input('uid');
        if($uid == 'all'){
            $discount = Discount::whereNull('deleted_at')->where('uid', 0)->first();
        }
        else{
            $discount = Discount::whereNull('deleted_at')->where('uid', $uid)->first();
        }

        if($discount){
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $discount);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }

    public function discount(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $roles = $user->roles;
        $flag = false;
        foreach ($roles as $role){
            if($role->name == '管理员'){
                $flag = true;
                break;
            }
        }

        if($flag) {
            $id = $request->input('id');
            $uid = $request->input('uid');
            $value = $request->input('value');

            $this->validate(request(), [
                'value' => 'required|min:1'
            ]);

            if ($id) {
                $obj = Discount::find($id);
                $obj->id = $id;
                $obj->value = $value;
                $res = $obj->save();
            } else {
                $param = request(['value']);
                $param['uid'] = $uid != 'all' ? $uid : 0;
                $res = Discount::create($param);
            }

            if ($res) {
                return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
            } else {
                return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '没有权限', '');
        }
    }

    public function statistic(Request $request){
        $data = array();
        return view('statistic', $data);
    }
}
