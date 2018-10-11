<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Models\Device;
use App\User;
use App\Order;
use UtilService;
use WechatService;
use JWTAuth;
use Illuminate\Support\Facades\DB;
use QrCode;
use ZipArchive;
use Illuminate\Database\QueryException;
use Excel;
use App\Http\Models\Pbrand;
use App\Http\Models\Ptype;
use App\Http\Models\Pricetpl;
use Illuminate\Support\Facades\Log;
use App\Http\Models\Maintenance;
use App\Http\Models\Password;
use App\Http\Models\Discount;

class DeviceController extends Controller
{
    const AJAX_SUCCESS = 0;
    const AJAX_FAIL = -1;
    const AJAX_NO_DATA = 10001;
    const AJAX_NO_AUTH = 99999;
    const NO_PAY = 0;
    const PAYED = 1;
    const REFUND = 2;
    const CLOSED = 3;

    public function index(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $page = $request->input('page');
        $limit = $request->input('num');
        $search = $request->input('search');
        $uid = $request->input('uid');
        $type = $request->input('type');
        $brand = $request->input('brand');
        $isactive = $request->input('isactive');
        $offset = ($page - 1) * $limit;
        $like = '%'.$search.'%';

        $total = Device::where('sn', 'like', $like);
        $lists = Device::where('sn', 'like', $like);
        if($type != 'all'){
            $total = $total->where('type', $type);
            $lists = $lists->where('type', $type);
        }

        if($brand != 'all'){
            $total = $total->where('brand', $brand);
            $lists = $lists->where('brand', $brand);
        }

        if($uid != 'all' && $uid && $uid != 2 && $uid != 'agent'){
            $ids = $this->pids($uid);
            $total = $total->whereIn('uid', $ids);
            $lists = $lists->whereIn('uid', $ids);
        }
        else if($uid == 2){
            $total = $total->whereIn('uid', array(0, 2));
            $lists = $lists->whereIn('uid', array(0, 2));
        }
        else if($uid == 'agent'){
            $total = $total->where('uid', $user->id);
            $lists = $lists->where('uid', $user->id);
        }

        if($isactive == 'yes'){
            $total = $total->whereExists(function ($query) {
                $query->select('*')
                    ->from('orders')
                    ->whereRaw('orders.device_id = devices.id')
                    ->where('status', self::PAYED);
            });
            $lists = $lists->whereExists(function ($query) {
                $query->select('*')
                    ->from('orders')
                    ->whereRaw('orders.device_id = devices.id')
                    ->where('status', self::PAYED);
            });
        }
        else if($isactive == 'no'){
            $total = $total->whereNotExists(function ($query) {
                $query->select('*')
                    ->from('orders')
                    ->whereRaw('orders.device_id = devices.id')
                    ->where('status', self::PAYED);
            });
            $lists = $lists->whereNotExists(function ($query) {
                $query->select('*')
                    ->from('orders')
                    ->whereRaw('orders.device_id = devices.id')
                    ->where('status', self::PAYED);
            });
        }

        $total = $total->orderBy('id', 'desc')
            ->get();

        $lists = $lists->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        if($lists){
            foreach ($lists as $key=>$item){
                if($item->uid) {
                    $agent = User::where('id', $item->uid)->first();
                    if($agent && $agent->router){
                        //路径剔除管理员
                        $r_arr = explode(',', $agent->router);
                        if(in_array(config('user.admin_id'), $r_arr)){
                            for($i=0; $i<count($r_arr); $i++){
                                if($r_arr[$i] == config('user.admin_id')){
                                    array_splice($r_arr, $i, 1);
                                    break;
                                }
                            }
                        }

                        $username = '';
                        foreach ($r_arr as $sub){
                            $u = User::where('id', $sub)->first();
                            if($username && $u){
                                $username = $username . '->' . $u->name;
                            }
                            elseif($u){
                                $username = $u->name;
                            }
                        }

                        $lists[$key]->username = $username;
                    }
                    else{
                        $lists[$key]->username = $agent ? $agent->name : '';
                    }
                }
                else{
                    $lists[$key]->username = '未分配';
                }

                $orders = Order::whereNull('deleted_at')->where('status', self::PAYED)->where('device_id', $item->id)->get();
                $order_num = $orders ? count($orders) : 0;
                $lists[$key]->active = $order_num >= 1 ? 1 : 0;

                $item->ptpls;
                $img = 'qrcode_'.$item->sn;
                $this->createimg($item->sn);
                $lists[$key]->qrimg = config('wechat.api_domain') . '/qrcodes/'. $img . '.png';
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

    public function store(Request $request){
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
        $uid = $request->input('uid');
        $type = $request->input('type');
        $brand = $request->input('brand');
        $address = $request->input('address');
        $location = $request->input('location');
        $pricelist = $request->input('pricelist');
        $sn = $request->input('sn');
        $category = $request->input('category');
        $isopen = $request->input('isopen');
        $ptpls = Pricetpl::findMany($pricelist);
        $this->validate(request(), [
            'type'=>'required|min:1',
            'brand'=>'required',
            'sn'=>'required',
            'pricelist'=>'required'
        ]);

        if(!$id){
            //新增判断sn是否存在
            $dObj = new Device();
            $sn_arr = explode(' ', $sn);
            foreach ($sn_arr as $item) {
                if ($item) {
                    $dRow = $dObj->isSnExist($sn);
                    if ($dRow) {
                        return UtilService::format_data(self::AJAX_FAIL, 'SN码已存在', '');
                    }
                }
            }
        }

        DB::beginTransaction();
        try {
            if ($id) {
                $id_arr = explode(',', $id);
                foreach ($id_arr as $item) {
                    if ($item) {
                        $obj = Device::find($item);
                        //当前价格模板
                        $myPtpls = $obj->ptpls;
                        //要增加的价格模板
                        $addPtpls = $ptpls->diff($myPtpls);
                        foreach ($addPtpls as $ptpl) {
                            $obj->grantPtpl($ptpl);
                        }

                        //要删除的价格模板
                        $deletePtpls = $myPtpls->diff($ptpls);
                        foreach ($deletePtpls as $ptpl) {
                            $obj->deletePtpl($ptpl);
                        }

                        if (!$flag) {
                            $obj->uid = $user->id;
                        }

                        $obj->uid = $uid;
                        $obj->type = $type;
                        $obj->brand = $brand;
                        $obj->category = $category;
                        $obj->address = $address;
                        $obj->location = $location;
                        $obj->isopen = $isopen;
                        $obj->save();
                    }
                }
            }
            else {
                $param = request(['type', 'brand', 'isopen', 'category', 'address', 'location']);
                $sn_arr = explode(' ', $sn);
                foreach ($sn_arr as $item) {
                    if($item) {
                        $param['sn'] = $item;
                        $param['uid'] = $uid;
                        $obj = Device::create($param);
                        if ($obj) {
                            $this->createimg($obj->sn);
                            //要增加的价格模板
                            foreach ($ptpls as $ptpl) {
                                $obj->grantPtpl($ptpl);
                            }
                        }
                    }
                }
            }
            DB::commit();
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', '');
        }
        catch(QueryException $ex)
        {
            DB::rollback();
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function lists(){
        $lists = Device::all();
        if($lists){
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $lists);
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

        $obj = Device::find($id);
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
        $res = Device::whereIn('id', $idarray)->delete();;
        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function consume(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $page = $request->input('page');
        $limit = $request->input('num');
        $search = $request->input('search');
        $type = $request->input('type');
        $brand = $request->input('brand');
        $start = $request->input('start');
        $end = $request->input('end');
        $uid = $request->input('uid');
        $offset = ($page - 1) * $limit;
        $like = '%'.$search.'%';
        $start_time = $start.' 00:00:00';
        $end_time = $end.' 23:59:59';

        $total = DB::table('devices')
            ->join('orders', 'devices.id', '=', 'orders.device_id')
            ->select('orders.*', 'devices.brand', 'devices.type', 'devices.sn', 'devices.category')
            ->where('orders.status', self::PAYED);

        $lists = DB::table('devices')
            ->join('orders', 'devices.id', '=', 'orders.device_id')
            ->select('orders.*', 'devices.brand', 'devices.type', 'devices.sn', 'devices.category')
            ->where('orders.status', self::PAYED);

        if($type != 'all'){
            $total = $total->where('devices.type', $type);
            $lists = $lists->where('devices.type', $type);
        }

        if($brand != 'all'){
            $total = $total->where('devices.brand', $brand);
            $lists = $lists->where('devices.brand', $brand);
        }

        if($uid != 'all' && $uid && $uid != 2){
            $ids = $this->pids($uid);
            $total = $total->whereIn('devices.uid', $ids);
            $lists = $lists->whereIn('devices.uid', $ids);
        }
        else if($uid == 2){
            $total = $total->whereIn('devices.uid', array(0, 2));
            $lists = $lists->whereIn('devices.uid', array(0, 2));
        }

        if($start && $end){
            $total = $total->where('orders.created_at', '>=', $start_time)->where('orders.created_at', '<=', $end_time);
            $lists = $lists->where('orders.created_at', '>=', $start_time)->where('orders.created_at', '<=', $end_time);
        }

        if($search){
            $total = $total->where('devices.sn', 'like', $like);
            $lists = $lists->where('devices.sn', 'like', $like);
        }

        $total = $total->orderBy('orders.id', 'desc')
            ->get();

        $lists = $lists->orderBy('orders.id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        if($lists){
            foreach($lists as $key=>$item){
                $agent = User::where('id', $item->uid)->first();
                if($agent && $agent->router){
                    //路径剔除管理员
                    $r_arr = explode(',', $agent->router);
                    if(in_array(config('user.admin_id'), $r_arr)){
                        for($i=0; $i<count($r_arr); $i++){
                            if($r_arr[$i] == config('user.admin_id')){
                                array_splice($r_arr, $i, 1);
                                break;
                            }
                        }
                    }

                    $username = '';
                    foreach ($r_arr as $sub){
                        $u = User::where('id', $sub)->first();
                        if($username && $u){
                            $username = $username . '->' . $u->name;
                        }
                        elseif($u){
                            $username = $u->name;
                        }
                    }

                    $lists[$key]->username = $username;
                }
                else{
                    $lists[$key]->username = $agent ? $agent->name : '';
                }
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

    public function statistic(Request $request){
        $page = $request->input('page');
        $uid = $request->input('uid');
        $limit = $request->input('num');
        $search = $request->input('search');
        $type = $request->input('type');
        $brand = $request->input('brand');
        $start = $request->input('start');
        $end = $request->input('end');
        $offset = ($page - 1) * $limit;
        $like = '%'.$search.'%';

        $total = DB::table('devices')
            ->join('orders', 'devices.id', '=', 'orders.device_id')
            ->select('devices.id', DB::raw('SUM(orders.money) as total_money'))
            ->where('orders.status', self::PAYED)
            ->groupBy('devices.id');

        $lists = DB::table('devices')
            ->join('orders', 'devices.id', '=', 'orders.device_id')
            ->select('devices.id', DB::raw('SUM(orders.money) as total_money'))
            ->where('orders.status', self::PAYED)
            ->groupBy('devices.id');

        if($type != 'all'){
            $total = $total->where('devices.type', $type);
            $lists = $lists->where('devices.type', $type);
        }

        if($brand != 'all'){
            $total = $total->where('devices.brand', $brand);
            $lists = $lists->where('devices.brand', $brand);
        }

        if($uid != 'all' && $uid && $uid != 2){
            $ids = $this->pids($uid);
            $total = $total->whereIn('devices.uid', $ids);
            $lists = $lists->whereIn('devices.uid', $ids);
        }
        else if($uid == 2){
            $total = $total->whereIn('devices.uid', array(0, 2));
            $lists = $lists->whereIn('devices.uid', array(0, 2));
        }

        if($search){
            $total = $total->where('devices.sn', 'like', $like);
            $lists = $lists->where('devices.sn', 'like', $like);
        }

        $total = $total->orderBy('devices.id', 'desc')
            ->get();

        $lists = $lists->orderBy('devices.id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $seven_day_begin = date('Y-m-d', time() - 7*24*60*60).' 00:00:00';
        $seven_day_end = date('Y-m-d', time()).' 23:59:59';
        $seven_day_list = $this->rangeStatistic($type, $brand, $uid, $search, $offset, $limit, $like, $seven_day_begin, $seven_day_end);

        $thirty_day_begin = date('Y-m-d', time() - 30*24*60*60).' 00:00:00';
        $thirty_day_end = date('Y-m-d', time()).' 23:59:59';
        $thirty_day_list = $this->rangeStatistic($type, $brand, $uid, $search, $offset, $limit, $like, $thirty_day_begin, $thirty_day_end);

        $range_day_begin = $start.' 00:00:00';
        $range_day_end = $end.' 23:59:59';
        if($start && $end) {
            $range_day_list = $this->rangeStatistic($type, $brand, $uid, $search, $offset, $limit, $like, $range_day_begin, $range_day_end);
        }

        foreach($lists as $key=>$item){
            $device = Device::where('id', $item->id)->first();
            if($device){
                if($device->uid) {
                    $agent = User::where('id', $device->uid)->first();
                    $discount = Discount::whereNull('deleted_at')->where('uid', $device->uid)->first();
                    if(!$discount){
                        $discount = Discount::whereNull('deleted_at')->where('uid', 0)->first();
                    }
                }
                else{
                    $agent = null;
                    $discount = new Discount();
                    $discount->value = 100;
                }
            }
            else{
                $agent = null;
                $discount = new Discount();
                $discount->value = 0;
            }

            if($agent && $agent->router){
                //路径剔除管理员
                $r_arr = explode(',', $agent->router);
                if(in_array(config('user.admin_id'), $r_arr)){
                    for($i=0; $i<count($r_arr); $i++){
                        if($r_arr[$i] == config('user.admin_id')){
                            array_splice($r_arr, $i, 1);
                            break;
                        }
                    }
                }

                $username = '';
                foreach ($r_arr as $sub){
                    $u = User::where('id', $sub)->first();
                    if($username && $u){
                        $username = $username . '->' . $u->name;
                    }
                    elseif($u){
                        $username = $u->name;
                    }
                }

                $lists[$key]->agent = $username;
            }
            else{
                $lists[$key]->agent = $agent ? $agent->name : '未分配';
            }

            $lists[$key]->type = $device ? $device->type : '';
            $lists[$key]->brand = $device ? $device->brand : '';
            $lists[$key]->sn = $device ? $device->sn : '';
            $lists[$key]->seven_total_money = 0;
            foreach($seven_day_list as $seven_key=>$seven_item){
                if($item->id == $seven_item->id) {
                    $money = rtrim(rtrim($seven_item->total_money, '0'), '.');
                    $lists[$key]->seven_total_money = $money;
                }
            }

            $lists[$key]->thirty_total_money = 0;
            foreach($thirty_day_list as $thirty_key=>$thirty_item){
                if($item->id == $thirty_item->id) {
                    $money = rtrim(rtrim($thirty_item->total_money, '0'), '.');
                    $lists[$key]->thirty_total_money = $money;
                }
            }

            if($start && $end) {
                $lists[$key]->range_total_money = 0;
                $lists[$key]->range_total_money_discount = 0;
                foreach($range_day_list as $range_key=>$range_item){
                    if($item->id == $range_item->id) {
                        $money = rtrim(rtrim($range_item->total_money, '0'), '.');
                        $lists[$key]->range_total_money = $money;
                        $lists[$key]->range_total_money_discount = $money * $discount->value * 0.01;
                    }
                }
            }
            else{
                $money = rtrim(rtrim($item->total_money, '0'), '.');
                $lists[$key]->range_total_money = $money;
                $lists[$key]->range_total_money_discount = $money * $discount->value * 0.01;
            }
        }

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

    private function rangeStatistic($type, $brand, $uid, $search, $offset, $limit, $like, $begin, $end){
        $lists = DB::table('devices')
            ->join('orders', 'devices.id', '=', 'orders.device_id')
            ->groupBy('devices.id')
            ->select('devices.id', DB::raw('SUM(orders.money) as total_money'))
            ->where('orders.status', self::PAYED);

        if($type != 'all'){
            $lists = $lists->where('devices.type', $type);
        }

        if($brand != 'all'){
            $lists = $lists->where('devices.brand', $brand);
        }

        if($uid != 'all' && $uid && $uid != 2){
            $ids = $this->pids($uid);
            $lists = $lists->whereIn('devices.uid', $ids);
        }
        else if($uid == 2){
            $lists = $lists->whereIn('devices.uid', array(0, 2));
        }

        if($search){
            $lists = $lists->where('devices.sn', 'like', $like);
        }

        $lists = $lists->orderBy('devices.id', 'desc')
            ->where('orders.pay_time', '>=', $begin)
            ->where('orders.pay_time', '<=', $end)
            ->offset($offset)
            ->limit($limit)
            ->get();

        return $lists;
    }

    private function rangeStatisticAll($type, $brand, $uid, $search, $like, $begin, $end){
        $lists = DB::table('devices')
            ->join('orders', 'devices.id', '=', 'orders.device_id')
            ->groupBy('devices.id')
            ->select('devices.id', DB::raw('SUM(orders.money) as total_money'))
            ->where('orders.status', self::PAYED);

        if($type != 'all'){
            $lists = $lists->where('devices.type', $type);
        }

        if($brand != 'all'){
            $lists = $lists->where('devices.brand', $brand);
        }

        if($uid != 'all' && $uid && $uid != 2){
            $ids = $this->pids($uid);
            $lists = $lists->whereIn('devices.uid', $ids);
        }
        else if($uid == 2){
            $lists = $lists->whereIn('devices.uid', array(0, 2));
        }

        if($search){
            $lists = $lists->where('devices.sn', 'like', $like);
        }

        $lists = $lists->orderBy('devices.id', 'desc')
            ->where('orders.pay_time', '>=', $begin)
            ->where('orders.pay_time', '<=', $end)
            ->get();

        return $lists;
    }

    public function qrcode(Request $request){
        $idstring = $request->input('idstring');
        $this->validate(request(), [
            'idstring'=>'required|min:1'
        ]);

        $idarray = explode(',', $idstring);
        $filename = str_replace('\\', '/', public_path()) . '/qrcodes/' . implode($idarray) . '.zip'; // 最终生成的文件名（含路径）
        // 生成文件
        $zip = new ZipArchive (); // 使用本类，linux需开启zlib，windows需取消php_zip.dll前的注释
        if ($zip->open($filename, ZIPARCHIVE::CREATE) !== TRUE) {
            return '无法打开文件，或者文件创建失败';
        }

        $devices = Device::findMany($idarray);
        foreach ($devices as $item) {
            $img = 'qrcode_'.$item->sn;
            $zip->addFile(str_replace('\\', '/', public_path()) . '/qrcodes/' . $img . '.png', basename($img . '.png')); // 第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下
        }
        $zip->close(); // 关闭

        //下面是输出下载;
        header("Cache-Control: max-age=0");
        header("Content-Description: File Transfer");
        header('Content-disposition: attachment; filename=' . basename($filename)); // 文件名
        header("Content-Type: application/zip"); // zip格式的
        header("Content-Transfer-Encoding: binary"); // 告诉浏览器，这是二进制文件
        header('Content-Length: ' . filesize($filename)); // 告诉浏览器，文件大小
        @readfile($filename);//输出文件;
    }

    public function consumeExcel(Request $request){
        $search = $request->input('search');
        $type = $request->input('type');
        $brand = $request->input('brand');
        $start = $request->input('start');
        $end = $request->input('end');
        $uid = $request->input('uid');
        $like = '%'.$search.'%';
        $start_time = $start.' 00:00:00';
        $end_time = $end.' 23:59:59';

        $lists = DB::table('devices')
            ->join('orders', 'devices.id', '=', 'orders.device_id')
            ->select('orders.*', 'devices.brand', 'devices.type', 'devices.sn', 'devices.category')
            ->where('orders.status', self::PAYED);

        if($type != 'all'){
            $lists = $lists->where('devices.type', $type);
        }

        if($brand != 'all'){
            $lists = $lists->where('devices.brand', $brand);
        }

        if($uid != 'all' && $uid && $uid != 2){
            $ids = $this->pids($uid);
            $lists = $lists->whereIn('devices.uid', $ids);
        }
        else if($uid == 2){
            $lists = $lists->whereIn('devices.uid', array(0, 2));
        }

        if($start && $end){
            $lists = $lists->where('orders.created_at', '>=', $start_time)->where('orders.created_at', '<=', $end_time);
        }

        if($search){
            $lists = $lists->where('devices.sn', 'like', $like);
        }

        $lists = $lists->orderBy('orders.id', 'asc')
            ->get();

        if($lists){
            $data = array();
            $data[] = ['序号', '代理商', '设备类型', '设备品牌', '设备编号', '消费金额（元）', '订单号', '支付号', '消费科目', '结算状态', '创建时间'];
            foreach($lists as $key=>$item){
                $agent = User::where('id', $item->uid)->first();
                if($agent && $agent->router){
                    //路径剔除管理员
                    $r_arr = explode(',', $agent->router);
                    if(in_array(config('user.admin_id'), $r_arr)){
                        for($i=0; $i<count($r_arr); $i++){
                            if($r_arr[$i] == config('user.admin_id')){
                                array_splice($r_arr, $i, 1);
                                break;
                            }
                        }
                    }

                    $username = '';
                    foreach ($r_arr as $sub){
                        $u = User::where('id', $sub)->first();
                        if($username && $u){
                            $username = $username . '->' . $u->name;
                        }
                        elseif($u){
                            $username = $u->name;
                        }
                    }

                    $lists[$key]->username = $username;
                }
                else{
                    $lists[$key]->username = $agent ? $agent->name : '';
                }

                $monty = rtrim(rtrim($item->money, '0'), '.');
                if($item->cash_status == 1){
                    $cash_status = '已冻结';
                }
                else if($item->cash_status == 2){
                    $cash_status = '已结算';
                }
                else{
                    $cash_status = '未结算';
                }
                $data[] = [$key +1, $item->username, $item->type, $item->brand, $item->sn, $monty, $item->orderid, $item->pay_no, $item->tpl_name, $cash_status, $item->created_at];
            }

            $filename = '设备消费流水'.date('YmdHis');
            Excel::create($filename, function($excel)use($data) {
                $excel->sheet('设备消费流水', function($sheet)use($data) {
                    $startRow = 2;
                    $startCell = 'B2';
                    $sheet->fromArray($data, null, $startCell);

                    foreach ($data as $key=>$item){
                        $num = ($key + $startRow + 1);
                        $range = 'B'.$num.':L'.$num;
                        if($key%2 == 0){
                            $sheet->cells($range, function($cells) {
                                $cells->setBackground('#ffffff');
                                $cells->setFontColor('#000000');
                                $cells->setAlignment('center');
                            });
                        }
                        else{
                            $sheet->cells($range, function($cells) {
                                $cells->setBackground('#fafafa');
                                $cells->setFontColor('#333333');
                                $cells->setAlignment('center');
                            });
                        }
                    }

                    $endRow = count($data) + 2;
                    $range = 'B2:L'.$endRow;
                    $sheet->setBorder($range, 'thin');
                    $sheet->setWidth(array(
                        'A'     =>  5,
                        'B'     =>  5,
                        'C'     =>  30,
                        'D'     =>  15,
                        'E'     =>  15,
                        'F'     =>  15,
                        'G'     =>  15,
                        'H'     =>  25,
                        'I'     =>  30,
                        'J'     =>  15,
                        'K'     =>  12,
                        'L'     =>  20,
                    ));

                    $sheet->mergeCells('B2:L2');

                    $sheet->cell('B2', function($cell) {
                        $cell->setValue('设备消费流水');
                        $cell->setBackground('#bdbdbd');
                        $cell->setFontColor('#333333');
                        $cell->setFontSize(16);
                        $cell->setFontWeight('bold');
                        $cell->setAlignment('center');
                        $cell->setValignment('center');
                    });
                });

            })->export('xlsx');
        }
    }

    public function statisticExcel(Request $request){
        $uid = $request->input('uid');
        $search = $request->input('search');
        $type = $request->input('type');
        $brand = $request->input('brand');
        $start = $request->input('start');
        $end = $request->input('end');
        $like = '%'.$search.'%';

        $lists = DB::table('devices')
            ->join('orders', 'devices.id', '=', 'orders.device_id')
            ->groupBy('devices.id')
            ->select('devices.id', DB::raw('SUM(orders.money) as total_money'))
            ->where('orders.status', self::PAYED);

        if($type != 'all'){
            $lists = $lists->where('devices.type', $type);
        }

        if($brand != 'all'){
            $lists = $lists->where('devices.brand', $brand);
        }

        if($uid != 'all' && $uid && $uid != 2){
            $ids = $this->pids($uid);
            $lists = $lists->whereIn('devices.uid', $ids);
        }
        else if($uid == 2){
            $lists = $lists->whereIn('devices.uid', array(0, 2));
        }

        if($search){
            $lists = $lists->where('devices.sn', 'like', $like);
        }

        $lists = $lists->orderBy('devices.id', 'desc')
            ->get();

        $seven_day_begin = date('Y-m-d', time() - 7*24*60*60).' 00:00:00';
        $seven_day_end = date('Y-m-d', time()).' 23:59:59';
        $seven_day_list = $this->rangeStatisticAll($type, $brand, $uid, $search, $like, $seven_day_begin, $seven_day_end);

        $thirty_day_begin = date('Y-m-d', time() - 30*24*60*60).' 00:00:00';
        $thirty_day_end = date('Y-m-d', time()).' 23:59:59';
        $thirty_day_list = $this->rangeStatisticAll($type, $brand, $uid, $search, $like, $thirty_day_begin, $thirty_day_end);

        $range_day_begin = $start.' 00:00:00';
        $range_day_end = $end.' 23:59:59';
        if($start && $end) {
            $range_day_list = $this->rangeStatisticAll($type, $brand, $uid, $search, $like, $range_day_begin, $range_day_end);
        }

        foreach($lists as $key=>$item){
            $device = Device::where('id', $item->id)->first();
            if($device){
                if($device->uid) {
                    $agent = User::where('id', $device->uid)->first();
                    $discount = Discount::whereNull('deleted_at')->where('uid', $device->uid)->first();
                    if (!$discount) {
                        $discount = Discount::whereNull('deleted_at')->where('uid', 0)->first();
                    }
                }
                else{
                    $agent = null;
                    $discount = new Discount();
                    $discount->value = 100;
                }
            }
            else{
                $agent = null;
                $discount = new Discount();
                $discount->value = 0;
            }

            if($agent && $agent->router){
                //路径剔除管理员
                $r_arr = explode(',', $agent->router);
                if(in_array(config('user.admin_id'), $r_arr)){
                    for($i=0; $i<count($r_arr); $i++){
                        if($r_arr[$i] == config('user.admin_id')){
                            array_splice($r_arr, $i, 1);
                            break;
                        }
                    }
                }

                $username = '';
                foreach ($r_arr as $sub){
                    $u = User::where('id', $sub)->first();
                    if($username && $u){
                        $username = $username . '->' . $u->name;
                    }
                    elseif($u){
                        $username = $u->name;
                    }
                }

                $lists[$key]->agent = $username;
            }
            else{
                $lists[$key]->agent = $agent ? $agent->name : '未分配';
            }

            $lists[$key]->type = $device ? $device->type : '';
            $lists[$key]->brand = $device ? $device->brand : '';
            $lists[$key]->sn = $device ? $device->sn : '';
            $lists[$key]->address = $device ? $device->address : '';
            $lists[$key]->location = $device ? $device->location : '';
            $lists[$key]->seven_total_money = 0;
            foreach($seven_day_list as $seven_key=>$seven_item){
                if($item->id == $seven_item->id) {
                    $money = rtrim(rtrim($seven_item->total_money, '0'), '.');
                    $lists[$key]->seven_total_money = $money;
                }
            }

            $lists[$key]->thirty_total_money = 0;
            foreach($thirty_day_list as $thirty_key=>$thirty_item){
                if($item->id == $thirty_item->id) {
                    $money = rtrim(rtrim($thirty_item->total_money, '0'), '.');
                    $lists[$key]->thirty_total_money = $money;
                }
            }

            if($start && $end) {
                $lists[$key]->range_total_money = 0;
                $lists[$key]->range_total_money_discount = 0;
                $lists[$key]->range_total_money_discount_company = 0;
                foreach($range_day_list as $range_key=>$range_item){
                    if($item->id == $range_item->id) {
                        $money = rtrim(rtrim($range_item->total_money, '0'), '.');
                        $lists[$key]->range_total_money = $money ? $money : 0;
                        $lists[$key]->range_total_money_discount = $money ? $money * $discount->value * 0.01 : 0;
                        $lists[$key]->range_total_money_discount_company = $money ? $money * (100 - $discount->value) * 0.01 : 0;
                    }
                }
            }
            else{
                $money = rtrim(rtrim($item->total_money, '0'), '.');
                $lists[$key]->range_total_money = $money ? $money : 0;
                $lists[$key]->range_total_money_discount = $money ? $money * $discount->value * 0.01 : 0;
                $lists[$key]->range_total_money_discount_company = $money ? $money * (100 - $discount->value) * 0.01 : 0;
            }
        }

        if($lists){
            $data = array();
            $data[] = ['序号', '代理商', '场地', '摆放位置', '设备类型', '设备品牌', '设备编号', '7天总营业额', '30天总营业额', '时间段总营业额', '时间段内总销售提成(元)', '公司收入'];
            foreach($lists as $key=>$item){
                $item->seven_total_money = $item->seven_total_money ? $item->seven_total_money : '0';
                $item->thirty_total_money = $item->thirty_total_money ? $item->thirty_total_money : '0';
                $item->range_total_money = $item->range_total_money ? $item->range_total_money : '0';
                $item->range_total_money_discount = $item->range_total_money_discount ? $item->range_total_money_discount : '0';
                $item->range_total_money_discount_company = $item->range_total_money_discount_company ? $item->range_total_money_discount_company : '0';
                $data[] = [$key +1, $item->agent, $item->address, $item->location, $item->type, $item->brand, $item->sn, $item->seven_total_money, $item->thirty_total_money, $item->range_total_money, $item->range_total_money_discount, $item->range_total_money_discount_company];
            }

            $filename = '设备消费统计'.date('YmdHis');
            Excel::create($filename, function($excel)use($data) {
                $excel->sheet('设备消费统计', function($sheet)use($data) {
                    $startRow = 2;
                    $startCell = 'B2';
                    $sheet->fromArray($data, null, $startCell);

                    foreach ($data as $key=>$item){
                        $num = ($key + $startRow + 1);
                        $range = 'B'.$num.':M'.$num;
                        if($key%2 == 0){
                            $sheet->cells($range, function($cells) {
                                $cells->setBackground('#ffffff');
                                $cells->setFontColor('#000000');
                                $cells->setAlignment('center');
                            });
                        }
                        else{
                            $sheet->cells($range, function($cells) {
                                $cells->setBackground('#fafafa');
                                $cells->setFontColor('#333333');
                                $cells->setAlignment('center');
                            });
                        }
                    }

                    $endRow = count($data) + 2;
                    $range = 'B2:M'.$endRow;
                    $sheet->setBorder($range, 'thin');
                    $sheet->setWidth(array(
                        'A'     =>  5,
                        'B'     =>  5,
                        'C'     =>  15,
                        'D'     =>  15,
                        'E'     =>  15,
                        'F'     =>  15,
                        'G'     =>  15,
                        'H'     =>  15,
                        'I'     =>  25,
                        'J'     =>  30,
                        'K'     =>  20,
                        'L'     =>  25,
                        'M'     =>  15,
                    ));

                    $sheet->mergeCells('B2:M2');

                    $sheet->cell('B2', function($cell) {
                        $cell->setValue('设备消费统计');
                        $cell->setBackground('#bdbdbd');
                        $cell->setFontColor('#333333');
                        $cell->setFontSize(16);
                        $cell->setFontWeight('bold');
                        $cell->setAlignment('center');
                        $cell->setValignment('center');
                    });
                });

            })->export('xlsx');
        }
    }

    public function add(Request $request)
    {
        $this->getOpenid($request);
        $openid = session('openid');
        $maintanceObj = new User();
        $maintenance = $maintanceObj->getByOpenid($openid);
        if($openid && $maintenance && $maintenance->status == 1) {
            $jsapi = WechatService::jsapi();
            $ptplObj = new Pricetpl();
            $ptplLists = $ptplObj->openList();
            $agentLists = User::whereNull('deleted_at')->where('owner_id', $maintenance->id)->where('isopen', 1)->get();

            $ptpls = array();
            if ($ptplLists) {
                foreach ($ptplLists as $item) {
                    $ptpls[] = array(
                        "label" => $item->name,
                        "value" => $item->id
                    );
                }
            }

            $agents = array();
            if ($agentLists) {
                foreach ($agentLists as $item) {
                    $agents[] = array(
                        "label" => $item->name,
                        "value" => $item->id
                    );
                }
            }

            $data = array(
                "ptpls" => json_encode($ptpls),
                "agents" => json_encode($agents),
                "apiurl" => config('wechat.api_domain')
            );

            $data = array_merge($data, $jsapi);

            return view('adddevice', $data);
        }
        else{
            $data = array(
                "message" => ''
            );
            if($maintenance && $maintenance->status == 0 && $openid) {
                $data['message'] = '正在审核中……';
            }
            else if($maintenance && $maintenance->status == 2 && $openid) {
                $data['message'] = '审核未通过';
            }
            else{
                $data['message'] = '您没有权限';
            }
            return view('error', $data);
        }
    }

    public function maintenance(Request $request)
    {
        $id = $request->input('id');
        $this->getOpenid($request);
        $openid = session('openid');
        $user = User::find($id);
        if($openid && $user) {
            session(['bind_uid' => $id]);
            $obj = new User();
            $data = $obj->getByOpenid($openid);
            $data = array(
                "name" => $user->name,
                "phone" => $user->phone,
                "isbind" => $data ? 1 : 0
            );
            return view('applymaintance', $data);
        }
        else{
            $data = array(
                "message" => '未知错误'
            );

            return view('error', $data);
        }
    }

    public function tpls(Request $request){
        $did = $request->input('did');
        $deviceObj = new Device();
        $device = $deviceObj->findBySn($did);

        $ptplObj = new Pricetpl();
        if($device) {
            $lists = $ptplObj->findByType($device->type);
            if($lists){
                $ptpls = array();
                foreach ($lists as $item) {
                    //只显示默认模板
                    if($item->is_admin == 1) {
                        $ptpls[] = array(
                            "label" => $item->name,
                            "value" => $item->id
                        );
                    }
                }
                return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', array("ptpls"=>$ptpls, "sn"=>$device->sn));
            }
            else{
                return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '参数错误', '');
        }
    }

    public function assign(Request $request){
        $uid = $request->input('uid');
        $address = $request->input('address');
        $location = $request->input('location');
        $pricelist = $request->input('pricelist');
        $did = $request->input('did');
        $ptpls = Pricetpl::findMany($pricelist);
        $this->validate(request(), [
            'uid'=>'required',
            'pricelist'=>'required',
            'did'=>'required'
        ]);

        $obj = Device::find($did);
        if($obj) {
            DB::beginTransaction();
            try {
                //当前价格模板
                $myPtpls = $obj->ptpls;
                //要增加的价格模板
                $addPtpls = $ptpls->diff($myPtpls);
                foreach ($addPtpls as $ptpl) {
                    $obj->grantPtpl($ptpl);
                }

                //要删除的价格模板
                $deletePtpls = $myPtpls->diff($ptpls);
                foreach ($deletePtpls as $ptpl) {
                    $obj->deletePtpl($ptpl);
                }

                $obj->uid = $uid;
                $obj->address = $address;
                $obj->location = $location;
                $obj->save();

                DB::commit();
                return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $obj);
            } catch (QueryException $ex) {
                DB::rollback();
                return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '参数错误', '');
        }
    }

    private function getOpenid($request){
        if(WechatService::isInWechat()){
            $code = $request->input('code');
            if(!$code){
                $wechat = session('openid');
                if($wechat){
                    Log::info('第二次进入，带有个人openid信息');
                    //有session,不执行oauth取用户信息，有openid，URL完整，执行后续代码
                }
                else{
                    Log::info('第一次进入oauth');
                    //没有session，oauth授权获取openid
                    $redirect_uri = WechatService::getPageUrl();
                    $state = 'good';
                    Log::info('oauth授权获取openid，回调URL：'.$redirect_uri);
                    $oauth_url = WechatService::set_oauth_snsapi_base($redirect_uri, $state);
                    //执行网页授权模式，然后跳转到redirect_uri
                    header("location: ".$oauth_url);
                    die();
                }
            }
            else{
                //oauth后回调原来URL并带上code参数
                $oauthInfo = WechatService::getOauthInfoByCode($code);
                Log::info('oauth回调带code'.$code);
                Log::info('oauth结果'.var_export($oauthInfo, true));
                if($oauthInfo != null){
                    $openid = $oauthInfo['openid'];
                    session(['openid' => $openid]);
                }
                else{
                    Log::info('授权出错');
                }
            }
        }
    }

    public function batchcancel(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $roles = $user->roles;
        $flag = false;
        $uid = $user->id;
        foreach ($roles as $role){
            if($role->name == '管理员'){
                $flag = true;
                $uid = 0;
                break;
            }
        }

        $idstring = $request->input('idstring');
        $this->validate(request(), [
            'idstring'=>'required|min:1'
        ]);

        $idarray = explode(',', $idstring);
        $res = Device::whereIn('id', $idarray)->update([
            'uid' => $uid,
            'isopen' => 0
        ]);
        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function batchreset(Request $request){
        $idstring = $request->input('idstring');
        $this->validate(request(), [
            'idstring'=>'required|min:1'
        ]);

        $idarray = explode(',', $idstring);
        $res = Password::whereIn('device_id', $idarray)->delete();
        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function batchassign(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $idstring = $request->input('idstring');
        $child_id = $request->input('child_id');
        $this->validate(request(), [
            'idstring'=>'required|min:1',
            'child_id'=>'required'
        ]);

        $idarray = explode(',', $idstring);
        $res = Device::whereIn('id', $idarray)->where("uid", $user->id)->update([
            "uid"=>$child_id,
            "updated_at"=>date('Y-m-d H:i:s')
        ]);
        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    private function pids($pid){
        $obj = new User();
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

    private function createimg($sn){
        $img = 'qrcode_'.$sn;
        if(!file_exists(public_path('qrcodes/' . $img . '.png'))) {
            $url = config('wechat.api_domain') . '/wxpay/product?sn=' . $sn;
            $path = public_path('qrcodes/' . $img . '.png');
            QrCode::encoding('UTF-8')->format('png')->size(120)->margin(2)->generate($url, $path);
            $context = stream_context_create($this->get_tg_stream_context_create_opts());
            $str = file_get_contents($path, false, $context);
            //从字符串中的图像流新建一图像
            $qrcode_img = imagecreatefromstring($str);

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
            $font_str = 'SN: '.$sn;

            //使用 FreeType 2 字体将文本写入图像
            imagefttext($qrcode_img, $fontSize, 0, 30, 118, $black, $font_file, $font_str);

            //建立 PNG 图型
            imagepng($qrcode_img, $path);

            //销毁图像
            imagedestroy($qrcode_img);
        }
    }

    private function get_tg_stream_context_create_opts(){
        return array(
            "http"=>array(
                "method"=>"GET",
                "timeout"=>3
            ),
        );
    }

    public function diyassign(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $roles = $user->roles;
        $flag = false;
        foreach ($roles as $role){
            if($role->name == '管理员'){
                $flag = true;
                break;
            }
        }

        $dObj = new Device();
        $type = $request->input('type');
        $start = $request->input('start');
        $end = $request->input('end');
        $sn = $request->input('sn');
        $uid = $request->input('uid');
        if($type == 2 && $sn){
            $sn_arr = explode(' ', $sn);
            $new_arr = array();
            foreach ($sn_arr as $item) {
                if($item) {
                    $new_arr[] = $item;
                    $row = $dObj->isSnExist($item);
                    if(!$row){
                        return UtilService::format_data(self::AJAX_FAIL, 'SN:'.$item.'不存在', '');
                        break;
                    }
                }
            }

            if($flag){
                $res = Device::whereIn('sn', $new_arr)->update([
                    "uid"=>$uid,
                    "updated_at"=>date('Y-m-d H:i:s')
                ]);
            }
            else{
                $res = Device::whereIn('sn', $new_arr)->where("uid", $user->id)->update([
                    "uid"=>$uid,
                    "updated_at"=>date('Y-m-d H:i:s')
                ]);
            }
        }
        elseif($type == 1 && $start && $end){
            if($flag){
                $res = Device::where('sn', '>=', $start)->where('sn', '<=', $end)->update([
                    "uid"=>$uid,
                    "updated_at"=>date('Y-m-d H:i:s')
                ]);
            }
            else{
                $res = Device::where('sn', '>=', $start)->where('sn', '<=', $end)->where("uid", $user->id)->update([
                    "uid"=>$uid,
                    "updated_at"=>date('Y-m-d H:i:s')
                ]);
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '参数错误', '');
        }

        if($res){
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function password(Request $request)
    {
        $jsapi = WechatService::jsapi();
        $data = array( );
        $data = array_merge($data, $jsapi);
        return view('password', $data);
    }

    public function pwdlist(Request $request){
        $sn = $request->input('sn');
        $signal_id = $request->input('signal_id');
        $deviceObj = new Device();
        $device = $deviceObj->findBySn($sn);
        if($device) {
            $pwdlist = array();
            for($i=1; $i<=50; $i++){
                $pwdlist[] = $signal_id.$this->generatePwd($sn, $i);
            }

            if(!empty($pwdlist)){
                return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', array("pwdlist"=>$pwdlist));
            }
            else{
                return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '参数错误', '');
        }
    }

    private function generatePwd($x, $y){
        $z = '';
        $i = 0;

        if($x < 100000)
        {
            $x = $x + 100000; //为了减少后面的循环次数
        }
        $z = floor($x * $y/1333) + floor(($x + 1)/3) + floor(($y + 3)/3);
        while($z <= 16777215)
        {
            $i++;
            $z = $z * 13 + $i;
        }

        $pwd = substr($z, -4, 4);

        return $pwd;
    }
}
