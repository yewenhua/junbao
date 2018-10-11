<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use UtilService;
use MiniService;   //引入 MiniService 门面
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Http\Models\Mini;
use App\Order;
use App\Events\OrderShipped;

class ShareController extends Controller
{
    const AJAX_SUCCESS = 0;
    const AJAX_FAIL = -1;
    const AJAX_NO_DATA = 10001;
    const AJAX_NO_AUTH = 99999;

    /**
     * 做活动用
     * 小程序login
     */
    public function wxlogin(Request $request) {
        $code = $request->input('code');
        $result = MiniService::getOpenidAndSessionkey($code);
        if($result && isset($result['openid']) && isset($result['session_key'])){
            $third_session = md5($result['openid'].'lucky');
            $key = $third_session;
            $param = array(
                "openid" => $result['openid'],
                "session_key" => $result['session_key']
            );

            if (Cache::has($key)) {
                Cache::forget($key);
            }

            //将session_key写进缓存，add 方法只会在缓存项不存在的情况下添加数据到缓存（分钟数）
            $res = Cache::add($key, $param, 60 * 6);
            if ($res) {
                return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', ['third_session'=>$third_session]);
            } else {
                return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
            }
        }
        else{
            return UtilService::format_data(self::AJAX_NO_DATA, '请求失败', '');
        }
    }

    /**
     * 从缓存中读取openid和sessionkey
     */
    private function getSessionByKey($third_session) {
        $res = Cache::get($third_session);
        if($res){
            return $res;
        }
        else{
            return null;
        }
    }

    //通过openid登录
    public function signinByOpenid(Request $request)
    {
        $key = $request->input('third_session');
        $parent_key = $request->input('code');
        $info = $this->getSessionByKey($key);
        $openid = $info['openid'];
        $session_key = $info['session_key'];

        if($openid) {
            $obj = new Mini();
            $row = $obj->findByOpenid($openid);
            if($row){
                $children = $obj->findChildrenByCode($row['code']);
                $row->children_num = $children ? count($children) : 0;
                return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $row);
            }
            else{
                $code = $this->random_str(5);
                $res = $obj->create([
                    'openid' => $openid,
                    'parent_key' => $parent_key,
                    'code' => $code
                ]);

                if($res){
                    $res->children_num = 0;
                    return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $res);
                }
                else{
                    return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
                }
            }
        }
        else{
            //session失效
            return UtilService::format_data(self::AJAX_NO_DATA, 'session失效', '');
        }
    }

    public function random_str($length)
    {
        //生成一个包含 大写英文字母, 小写英文字母, 数字 的数组
        $arr = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));

        $str = '';
        $arr_len = count($arr);
        for ($i = 0; $i < $length; $i++)
        {
            $rand = mt_rand(0, $arr_len-1);
            $str.=$arr[$rand];
        }

        return $str;
    }

    public function order(Request $request)
    {
        $templateid = config('mini.templateid');
        $formid = $request->input('formid');
        $key = $request->input('third_session');
        $username = $request->input('username');
        $goodsname = $request->input('goodsname');
        $mobile = $request->input('mobile');
        $city = $request->input('city');
        $info = $this->getSessionByKey($key);
        $openid = $info['openid'];

        if($openid) {
            $orderObj = new Order();
            $row = $orderObj->findByOpenid($openid);
            if(!$row) {
                $obj = new Mini();
                $self = $obj->findByOpenid($openid);
                $orderId = date('YmdHis') . rand(100000, 999999);
                $order = Order::firstOrCreate([
                    'order_id' => $orderId,
                    'status' => 0,
                    'username' => $username,
                    'goodsname' => $goodsname,
                    'mobile' => $mobile,
                    'city' => $city,
                    'openid' => $openid,
                    'recomend_code' => $self ? $self['parent_key'] : ''
                ]);

                if ($order) {
                    $remark = $username.'您好，VOC指纹锁' . $goodsname . '预约成功';
                    $param = array(
                        'openid' => $openid,
                        'templateid' => $templateid,
                        'formid' => $formid,
                        'page' => 'pages/index/index',
                        'keyword1' => $goodsname,
                        'keyword2' => $username,
                        'keyword3' => $mobile,
                        'keyword4' => $city,
                        'keyword5' => $remark
                    );
                    MiniService::templateMsg($param);

                    //触发事件
                    event(new OrderShipped($order));
                    return UtilService::format_data(self::AJAX_SUCCESS, '操作成功', $order);
                } else {
                    return UtilService::format_data(self::AJAX_FAIL, '操作失败', '');
                }
            }
            else{
                return UtilService::format_data(self::AJAX_FAIL, '您已提交过订单', '');
            }
        }
        else{
            //session失效
            return UtilService::format_data(self::AJAX_NO_DATA, 'session失效', '');
        }
    }
}
