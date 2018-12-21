<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Models\Cash;
use JWTAuth;
use Illuminate\Support\Facades\Log;
use UtilService;
use App\Order;
use App\User;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Discount;
use Illuminate\Database\QueryException;
use Excel;

class CashController extends Controller
{
    const AJAX_SUCCESS = 0;
    const AJAX_FAIL = -1;
    const AJAX_NO_DATA = 10001;
    const AJAX_NO_AUTH = 99999;

    const NO_CHECK = 0;
    const FREEZE = 1;
    const AGREED = 2;
    const DISAGREED = 3;

    const NO_PAY = 0;
    const PAYED = 1;
    const REFUND = 2;
    const CLOSED = 3;

    public function cashlog(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $roles = $user->roles;
        $flag = false;
        foreach ($roles as $role){
            if($role->name == '管理员' || $role->name == '财务'){
                $flag = true;
                break;
            }
        }

        $page = $request->input('page');
        $limit = $request->input('num');
        $status = $request->input('status');
        $uid = $request->input('uid');
        $start = $request->input('start');
        $end = $request->input('end');
        $offset = ($page - 1) * $limit;
        $start_time = $start.' 00:00:00';
        $end_time = $end.' 23:59:59';
        $uid = !$flag ? $user->id : $uid;

        $total = DB::table('users')
            ->join('cash', 'users.id', '=', 'cash.uid')
            ->select('cash.*', 'users.name');

        $lists = DB::table('users')
            ->join('cash', 'users.id', '=', 'cash.uid')
            ->select('cash.*', 'users.name');

        if($status != 'all'){
            $total = $total->where('cash.status', $status);
            $lists = $lists->where('cash.status', $status);
        }

        if($uid != 'all' && $uid && $uid != 2){
            $ids = $this->pids($uid);
            $total = $total->whereIn('cash.uid', $ids);
            $lists = $lists->whereIn('cash.uid', $ids);
        }
        else if($uid == 2){
            $total = $total->whereIn('cash.uid', array(0, 2));
            $lists = $lists->whereIn('cash.uid', array(0, 2));
        }

        if($start && $end){
            $total = $total->where('cash.created_at', '>=', $start_time)->where('cash.created_at', '<=', $end_time);
            $lists = $lists->where('cash.created_at', '>=', $start_time)->where('cash.created_at', '<=', $end_time);
        }

        $total = $total->orderBy('cash.id', 'desc')
            ->get();

        $lists = $lists->orderBy('cash.id', 'desc')
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

    public function money(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $roles = $user->roles;
        $flag = false;
        foreach ($roles as $role){
            if($role->name == '管理员' || $role->name == '财务'){
                $flag = true;
                break;
            }
        }

        $uid = $request->input('uid');
        $uid = !$flag ? $user->id : $uid;

        if($uid != 'all') {
            $discount = Discount::whereNull('deleted_at')->where('uid', $uid)->first();
            if (!$discount) {
                $discount = Discount::whereNull('deleted_at')->where('uid', 0)->first();
            }
        }

        if($uid != 'all' && $uid && $uid != 2){
            $pids = $this->pids($uid);
        }
        else if($uid == 2){
            $pids = array(0, 2);
        }
        else{
            $pids = array();
            $all = User::select(['id'])->whereNull('deleted_at')->get();
            foreach ($all as $u){
                $pids[] = $u->id;
            }
        }

        $lists = DB::table('orders')
            ->select(['cash_status', 'money'])
            ->where('status', self::PAYED)
            ->whereIn('uid', $pids)
            ->orderBy('id', 'desc')
            ->get();

        $rest_money = 0;
        $sale_money = 0;
        if($lists){
            foreach ($lists as $item){
                if($item->cash_status == self::NO_CHECK) {
                    $rest_money = $rest_money + $item->money;
                }
                $sale_money = $sale_money + $item->money;
            }

            if($uid != 'all') {
                $rest_money = $rest_money * $discount->value * 0.01;
                if(strpos($rest_money, '.') !== false){
                    $rest_money = round($rest_money, 2);
                }
            }
        }

        $freeze = Cash::whereIn('uid', $pids)
            ->where('status', self::NO_CHECK)
            ->first();

        $freeze_money = $freeze ? $freeze['money'] : 0;

        $take = Cash::select(['money'])->whereIn('uid', $pids)
            ->where('status', self::AGREED)
            ->get();

        $take_money = 0;
        if($take){
            foreach ($take as $item){
                $take_money = $take_money + $item->money;
            }
        }

        return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', array("freeze_money"=>$freeze_money, "rest_money"=>round($rest_money, 2), "take_money"=>$take_money, "sale_money"=>round($sale_money, 2)));
    }

    public function takecash(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $pids = $this->pids($user->id);
        $freeze = Cash::whereIn('uid', $pids)->where('status', self::NO_CHECK)->first();
        if(!$freeze) {
            $discount = Discount::whereNull('deleted_at')->where('uid', $user->id)->first();
            if (!$discount) {
                $discount = Discount::whereNull('deleted_at')->where('uid', 0)->first();
            }

            DB::beginTransaction();
            try {
                $ids = $this->pids($user->id);
                $deadline = date('Y-m-d H:i:s');
                $obj = DB::table('orders')->select(DB::raw('SUM(orders.money) as total_money'))->where('created_at', '<=', $deadline)->whereIn('uid', $ids)->where('status', self::PAYED)->where('cash_status', self::NO_CHECK)->first();
                $money = $obj ? $obj->total_money : '0';
                if ($money > 0 && $discount) {
                    $money = $money * $discount->value * 0.01;
                    DB::table('orders')->whereIn('uid', $ids)->where('cash_status', self::NO_CHECK)->where('status', self::PAYED)->where('created_at', '<=', $deadline)->update([
                        'cash_status' => self::FREEZE
                    ]);

                    DB::table('cash')->insert([
                        "money" => $money,
                        'uid' => $user->id,
                        "status" => self::NO_CHECK,
                        "created_at" => date('Y-m-d H:i:s'),
                        "updated_at" => date('Y-m-d H:i:s')
                    ]);

                    DB::commit();

                    $log = array(
                        'money' => $money,
                        'uid' => $user->id
                    );
                    Log::info('takecash success:' . var_export($log, true));
                    return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $log);
                } else {
                    DB::rollback();
                    return UtilService::format_data(self::AJAX_FAIL, '没有余额', '');
                }
            } catch (QueryException $ex) {
                DB::rollback();
                Log::info('takecash fail:' . var_export($log, true));
                return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '您还有未审核的提现', '');
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

    public function checkcash(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $id = $request->input('id');
        $uid = $request->input('uid');
        $status = $request->input('status');
        $status = $status == 'agree' ? self::AGREED : self::DISAGREED;
        $this->validate(request(), [
            'status' => 'required|min:1',
            'id' => 'required'
        ]);

        if($status == self::AGREED){
            $cash_status = self::AGREED;
            $order_cash_status = self::AGREED;
        }
        else{
            $cash_status = self::DISAGREED;
            $order_cash_status = self::NO_CHECK; //退回
        }

        DB::beginTransaction();
        try {
            $pids = $this->pids($uid);
            DB::table('orders')->where('status', self::PAYED)->where('cash_status', self::FREEZE)->whereIn('uid', $pids)->update([
                'cash_status' => $order_cash_status,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            DB::table('cash')->where('id', $id)->update([
                'status' => $cash_status,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            DB::commit();
            Log::info('checkcash success:' . var_export(array('id'=>$id, 'uid'=>$uid), true));
            return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', '');
        }catch(QueryException $ex) {
            DB::rollback();
            Log::info('checkcash fail:' . var_export(array('id'=>$id, 'uid'=>$uid), true));
            return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
        }
    }

    public function statistic(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $uid = $request->input('uid');
        $search = $request->input('search');
        $like = '%'.$search.'%';
        $begin = date('Y-m-d', time() - 7*24*60*60).' 00:00:00';
        $end = date('Y-m-d', time()).' 23:59:59';

        $lists = DB::table('orders')
            ->join('devices', 'devices.id', '=', 'orders.device_id')
            ->select('orders.*')
            ->where('orders.status', self::PAYED)
            ->where('orders.pay_time', '>=', $begin)
            ->where('orders.pay_time', '<=', $end)
            ->whereNull('orders.deleted_at');

        if($uid != 'all' && $uid && $uid != 2){
            $ids = $this->pids($uid);
            $lists = $lists->whereIn('orders.uid', $ids);
        }
        else if($uid == 2){
            $lists = $lists->whereIn('orders.uid', array(0, 2));
        }

        if($search){
            $lists = $lists->where('devices.sn', 'like', $like);
        }

        $lists = $lists->orderBy('orders.id', 'desc')
            ->get();

        if($lists){
            $data = array(
                date('Y-m-d', time() - 6*24*60*60) => 0,
                date('Y-m-d', time() - 5*24*60*60) => 0,
                date('Y-m-d', time() - 4*24*60*60) => 0,
                date('Y-m-d', time() - 3*24*60*60) => 0,
                date('Y-m-d', time() - 2*24*60*60) => 0,
                date('Y-m-d', time() - 24*60*60) => 0,
                date('Y-m-d', time()) => 0
            );

            foreach ($lists as $item){
                $day = substr($item->pay_time, 0, 10);
                foreach ($data as $key=>$val) {
                    if($day == $key){
                        $data[$key] = $val + $item->money;
                        break;
                    }
                }
            }

            foreach ($data as $key=>$val) {
                $data[$key] = round($val, 2);
            }
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', $data);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }
}
