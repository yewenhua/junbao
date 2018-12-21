<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use JWTAuth;
use UtilService;
use WxpayService;
use WechatService;
use Illuminate\Support\Facades\Log;
use App\Order;
use App\Http\Models\Device;
use App\Http\Models\Pricetpl;
use App\Http\Models\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class WxpayController extends Controller
{
    const AJAX_SUCCESS = 0;
    const AJAX_FAIL = -1;
    const AJAX_NO_DATA = 10001;
    const AJAX_NO_AUTH = 99999;
    const NO_PAY = 0;
    const PAYED = 1;
    const REFUND = 2;
    const CLOSED = 3;

    public function product(Request $request)
    {
        $this->getOpenid($request);
        $sn = $request->input('sn');
        $sn = ltrim($sn, '0');
        $data = array();
        $deviceObj = new Device();
        $device = $deviceObj->findBySn($sn);
        if($device) {
            $priceList = $device->ptpls;
            $openid = session('openid');
            //$openid = 'oG-AvwrJEbLG7lkd9j-nmeMojf6g';
            if($openid && $priceList)
            {
                $flag = false;
                $diff_time = 0;
                $pwd = '';
                $compareOrder = Order::whereNull('deleted_at')->where('status', self::PAYED)->where('device_id', $device->id)->orderBy('id', 'desc')->first();
                $newestOrder = Order::whereNull('deleted_at')->where('status', self::PAYED)->where('openid', $openid)->where('device_id', $device->id)->orderBy('id', 'desc')->first();
                if($newestOrder && $compareOrder && $compareOrder['id'] == $newestOrder['id']){
                    //最新订单在时间范围内并且最新订单是自己的
                    $signal_id = $newestOrder->signal_id;
                    $pay_time = strtotime($newestOrder->pay_time);
                    $compare_time = time() - $pay_time;
                    $time = $this->usefulTime($signal_id);

                    if($time > $compare_time){
                        $flag = true;
                        $pwdObj = new Password();
                        $pwdData = $pwdObj->pwdByOrderid($newestOrder->orderid);
                        $pwd = $pwdData ? $pwdData->password : '';
                        $diff_time = $pay_time + $time - time();
                    }
                }

                foreach($priceList as $key=>$item){
                    $priceList[$key]['price'] = rtrim(rtrim($item['price'], '0'), '.');
                }
                $data['price_list'] = json_encode($priceList);
                $data['device'] = $device;
                $data['password'] = $pwd;
                $data['diff_time'] = $flag ? $diff_time : 0;
                return view('product', $data);
            } else {
                return '未知错误';
            }
        }
        else{
            return '参数错误';
        }
    }

    public function prepay(Request $request){
        $domain = UtilService::domain();
        $openid = session('openid');
        $money = $request->input('money');
        $signal_id = $request->input('signal_id');
        $tpl_name = $request->input('tpl_name');
        $device_id = $request->input('device_id');
        $uid = $request->input('uid');
        if($money && $openid) {
            $orderid = date('YmdHis') . rand(1000000, 9999999);
            $type = 'JSAPI';
            $fee = $money * 100; //订单总金额，单位为分
            $params = array(
                "openid" => $openid,
                "money" => $money,
                "signal_id" => $signal_id,
                "uid" => $uid,
                "tpl_name" => $tpl_name,
                "orderid" => $orderid,
                "device_id" => $device_id
            );

            Log::info('Order params:' . var_export($params, true));
            $res = Order::create($params);
            if($res) {
                $notify_url = $domain . "/wxpay/notify";
                WxpayService::setNotifyUrl($notify_url);
                $prepayInfo = WxpayService::prepay($orderid, $openid, $type, $fee);
                if ($prepayInfo !== null) {
                    $prepay_id = $prepayInfo["prepay_id"];
                    $jsapiParameters = WxpayService::getJsapiParameters($prepay_id);
                    Log::info('前端支付参数 param:' . $jsapiParameters);
                    return UtilService::format_data(self::AJAX_SUCCESS, '创建订单成功', ['json'=>$jsapiParameters, "orderid" => $orderid]);
                } else {
                    return UtilService::format_data(self::AJAX_FAIL, '微信下单失败', '');
                }
            }
            else{
                return UtilService::format_data(self::AJAX_FAIL, '订单创建失败', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '参数错误', '');
        }
    }

    public function notify(){
        $return = WxpayService::notify();
        if($return['status'] == 'success') {
            $callbackData = $return['callback'];
            $orderid = $callbackData['out_trade_no'];
            $status = self::PAYED;
            $pay_time = date('Y-m-d H:i:s', strtotime($callbackData['time_end']));
            $orderObj = new Order();
            $dataObj = $orderObj->findByOrderid($orderid);
            $deviceObj = new Device();
            $device = $deviceObj->findByDid($dataObj['device_id']);
            if($dataObj && $dataObj->status == self::NO_PAY && $device) {
                $pwdObj = new Password();
                $lastPassword = $pwdObj->newestPwd($dataObj['device_id']);
                if($lastPassword){
                    if($lastPassword['sn'] >= 1 && $lastPassword['sn'] < 999){
                        $new_sn = $lastPassword['sn'] + 1;
                    }
                    else{
                        if($device['sn'] >= 20000){
                            $new_sn = 3;
                        }
                        else{
                            $new_sn = 1;
                        }
                    }
                }
                else{
                    if($device['sn'] >= 20000){
                        $new_sn = 3;
                    }
                    else{
                        $new_sn = 1;
                    }
                }

                $newPassword = $this->generatePwd($device['sn'], $new_sn);
                $newPassword = $dataObj->signal_id.$newPassword;

                DB::beginTransaction();
                try {
                    DB::table('orders')->where('orderid', $orderid)->update([
                        'status' => $status,
                        'pay_no' => $callbackData['transaction_id'],
                        'pay_time' => $pay_time
                    ]);

                    DB::table('device_pwd')->insert([
                        "sn" => $new_sn,
                        "device_id" => $dataObj['device_id'],
                        "orderid" => $orderid,
                        "password" => $newPassword,
                        "created_at" => date('Y-m-d H:i:s'),
                        "updated_at" => date('Y-m-d H:i:s')
                    ]);

                    DB::commit();
                    Log::info('notify success:' . var_export($callbackData, true));
                    echo $return['xml'];
                }catch(QueryException $ex) {
                    DB::rollback();
                    Log::info('notify fail:' . var_export($callbackData, true));
                    echo '';
                }
            }
        }
        exit;
    }

    public function autoClose(){
        $status = self::NO_PAY;
        $orderObj = new Order();
        $dataList = $orderObj->findByStatus($status);
        if($dataList) {
            foreach ($dataList as $item) {
                $new_status = self::CLOSED;
                $res = $orderObj->chnageStatus($new_status, $item['orderid']);
                if($res){
                    Log::info('2小时未支付自动关闭订单:' . var_export($item['orderid'], true));
                }
            }
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

    private function usefulTime($signal_id){
        $time = 0;
        if($signal_id == 0){
            $time = 30 * 60;
        }
        elseif($signal_id == 1){
            $time = 60 * 60;
        }
        elseif($signal_id == 2){
            $time = 2 *60 * 60;
        }
        elseif($signal_id == 3){
            $time = 3 *60 * 60;
        }
        elseif($signal_id == 4){
            $time = 4 *60 * 60;
        }
        elseif($signal_id == 5){
            $time = 5 *60 * 60;
        }
        elseif($signal_id == 6){
            $time = 8 *60 * 60;
        }
        elseif($signal_id == 7){
            $time = 12 *60 * 60;
        }
        elseif($signal_id == 8){
            $time = 16 *60 * 60;
        }
        elseif($signal_id == 9){
            $time = 25 *60 * 60;
        }

        return $time;
    }

    public function password(Request $request){
        $orderid = $request->input('orderid');
        $orderObj = new Order();
        $pwdObj = new Password();
        $lastPassword = $pwdObj->pwdByOrderid($orderid);
        $orderData = $orderObj->findByOrderid($orderid);
        if($lastPassword && $orderData){
            $time = $this->usefulTime($orderData['signal_id']);
            return UtilService::format_data(self::AJAX_SUCCESS, '获取成功', ['json'=>json_encode(array("pwd"=>$lastPassword->password, "time"=>$time))]);
        }
        else{
            return UtilService::format_data(self::AJAX_FAIL, '获取失败', '');
        }
    }
}
