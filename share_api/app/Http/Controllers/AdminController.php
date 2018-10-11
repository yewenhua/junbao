<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use UtilService;
use JWTAuth;
use Hash;
use Illuminate\Support\Facades\Gate;
use App\Http\Models\Maintenance;
use App\Http\Models\Device;
use App\Order;
use App\Role;
use App\Http\Models\Discount;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    const AJAX_SUCCESS = 0;
    const AJAX_FAIL = -1;
    const AJAX_NO_DATA = 10001;
    const AJAX_NO_AUTH = 99999;

    const NO_PAY = 0;
    const PAYED = 1;
    const REFUND = 2;
    const CLOSED = 3;

    private $privateKey = "233f4def5c875875";
    private $iv = "233f4def5c875875";

    public function index(Request $request){
        $userObj = JWTAuth::parseToken()->authenticate();
        $page = $request->input('page');
        $limit = $request->input('num');
        $search = $request->input('search');
        $roleid = $request->input('roleid');
        $offset = ($page - 1) * $limit;
        $like = '%' . $search . '%';

        $total = \App\User::where('name', 'like', $like);
        $users = \App\User::where('name', 'like', $like);

        if($roleid != 'all') {
            $role = Role::find($roleid);
            $members = $role->users;
            $idarray = array();
            foreach ($members as $member) {
                $idarray[] = $member->id;
            }

            $total = $total->whereIn('id', $idarray);
            $users = $users->whereIn('id', $idarray);
        }

        $total = $total->orderBy('id', 'desc')
            ->get();

        $users = $users->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        if ($users) {
            foreach ($users as $key=>$item) {
                $roles = $item->roles;
                if($roles && count($roles) > 0) {
                    $users[$key]->role_name = $roles[0]->name;
                }
                else{
                    $users[$key]->role_name = '';
                }

                if($item->owner_id != 0) {
                    $m = \App\User::find($item->owner_id);
                    $users[$key]->maintenance = $m ? $m->name : '--';
                }
                else{
                    $users[$key]->maintenance = '管理员';
                }

                if($item->type == 'agent' || $item->type == 'straight' || $item->type == 'admin') {
                    if($item->type == 'admin'){
                        $devices = Device::whereNull('deleted_at')->whereIn('uid', array(0, config('user.admin_id')))->get();
                        $orders = Order::whereNull('deleted_at')->whereIn('uid', array(0, config('user.admin_id')))->where('status', self::PAYED)->get();
                        $users[$key]->discount = 100;
                    }
                    else {
                        $devices = Device::whereNull('deleted_at')->where('uid', $item->id)->get();
                        $ids = $this->pids($item->id);
                        $orders = Order::whereNull('deleted_at')->whereIn('uid', $ids)->where('status', self::PAYED)->get();
                        $discount = Discount::whereNull('deleted_at')->where('uid', $item->id)->first();
                        if(!$discount){
                            $discount = Discount::whereNull('deleted_at')->where('uid', 0)->first();
                        }
                        $users[$key]->discount = $discount ? $discount->value : 0;
                    }

                    $users[$key]->device_num = $devices ? count($devices) : 0;
                    $clear_money = 0;
                    $unclear_money = 0;
                    $freeze_money = 0;
                    if($orders) {
                        foreach ($orders as $order) {
                            if ($order['cash_status'] == 0) {
                                $unclear_money = $unclear_money + $order['money'];
                            } elseif ($order['cash_status'] == 1) {
                                $freeze_money = $freeze_money + $order['money'];
                            } else {
                                $clear_money = $clear_money + $order['money'];
                            }
                        }
                    }
                    $users[$key]->clear_money = $clear_money;
                    $users[$key]->unclear_money = $unclear_money;
                    $users[$key]->freeze_money = $freeze_money;
                }
                else{
                    $users[$key]->device_num = 0;
                    $users[$key]->clear_money = 0;
                    $users[$key]->unclear_money = 0;
                    $users[$key]->freeze_money = 0;
                    $users[$key]->discount = 0;
                }
            }

            $res = array(
                'user' => $userObj,
                'data' => $users,
                'total' => count($total)
            );
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $res);
        } else {
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }

    public function create(){

    }

    //管理员和代理商添加代理入口
    public function store(Request $request){
        $userObj = JWTAuth::parseToken()->authenticate();
        $roles = $userObj->roles;
        $flag = false;
        foreach ($roles as $role){
            if($role->name == '管理员'){
                $flag = true;
                break;
            }
        }

        if($userObj && ($userObj->type == 'agent' || $userObj->type == 'straight' || $userObj->type == 'admin')) {
            $id = $request->input('id');
            $name = $request->input('name');
            $desc = $request->input('desc');
            $email = $request->input('email');
            $contact = $request->input('contact');
            $phone = $request->input('phone');
            $area = $request->input('area');
            $address = $request->input('address');
            $isopen = $request->input('isopen');
            $type = $request->input('type');
            $this->validate(request(), [
                'name' => 'required|min:1',
                'email' => 'required',
                'contact' => 'required',
                'phone' => 'required',
                'area' => 'required',
                'address' => 'required'
            ]);

            if ($id == 2) {
                return '';
            }

            $obj = new \App\User();
            $row = $obj->isMobileExist($phone);

            if (($id && $row && $row->id != $id) || (!$id && $row)) {
                return UtilService::format_data(self::AJAX_FAIL, '该手机号码已存在或已软删除', '');
            } else {
                DB::beginTransaction();
                try {
                    if ($id) {
                        $user = \App\User::find($id);
                        $user->name = $name;
                        $user->desc = $desc;
                        $user->email = $email;
                        $user->contact = $contact;
                        $user->phone = $phone;
                        $user->area = $area;
                        $user->address = $address;
                        $user->isopen = $isopen;
                        $res = $user->save();
                    }
                    else {
                        $params = request(['name', 'desc', 'email', 'contact', 'phone', 'area', 'address', 'isopen']);
                        $params['password'] = bcrypt('123456');
                        $params['status'] = 1;
                        if ($type) {
                            $params['type'] = $type;
                        }

                        $res = \App\User::create($params); //save 和 create 的不同之处在于 save 接收整个 Eloquent 模型实例而 create 接收原生 PHP 数组
                        if ($type && $res) {
                            //非管理员
                            if ($type == 'operate') {
                                $role_id = config('user.role_operate_id');
                            } elseif ($type == 'manitenance') {
                                $role_id = config('user.role_manitenance_id');
                            } elseif ($type == 'agent') {
                                $role_id = config('user.role_child_id');
                            }

                            $role = \App\Role::find($role_id);
                            $res->assignRole($role);  //要增加的角色
                        }

                        if ($res) {
                            if ($flag) {
                                $res->level = 2;
                                $res->router = config('user.admin_id') . ',' . $res->id;
                                $res->owner_id = config('user.admin_id');
                                $res->parent_id = config('user.admin_id');
                            } else {
                                $res->level = $userObj->level + 1;
                                $res->router = $userObj->router . ',' . $res->id;
                                $res->owner_id = $userObj->id;
                                $res->parent_id = $userObj->id;
                            }
                            $res->save();
                        }
                    }
                    DB::commit();
                    return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', '');
                } catch (QueryException $ex) {
                    DB::rollback();
                    return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
                }
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '没有权限', '');
        }
    }

    //路由模型绑定user实例
    public function role(\App\User $user){
        $roles = \App\Role::all(); // all roles
        $myRoles = $user->roles; //带括号的是返回关联对象实例，不带括号是返回动态属性

        //compact 创建一个包含变量名和它们的值的数组
        $data = compact('roles', 'myRoles', 'role');
        return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $data);
    }

    //储存用户角色
    public function storeRole(\App\User $user){
        $userObj = JWTAuth::parseToken()->authenticate();
        $roles = $userObj->roles;
        $flag = false;
        foreach ($roles as $role){
            if($role->name == '管理员'){
                $flag = true;
                break;
            }
        }

        if($flag) {
            //验证
            $roles = \App\Role::findMany(request('roles'));
            $myRoles = $user->roles;

            //要增加的角色
            $addRoles = $roles->diff($myRoles);
            foreach ($addRoles as $role) {
                $user->assignRole($role);
            }

            //要删除的角色
            $deleteRoles = $myRoles->diff($roles);
            foreach ($deleteRoles as $role) {
                $user->deleteRole($role);
            }

            foreach ($roles as $role){
                if(strpos($role->name, '财务') !== false){
                    $user->type = 'finance';
                }
                elseif(strpos($role->name, '运营') !== false){
                    $user->type = 'operate';
                }
                elseif(strpos($role->name, '市场维护') !== false){
                    $user->type = 'maintenance';
                }
                elseif(strpos($role->name, '代理商') !== false){
                    $user->type = 'agent';
                }
                elseif(strpos($role->name, '直营客户') !== false){
                    $user->type = 'straight';
                }
                else{
                    $user->type = '';
                }
                $user->save();
            }

            return UtilService::format_data(self::AJAX_SUCCESS, '保存成功', []);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '没有权限', '');
        }
    }

    public function delete(Request $request){
        $id = $request->input('id');
        $this->validate(request(), [
            'id'=>'required|min:1'
        ]);

        $user = \App\User::find($id);
        $res = $user->delete();
        if($user && $res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function batchdelete(Request $request){
        $userObj = JWTAuth::parseToken()->authenticate();
        $idstring = $request->input('idstring');
        $password = $request->input('password');
        $this->validate(request(), [
            'idstring'=>'required|min:1',
            'password'=>'required'
        ]);

        $password = urldecode($password); //前端用encodeURIComponent编码
        $password = $this->aesdecrypt($password);

        if (Hash::check($password, $userObj->password)){
            $idarray = explode(',', $idstring);
            $devs = Device::whereNull('deleted_at')->whereIn('uid', $idarray)->first();
            if(!$devs) {
                $res = \App\User::whereIn('id', $idarray)->delete();;
                if ($res) {
                    return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
                } else {
                    return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
                }
            }
            else{
                return UtilService::format_data(self::AJAX_FAIL, '名下有设备，不能删除', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '密码错误', '');
        }
    }

    public function chgpwd(Request $request){
        $id = $request->input('id');
        $oldpwd = $request->input('oldpwd');
        $newpwd = $request->input('newpwd');
        $this->validate(request(), [
            'id'=>'required',
            'oldpwd'=>'required|min:1',
            'newpwd'=>'required'
        ]);

        $user = JWTAuth::parseToken()->authenticate();
        if($user && $user->id == $id){
            $flag = Hash::check($oldpwd, $user->password);
            if($flag) {
                $user->password = bcrypt($newpwd);
                $res = $user->save();
                if ($res) {
                    return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
                } else {
                    return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
                }
            }
            else{
                return UtilService::format_data(self::AJAX_FAIL, '原密码错误', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '用户错误', '');
        }
    }

    public function userInfo(){
        $userObj = JWTAuth::parseToken()->authenticate();
        $roles = $userObj->roles;
        foreach ($roles as $role) {
            $permissions = $role->permissions;
        }

        return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $userObj);
    }

    public function lists(){
        $user = JWTAuth::parseToken()->authenticate();
        $flag = false;
        if($user->owner_id == config('user.admin_id')){
            $flag = true;
        }

        $rtn = array();
        if($flag && $user->type != 'agent' && $user->type != 'straight'){
            //管理员一级
            $lists = \App\User::whereNull('deleted_at')
                ->where('isopen', 1)
                ->whereIn('type', array('agent', 'straight'))
                ->where('level', 2)
                ->get();
            $rtn['type'] = 'admin';
        }
        else{
            //代理商一级
            $lists = \App\User::whereNull('deleted_at')
                ->where('isopen', 1)
                ->where('type', 'agent');

            if($user->type == 'agent' || $user->type == 'straight'){
                $level = $user->level + 1;
                $lists = $lists->where('parent_id', $user->id)
                    ->where('level', $level)
                    ->get();
            }
            else{
                $level = $user->level;
                $lists = $lists->where('parent_id', $user->owner_id)
                    ->where('level', $level)
                    ->get();
            }

            if($lists){
                $arr = array();
                array_unshift($arr, $user);
                foreach ($lists as $list) {
                    $arr[] = $list;
                }
                $lists = $arr;
            }
            else{
                $lists = array(
                    $user
                );
            }

            $rtn['type'] = 'agent';
        }

        if($lists){
            $rtn['list'] = $lists;
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $rtn);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }

    public function apply(Request $request){
        $name = $request->input('name');
        $email = $request->input('email');
        $contact = $request->input('contact');
        $phone = $request->input('phone');
        $area = $request->input('area');
        $address = $request->input('address');
        $openid = session('openid');
        $obj = new \App\User();
        $maintenance = $obj->getByOpenid($openid);
        if($openid && $maintenance && $maintenance->type == 'maintenance') {
            $this->validate(request(), [
                'name' => 'required|min:1',
                'email' => 'required',
                'contact' => 'required',
                'phone' => 'required',
                'area' => 'required',
                'address' => 'required'
            ]);

            $params = request(['name', 'email', 'contact', 'phone', 'area', 'address']);
            $params['password'] = bcrypt('123456');
            $params['owner_id'] = $maintenance->id;
            $params['status'] = 0;
            $params['type'] = 'agent';

            $res = \App\User::create($params); //save 和 create 的不同之处在于 save 接收整个 Eloquent 模型实例而 create 接收原生 PHP 数组
            if ($res) {
                if($maintenance->owner_id == config('user.admin_id')){
                    //业务员属于平台
                    $res->level = 2;
                    $res->router = config('user.admin_id') . ',' . $res->id;
                    $res->parent_id = config('user.admin_id');
                }
                else{
                    //业务员属于代理商
                    $res->level = $maintenance->level;
                    if($maintenance->router){
                        $arr = explode(',', $maintenance->router);
                        $arr[count($arr) - 1] = $res->id;
                        $res->router = implode(',', $arr);
                    }
                    $res->parent_id = $maintenance->owner_id;
                }
                return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
            } else {
                return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '未知错误', '');
        }
    }

    public function check(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $id = $request->input('id');
        $status = $request->input('status');
        $status = $status == 'agree' ? 1 : 2;
        $this->validate(request(), [
            'status' => 'required',
            'id' => 'required'
        ]);

        $obj = \App\User::find($id);
        if($obj) {
            $obj->status = $status;
            $res = $obj->save();
            if($res){
                return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
            }
            else{
                return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '参数错误', '');
        }
    }

    public function resetpwd(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $roles = $user->roles;
        $flag = false;
        foreach ($roles as $role){
            if($role->name == '管理员'){
                $flag = true;
                break;
            }
        }

        $idstring = $request->input('idstring');
        $this->validate(request(), [
            'idstring'=>'required'
        ]);

        if($flag){
            $idarray = explode(',', $idstring);
            $password = bcrypt('123456');
            $res = \App\User::whereIn('id', $idarray)->update([
                'password' => $password
            ]);

            if ($res) {
                return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
            } else {
                return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '用户错误', '');
        }
    }

    public function children(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $page = $request->input('page');
        $search = $request->input('search');
        $limit = $request->input('num');
        $offset = ($page - 1) * $limit;
        $like = '%' . $search . '%';
        if($user->type == 'agent' || $user->type == 'straight'){
            $parent_id = $user->id;
        }
        else{
            $parent_id = $user->owner_id;
        }

        $total = \App\User::where('parent_id', $parent_id)
            ->where('name', 'like', $like)
            ->orderBy('id', 'desc')
            ->get();

        $lists = \App\User::where('parent_id', $parent_id)
            ->where('name', 'like', $like)
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        if($lists){
            foreach ($lists as $key=>$item){
                $roles = $item->roles;
                if($roles && count($roles) > 0) {
                    $lists[$key]->role_name = $roles[0]->name;
                }
                else{
                    $lists[$key]->role_name = '';
                }

                $devices = Device::whereNull('deleted_at')->where('uid', $item->id)->get();
                $lists[$key]->device_num = $devices ? count($devices) : 0;

                $orders = Order::whereNull('deleted_at')->where('uid', $item->id)->where('status', self::PAYED)->get();
                $clear_money = 0;
                $unclear_money = 0;
                $freeze_money = 0;
                foreach ($orders as $order) {
                    if($order['cash_status'] == 0){
                        $unclear_money = $unclear_money + $order['money'];
                    }
                    elseif($order['cash_status'] == 1){
                        $freeze_money = $freeze_money + $order['money'];
                    }
                    else{
                        $clear_money = $clear_money + $order['money'];
                    }
                }
                $lists[$key]->clear_money = $clear_money;
                $lists[$key]->unclear_money = $unclear_money;
                $lists[$key]->freeze_money = $freeze_money;
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

    public function allchild(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $lists = \App\User::where('parent_id', $user->id)
            ->where('type', 'agent')
            ->orderBy('id', 'desc')
            ->get();

        if($lists){
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $lists);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }

    public function childdelete(Request $request){
        $userObj = JWTAuth::parseToken()->authenticate();
        $idstring = $request->input('idstring');
        $password = $request->input('password');
        $this->validate(request(), [
            'idstring'=>'required|min:1',
            'password'=>'required'
        ]);

        $password = urldecode($password); //前端用encodeURIComponent编码
        $password = $this->aesdecrypt($password);

        if($userObj && $userObj->type == 'agent') {
            if (Hash::check($password, $userObj->password)) {
                $idarray = explode(',', $idstring);
                $devs = Device::whereNull('deleted_at')->whereIn('uid', $idarray)->get();
                if (!$devs) {
                    $res = \App\User::whereIn('id', $idarray)->where('parent_id', $userObj->id)->delete();;
                    if ($res) {
                        return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
                    } else {
                        return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
                    }
                } else {
                    return UtilService::format_data(self::AJAX_FAIL, '名下有设备，不能删除', '');
                }
            } else {
                return UtilService::format_data(self::AJAX_FAIL, '密码错误', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '没有权限', '');
        }
    }

    private function pids($pid){
        $obj = new \App\User();
        $children = $obj->findByParentId($pid);
        $arr = array();
        $arr[] = $pid;
        foreach ($children as $child) {
            if($child->type == 'agent') {
                $arr[] = $child->id;
            }
        }

        return $arr;
    }

    private function aesdecrypt($str){
        $encryptedData = base64_decode($str);
        $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->privateKey, $encryptedData, MCRYPT_MODE_CBC, $this->iv);
        $decrypted = rtrim($decrypted,"\0");
        $decrypted = json_decode($decrypted);

        return $decrypted;
    }

    private function aesencrypt($str){
        $data = json_encode($str);
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->privateKey, $data, MCRYPT_MODE_CBC, $this->iv));

        return $encrypted;
    }
}
