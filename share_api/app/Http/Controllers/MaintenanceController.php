<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use UtilService;
use App\Http\Models\Pbrand;
use App\Http\Models\Ptype;
use App\Http\Models\Pricetpl;
use App\Http\Models\Maintenance;
use Illuminate\Support\Facades\Log;
use JWTAuth;
use App\User;
use QrCode;

class MaintenanceController extends Controller
{
    const AJAX_SUCCESS = 0;
    const AJAX_FAIL = -1;
    const AJAX_NO_DATA = 10001;
    const AJAX_NO_AUTH = 99999;

    public function ptype(Request $request){
        $page = $request->input('page');
        $limit = $request->input('num');
        $search = $request->input('search');
        $offset = ($page - 1) * $limit;
        $like = '%'.$search.'%';

        $total = Ptype::where('name', 'like', $like)
            ->orderBy('id', 'desc')
            ->get();

        $lists = Ptype::where('name', 'like', $like)
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

    public function ptopen(){
        $lists = Ptype::whereNull('deleted_at')->where('isopen', 1)->get();
        if($lists){
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $lists);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }

    public function ptstore(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $id = $request->input('id');
        $name = $request->input('name');
        $isopen = $request->input('isopen');
        $this->validate(request(), [
            'name'=>'required|min:1'
        ]);

        if($id){
            $obj = Ptype::find($id);
            $obj->id = $id;
            $obj->name = $name;
            $obj->isopen = $isopen;
            $res = $obj->save();
        }
        else{
            $param = request(['name', 'isopen']);
            $res = Ptype::create($param);
        }

        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function ptbatchelete(Request $request){
        $idstring = $request->input('idstring');
        $this->validate(request(), [
            'idstring'=>'required|min:1'
        ]);

        $idarray = explode(',', $idstring);
        $res = Ptype::whereIn('id', $idarray)->delete();;
        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function pbrand(Request $request){
        $page = $request->input('page');
        $limit = $request->input('num');
        $search = $request->input('search');
        $offset = ($page - 1) * $limit;
        $like = '%'.$search.'%';

        $total = Pbrand::where('name', 'like', $like)
            ->orderBy('id', 'desc')
            ->get();

        $lists = Pbrand::where('name', 'like', $like)
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

    public function pbopen(){
        $lists = Pbrand::whereNull('deleted_at')->where('isopen', 1)->get();
        if($lists){
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $lists);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }

    public function pbstore(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $id = $request->input('id');
        $name = $request->input('name');
        $isopen = $request->input('isopen');
        $this->validate(request(), [
            'name'=>'required|min:1'
        ]);

        if($id){
            $obj = Pbrand::find($id);
            $obj->id = $id;
            $obj->name = $name;
            $obj->isopen = $isopen;
            $res = $obj->save();
        }
        else{
            $param = request(['name', 'isopen']);
            $res = Pbrand::create($param);
        }

        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function pbbatchelete(Request $request){
        $idstring = $request->input('idstring');
        $this->validate(request(), [
            'idstring'=>'required|min:1'
        ]);

        $idarray = explode(',', $idstring);
        $res = Pbrand::whereIn('id', $idarray)->delete();;
        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function pricetpl(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $roles = $user->roles;
        $flag = false;
        foreach ($roles as $role){
            if($role->name == '管理员'){
                $flag = true;
                break;
            }
        }

        $page = $request->input('page');
        $limit = $request->input('num');
        $search = $request->input('search');
        $type = $request->input('type');
        $offset = ($page - 1) * $limit;
        $like = '%'.$search.'%';


        $total = Pricetpl::where('name', 'like', $like);
        $lists = Pricetpl::where('name', 'like', $like);
        if($type != 'all'){
            $total = $total->where('ptype', $type);
            $lists = $lists->where('ptype', $type);
        }

        if(!$flag){
            $total = $total->where('uid', $user->id);
            $lists = $lists->where('uid', $user->id);
        }

        $total = $total->orderBy('id', 'desc')
            ->get();

        $lists = $lists->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        if($lists){
            foreach ($lists as $key=>$item){
                $user = User::whereNull('deleted_at')->where('id', $item->uid)->first();
                $lists[$key]->username = $user->name;
            }

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

    public function priceopen(){
        $lists = Pricetpl::whereNull('deleted_at')->get();
        if($lists){
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $lists);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }

    public function pricestore(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $roles = $user->roles;
        $flag = false;
        foreach ($roles as $role){
            if($role->name == '管理员'){
                $flag = true;
                break;
            }
        }

        $id = $request->input('id');
        $name = $request->input('name');
        $ptype = $request->input('ptype');
        $signal_id = $request->input('signal_id');
        $price = $request->input('price');
        $description = $request->input('description');
        $this->validate(request(), [
            'name'=>'required|min:1',
            'ptype'=>'required',
            'signal_id'=>'required',
            'price'=>'required'
        ]);

        if($id){
            $obj = Pricetpl::find($id);
            $obj->id = $id;
            $obj->name = $name;
            $obj->ptype = $ptype;
            $obj->signal_id = $signal_id;
            $obj->price = $price;
            $obj->description = $description;
            $res = $obj->save();
        }
        else{
            $param = request(['name', 'ptype', 'signal_id', 'price', 'description']);
            $param['uid'] = $user->id;
            $param['is_admin'] = $flag ? 1 : 0;
            $res = Pricetpl::create($param);
        }

        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function pricebatchelete(Request $request){
        $idstring = $request->input('idstring');
        $this->validate(request(), [
            'idstring'=>'required|min:1'
        ]);

        $idarray = explode(',', $idstring);
        $res = Pricetpl::whereIn('id', $idarray)->delete();;
        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function ptplagent(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $type = $request->input('type');
        $lists = Pricetpl::orWhere(function ($query)use($user, $type) {
                $query = $query->where('uid', $user->id)
                    ->whereNull('deleted_at');

                if($type){
                    $query->where('ptype', $type);
                }
            })
            ->orWhere(function ($query)use($type) {
                $query = $query->where('is_admin', 1)
                    ->whereNull('deleted_at');
                if($type){
                    $query->where('ptype', $type);
                }
            })
            ->get();

        if($lists){
            foreach($lists as $key=>$item){
                $lists[$key]['price'] = rtrim(rtrim($item['price'], '0'), '.');
            }
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $lists);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }

    public function apply(Request $request){
        $id = session('bind_uid');
        $openid = session('openid');

        if($openid && $id) {
            $u = new User();
            $row = $u->getByOpenid($openid);
            if(!$row) {
                $obj = User::find($id);
                if($obj) {
                    $obj->openid = $openid;
                    $res = $obj->save();
                    if ($res) {
                        return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $obj);
                    } else {
                        return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
                    }
                }
                else{
                    return UtilService::format_data(self::AJAX_FAIL, '用户不存在', '');
                }
            }
            else{
                return UtilService::format_data(self::AJAX_FAIL, '已绑定', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '参数错误', '');
        }
    }

    public function lists(Request $request){
        $userObj = JWTAuth::parseToken()->authenticate();
        $flag = false;
        if($userObj->parent_id == config('user.admin_id') && $userObj->type != 'agent'){
            $flag = true;
        }

        $page = $request->input('page');
        $limit = $request->input('num');
        $search = $request->input('search');
        $offset = ($page - 1) * $limit;
        $like = '%'.$search.'%';

        $total = User::whereNull('deleted_at')
            ->where('name', 'like', $like)
            ->where('type', 'maintenance');

        $lists = User::whereNull('deleted_at')
            ->where('name', 'like', $like)
            ->where('type', 'maintenance');

        if($flag){
            $total = $total->where('level', 2);
            $lists = $lists->where('level', 2);
        }
        else{
            if($userObj->type == 'agent' || $userObj->type == 'straight') {
                $total = $total->where('parent_id', $userObj->id);
                $lists = $lists->where('parent_id', $userObj->id);
            }
            else{
                $total = $total->where('parent_id', $userObj->owner_id);
                $lists = $lists->where('parent_id', $userObj->owner_id);
            }
        }

        $total = $total->orderBy('id', 'desc')->get();
        $lists = $lists->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        if($lists){
            foreach ($lists as $key=>$list){
                $img = 'maintenance_'.$list->id;
                $this->createimg($list->name, $list->id);
                $lists[$key]->qrimg = config('wechat.api_domain') . '/qrcodes/maintenance/'. $img . '.png';
            }

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

    private function createimg($name, $id){
        $img = 'maintenance_'.$id;
        if(!file_exists(public_path('qrcodes/maintenance/' . $img . '.png'))) {
            $url = config('wechat.api_domain') . '/devices/maintenance?id=' . $id;
            $path = public_path('qrcodes/maintenance/' . $img . '.png');
            QrCode::encoding('UTF-8')->format('png')->size(120)->margin(2)->generate($url, $path);
            $qrcode_img = imagecreatefrompng($path);

            if (imageistruecolor($qrcode_img)) {
                //将真彩色图像转换为调色板图像
                imagetruecolortopalette($qrcode_img, false, 65535);
            }

            //设定图像的混色模式并启用
            imagealphablending($qrcode_img, true);

            //为一幅图像分配颜色
            $black = imagecolorallocate($qrcode_img, 0, 0, 0);
            $font_file = public_path('qrcodes/wryh.ttf');
            $fontSize = 8;
            $font_str = '市场维护: '.$name;

            //使用 FreeType 2 字体将文本写入图像
            imagefttext($qrcode_img, $fontSize, 0, 23, 115, $black, $font_file, $font_str);

            //建立 PNG 图型
            imagepng($qrcode_img, $path);

            //销毁图像
            imagedestroy($qrcode_img);
        }
    }

    public function agents(Request $request){
        $page = $request->input('page');
        $limit = $request->input('num');
        $mid = $request->input('mid');
        $offset = ($page - 1) * $limit;

        $total = User::where('owner_id', $mid)
            ->orderBy('id', 'desc')
            ->get();

        $lists = User::where('owner_id', $mid)
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

    public function batchdelete(Request $request){
        $userObj = JWTAuth::parseToken()->authenticate();
        $idstring = $request->input('idstring');
        $this->validate(request(), [
            'idstring'=>'required|min:1'
        ]);

        $idarray = explode(',', $idstring);
        $res = User::whereIn('id', $idarray)->where('owner_id', $userObj->id)->delete();;
        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }
}
