<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class WxpayService
{
    private $publicPayParameters = array();
    private $jsapiParameters = array();
    private $companyPayParameters = array();
    private $returnParameters = array();
    private $scanPayParameters = array();
    private $prepay_url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';        //统一下单
    private $orderquery_url = 'https://api.mch.weixin.qq.com/pay/orderquery';      //查询订单
    private $refund_url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';       //申请退款
    private $closeorder_url = 'https://api.mch.weixin.qq.com/pay/closeorder';      //关闭订单
    private $refundquery_url = 'https://api.mch.weixin.qq.com/pay/refundquery';    //查询退款
    private $downloadbill_url = 'https://api.mch.weixin.qq.com/pay/downloadbill';  //下载对账单
    private $transfers_url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';       //企业付款
    private $transfersquery_url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gettransferinfo ';     //查询企业付款
    private $notify_url = "";
    private $response;
    private $result;
    private $curl_timeout;

    /**
     * 企业付款接口
     * @param $openid
     * @param $true_name
     * @param $amount
     * @param $desc
     */
    public function transfers($openid, $true_name, $amount, $desc){
        $this->companyPayParameters = array();
        $this->companyPayParameters['mch_appid']  = config('wxpay.appid');
        $this->companyPayParameters['mchid']     = config('wxpay.mchid');
        $this->companyPayParameters['nonce_str']  = $this->createNoncestr();
        $this->companyPayParameters['partner_trade_no'] = 'WD'.date('YmdHis') . rand(100000, 999999);
        $this->companyPayParameters['openid'] = $openid;
        $this->companyPayParameters['check_name'] = 'NO_CHECK';
        $this->companyPayParameters['re_user_name'] = $true_name;
        $this->companyPayParameters['amount'] = $amount*100;
        $this->companyPayParameters['desc'] = $desc;
        $this->companyPayParameters['spbill_create_ip'] = $this->get_client_ip();

        //生成签名结果
        $mysign = $this->getSign($this->companyPayParameters);
        //签名结果与签名方式加入请求提交参数组中
        $this->companyPayParameters['sign'] = $mysign;

        //准备XML数据库和curl相关参数
        $xmlData = $this->arrayToXml($this->companyPayParameters);
        Log::info('transfers param:'.$xmlData);
        $result = $this->postXmlSSLCurl($xmlData, $this->transfers_url, $second=30);
        $rtn = $this->xmlToArray($result);
        Log::info('transfers result:'.var_export($rtn, true));
        return $rtn;
    }

    /**
     * 查询企业付款接口
     */
    public function getTransferInfo($partner_trade_no){
        //最终返回值
        $this->companyPayParameters = array();
        $this->companyPayParameters['appid']  = config('wxpay.appid');
        $this->companyPayParameters['mch_id']     = config('wxpay.mchid');
        $this->companyPayParameters['nonce_str']  = $this->createNoncestr();
        $this->companyPayParameters['partner_trade_no'] = $partner_trade_no;

        //生成签名结果
        $mysign = $this->getSign($this->companyPayParameters);
        //签名结果与签名方式加入请求提交参数组中
        $this->companyPayParameters['sign'] = $mysign;

        //准备XML数据库和curl相关参数
        $xmlData = $this->arrayToXml($this->companyPayParameters);
        Log::info('getTransferInfo param:'.$xmlData);
        $result = $this->postXmlSSLCurl($xmlData, $this->transfersquery_url, $second=30);
        $rtn = $this->xmlToArray($result);
        Log::info('getTransferInfo result:'.var_export($rtn, true));
        return $rtn;
    }

    /**
     * 预支付订单
     * 使用统一支付接口，获取prepay_id ...
     * @param $openid
     * @param $type  JSAPI NATIVE
     */
    public function prepay($orderid, $openid, $type, $fee) {
        $this->publicPayParameters = array();
        $this->setPayParameters('appid', config('wxpay.appid'));//公众账号ID
        $this->setPayParameters('mch_id', config('wxpay.mchid'));//商户号
        $this->setPayParameters('nonce_str', $this->createNoncestr());//随机字符串

        $this->setPayParameters('spbill_create_ip', $this->get_client_ip());
        $this->setPayParameters('openid', $openid);  //trade_type 为 JSAPI 时，此参数必传
        $this->setPayParameters('body', 'pay goods'); //商品描述
        $this->setPayParameters('out_trade_no', $orderid);
        $this->setPayParameters('total_fee', $fee);  //订单总金额，单位为分，不 能带小数点
        $this->setPayParameters('notify_url', $this->notify_url);
        $this->setPayParameters('trade_type', $type);
        $this->publicPayParameters["sign"] = $this->getSign($this->publicPayParameters);//签名
        $xml = $this->arrayToXml($this->publicPayParameters);
        Log::info('prepay param:'.$xml);

        $this->response = $this->postXmlCurlNew($xml, $this->prepay_url, $this->curl_timeout);
        $this->result = $this->xmlToArray($this->response);
        Log::info('prepay result:'.var_export($this->result, true));
        if(isset($this->result["prepay_id"])){
            return $this->result;
        }
        else{
            return null;
        }
    }

    public function setNotifyUrl($val){
        $this->notify_url = $val;
    }

    /**
     * notify ...
     */
    public function notify() {
        $this->returnParameters = array();
        $postXML      = $GLOBALS['HTTP_RAW_POST_DATA'];
        $callbackData = $this->xmlToArray($postXML);
        Log::info('notify callback param:'.var_export($callbackData, true));
        $status = '';
        if($this->checkSign($callbackData) != FALSE){
            if(isset($callbackData['return_code']) && $callbackData['return_code'] == 'SUCCESS' && isset($callbackData['result_code']) && $callbackData['result_code'] == 'SUCCESS')
            {
                $this->setReturnParameters("return_code","SUCCESS");   //返回状态码
                $this->setReturnParameters("return_msg","OK");
                $status = 'success';
            }
            else{
                $this->setReturnParameters("return_code","FAIL");   //返回状态码
                $this->setReturnParameters("return_msg","ERROR");
                $status = 'fail';
            }
        }
        else{
            $this->setReturnParameters("return_code","签名失败");
            $this->setReturnParameters("return_msg","ERROR");
            $status = 'sign_error';
        }
        $xml = $this->arrayToXml($this->returnParameters);

        return array('status'=>$status, 'callback'=>$callbackData, 'xml'=>$xml);
    }

    /**
     * 查询订单
     * @param $transaction_id
     * @param $out_trade_no
     */
    public function orderquery($transaction_id, $out_trade_no) {
        $this->publicPayParameters = array();
        $this->setPayParameters('appid', config('wxpay.appid'));//公众账号ID
        $this->setPayParameters('mch_id', config('wxpay.mchid'));//商户号
        $this->setPayParameters('nonce_str', $this->createNoncestr());//随机字符串
        $this->setPayParameters('transaction_id', $transaction_id);  //微信的订单号，优先使用
        if($transaction_id){
            $this->setPayParameters('transaction_id', $transaction_id);  //微信的订单号，优先使用
        }
        elseif($out_trade_no){
            $this->setPayParameters('out_trade_no', $out_trade_no); //商户系统内部的订单号，当没提供transaction_id时需要传这个
        }
        $this->publicPayParameters["sign"] = $this->getSign($this->publicPayParameters);//签名
        $xml = $this->arrayToXml($this->publicPayParameters);
        Log::info('orderquery param:'.$xml);
        $this->response = $this->postXmlCurl($xml, $this->orderquery_url, $this->curl_timeout);
        $this->result = $this->xmlToArray($this->response);
        Log::info('orderquery result:'.var_export($this->result, true));

        if(isset($this->result["return_code"]) && $this->result["return_code"] == 'SUCCESS'){
            if(isset($this->result["result_code"]) && $this->result["result_code"] == 'SUCCESS'){
                $res = $this->result;
                foreach($res as $key=>$item){
                    if((is_array($item) && empty($item)) || $item == ''){
                        unset($res[$key]);
                    }
                }

                if($this->checkSign($res) != FALSE){
                    return $this->result;
                }
                else{
                    return false;
                }
            }
            else{
                return false;
            }
        }
        else{
            return false;
        }
    }

    /**
     * 关闭订单 ...
     * @param unknown_type $out_trade_no
     */
    public function closeorder($out_trade_no) {
        $this->publicPayParameters = array();
        $this->setPayParameters('appid', config('wxpay.appid'));//公众账号ID
        $this->setPayParameters('mch_id', config('wxpay.mchid'));//商户号
        $this->setPayParameters('nonce_str', $this->createNoncestr());//随机字符串
        $this->setPayParameters('out_trade_no', $out_trade_no);
        $this->publicPayParameters["sign"] = $this->getSign($this->publicPayParameters);//签名
        $xml = $this->arrayToXml($this->publicPayParameters);
        Log::info('closeorder param:'.$xml);
        $this->curl_timeout = 30;
        $this->response = $this->postXmlCurl($xml, $this->closeorder_url, $this->curl_timeout);
        $this->result = $this->xmlToArray($this->response);
        Log::info('closeorder result:'.var_export($this->result, true));

        if(isset($this->result["return_code"]) && $this->result["return_code"] == 'SUCCESS'){
            $res = $this->result;
            foreach($res as $key=>$item){
                if((is_array($item) && empty($item)) || $item == ''){
                    unset($res[$key]);
                }
            }

            if($this->checkSign($res) != FALSE){
                return $this->result;
            }
            else{
                return null;
            }
        }
        else{
            return null;
        }
    }

    /**
     * 退款
     * @param $transaction_id
     * @param $out_trade_no
     * @param $total_fee
     * @param $refund_fee
     */
    public function refund($transaction_id, $out_trade_no, $total_fee, $refund_fee) {
        $this->publicPayParameters = array();
        $out_refund_no = date('YmdHis', time()).rand(10000, 99999);
        $this->setPayParameters('appid', config('wxpay.appid'));//公众账号ID
        $this->setPayParameters('mch_id', config('wxpay.mchid'));//商户号
        $this->setPayParameters('nonce_str', $this->createNoncestr());//随机字符串
        if($transaction_id){
            $this->setPayParameters('transaction_id', $transaction_id);  //微信的订单号，优先使用
        }
        elseif($out_trade_no){
            $this->setPayParameters('out_trade_no', $out_trade_no); //商户系统内部的订单号，当没提供transaction_id时需要传这个
        }
        $this->setPayParameters('out_refund_no', $out_refund_no);  //商户系统内部的退款单号，商户系统内部唯一，同一退款单号多次请求只退一笔
        $this->setPayParameters('total_fee', $total_fee);   //订单总金额，单位为分，只能为整数
        $this->setPayParameters('refund_fee', $refund_fee);  //退款总金额，订单总金额，单位为分，只能为整数
        $this->setPayParameters('op_user_id', config('wxpay.mchid'));  //操作员帐号, 默认为商户号
        $this->publicPayParameters["sign"] = $this->getSign($this->publicPayParameters);//签名
        $xml = $this->arrayToXml($this->publicPayParameters);
        Log::info('refund param:'.$xml);
        $this->response = $this->postXmlSSLCurl($xml, $this->refund_url, $this->curl_timeout);
        $this->result = $this->xmlToArray($this->response);
        Log::info('refund result:'.var_export($this->result, true));

        if(isset($this->result["return_code"]) && $this->result["return_code"] == 'SUCCESS' && isset($this->result["result_code"]) && $this->result["result_code"] == 'SUCCESS'){
            $res = $this->result;
            foreach($res as $key=>$item){
                if((is_array($item) && empty($item)) || $item == ''){
                    unset($res[$key]);
                }
            }

            if($this->checkSign($res) != FALSE){
                return $this->result;
            }
            else{
                return null;
            }
        }
        else{
            return null;
        }
    }

    /**
     * 退款查询
     * @param $transaction_id
     * @param $out_trade_no
     * @param $out_refund_no
     * @param $refund_id
     */
    public function refundquery($transaction_id, $out_trade_no, $out_refund_no, $refund_id) {
        $this->publicPayParameters = array();
        $this->setPayParameters('appid', config('wxpay.appid'));//公众账号ID
        $this->setPayParameters('mch_id', config('wxpay.mchid'));//商户号
        $this->setPayParameters('nonce_str', $this->createNoncestr());//随机字符串
        if($transaction_id){
            $this->setPayParameters('transaction_id', $transaction_id);  //四选一
        }
        elseif($out_trade_no){
            $this->setPayParameters('out_trade_no', $out_trade_no);      //四选一
        }
        elseif($out_refund_no){
            $this->setPayParameters('out_refund_no', $out_refund_no);    //四选一
        }
        elseif($refund_id){
            $this->setPayParameters('refund_id', $refund_id);            //四选一
        }

        $this->publicPayParameters["sign"] = $this->getSign($this->publicPayParameters);//签名
        $xml = $this->arrayToXml($this->publicPayParameters);
        Log::info('refundquery param:'.$xml);
        $this->response = $this->postXmlCurl($xml, $this->refundquery_url, $this->curl_timeout);
        $this->result = $this->xmlToArray($this->response);
        Log::info('refundquery result:'.var_export($this->result, true));

        if(isset($this->result["return_code"]) && $this->result["return_code"] == 'SUCCESS' && isset($this->result["result_code"]) && $this->result["result_code"] == 'SUCCESS'){
            $res = $this->result;
            foreach($res as $key=>$item){
                if((is_array($item) && empty($item)) || $item == ''){
                    unset($res[$key]);
                }
            }

            if($this->checkSign($res) != FALSE){
                return $this->result;
            }
            else{
                return null;
            }
        }
        else{
            return null;
        }
    }

    /**
     * 下载对账单
     */
    public function downloadbill() {
        $this->publicPayParameters = array();
        $this->setPayParameters('appid', config('wxpay.appid'));//公众账号ID
        $this->setPayParameters('mch_id', config('wxpay.mchid'));//商户号
        $this->setPayParameters('nonce_str', $this->createNoncestr());//随机字符串
        $this->setPayParameters('bill_date', date('Ymd'));            //四选一

        $this->publicPayParameters["sign"] = $this->getSign($this->publicPayParameters);//签名
        $xml = $this->arrayToXml($this->publicPayParameters);
        Log::info('downloadbill param:'.$xml);
        $this->response = $this->postXmlCurl($xml, $this->downloadbill_url, $this->curl_timeout);
        $this->result = $this->xmlToArray($this->response);
        Log::info('downloadbill result:'.var_export($this->result, true));
        if(isset($this->result["return_code"]) && $this->result["return_code"] == 'SUCCESS'){
            return $this->result;
        }
        else{
            return null;
        }
    }

    /**
     * 生成短连接
     * @param $long_url
     */
    public function getShortUrl($long_url)
    {
        $url = "https://api.mch.weixin.qq.com/tools/shorturl";
        $this->publicPayParameters = array();
        $this->setPayParameters('appid', config('wxpay.appid'));//公众账号ID
        $this->setPayParameters('mch_id', config('wxpay.mchid'));//商户号
        $this->setPayParameters('nonce_str', $this->createNoncestr());//随机字符串
        $this->setPayParameters('long_url', $long_url);//随机字符串
        $this->publicPayParameters["sign"] = $this->getSign($this->publicPayParameters);//签名
        $xml = $this->arrayToXml($this->publicPayParameters);
        $this->response = $this->postXmlCurl($xml, $url, $this->curl_timeout);
        $this->result = $this->xmlToArray($this->response);
        if(isset($this->result["short_url"])){
            return $this->result["short_url"];
        }
        else{
            return null;
        }
    }

    /**
     * 生成二维码参数ＵＲＬ
     * @param $product_id
     */
    public function createQrcodeUrl($product_id) {
        $time_stamp = time();
        if($product_id == null)
        {
            die("缺少Native支付二维码链接必填参数product_id！"."<br>");
        }
        $this->setScanParameters('appid', config('wxpay.appid'));
        $this->setScanParameters('mch_id', config('wxpay.mchid'));
        $this->setScanParameters('nonce_str', $this->createNoncestr());
        $this->setScanParameters('time_stamp', "$time_stamp");
        $this->setScanParameters('product_id', $product_id);
        $this->setScanParameters('sign', $this->getSign($this->scanPayParameters));//签名
        $bizString = $this->formatBizQueryParaMap($this->parameters, false);
        $long_url = "weixin://wxpay/bizpayurl?".$bizString;
        $short_url = $this->getShortUrl($long_url);
        $url_array = array(
            'product_url'=>$long_url, //传到前端生成二维码
            'long_url'=>$long_url,
            'short_url'=>$short_url
        );
        return $url_array;
    }

    /**
     * 扫码支付回调--模式一
     * 扫码生成订单
     */
    public function native_call() {
        $this->returnParameters = array();
        $postXML      = $GLOBALS['HTTP_RAW_POST_DATA'];
        $callbackData = $this->xmlToArray($postXML);
        if($this->checkSign($callbackData) != FALSE){
            $product_id = $callbackData['product_id'];
            $openid = $callbackData['openid'];
            $type = 'NATIVE';
            $rtn = $this->prepay($openid, $type);
            if($rtn != null){
                $prepay_id = $rtn["prepay_id"];
                $nonce_str = $callbackData['nonce_str'];
                $sign = $callbackData['sign'];

                $this->setReturnParameters("return_code","SUCCESS");   //返回状态码
                $this->setReturnParameters("result_code","SUCCESS");   //业务结果
                $this->setReturnParameters("prepay_id","$prepay_id");  //预支付ID
                $this->setReturnParameters('appid', config('wxpay.appid'));
                $this->setReturnParameters('mch_id', config('wxpay.mchid'));
                $this->setReturnParameters("nonce_str","$nonce_str");  //微信返回的随机字符串
                $this->setReturnParameters("sign","$sign");  //返回数据签名
            }
            else{
                $this->setReturnParameters("return_code","FAIL");//返回状态码
                $this->setReturnParameters("return_msg","prepay_id FAIL");//返回信息
            }
        }
        else{
            $this->setReturnParameters("return_code","FAIL");//返回状态码
            $this->setReturnParameters("return_msg","签名失败");//返回信息
        }
        $xml = arrayToXml($this->returnParameters);
        echo $xml;
    }

    /**
     * 扫码支付--模式二
     * 根据已生成订单二维码直接支付，扫码本身不生成订单
     */
    public function native_dynamic_qrcode() {
        $code_url = null;
        $this->returnParameters = array();
        $postXML      = $GLOBALS['HTTP_RAW_POST_DATA'];
        $callbackData = $this->xmlToArray($postXML);
        if($this->checkSign($callbackData) != FALSE){
            $product_id = $callbackData['product_id'];
            $openid = $callbackData['openid'];
            $type = 'NATIVE';
            $rtn = $this->prepay($openid, $type);
            if($rtn != null){
                $code_url = $rtn['code_url'];
            }
        }
        return $code_url;
    }

    /**
     * 	作用：设置前端jsapi的参数
     */
    public function getJsapiParameters($prepay_id)
    {
        $jsApiObj = array();
        $jsApiObj["appId"] = config('wxpay.appid');
        $timeStamp = time();
        $jsApiObj["timeStamp"] = "$timeStamp";
        $jsApiObj["nonceStr"] = $this->createNoncestr();
        $jsApiObj["package"] = "prepay_id=$prepay_id";
        $jsApiObj["signType"] = "MD5";
        $jsApiObj["paySign"] = $this->getSign($jsApiObj);
        $this->jsapiParameters = json_encode($jsApiObj);

        return $this->jsapiParameters;
    }

    /**
     * 设置预支付订单参数
     * @param $data
     */
    private function setPayParameters($key, $value) {
        $this->publicPayParameters[$this->trimString($key)] = $this->trimString($value);
        return $this->publicPayParameters;
    }

    /**
     * 设置返回参数
     * @param $data
     */
    private function setReturnParameters($key, $value) {
        $this->returnParameters[$this->trimString($key)] = $this->trimString($value);
        return $this->returnParameters;
    }

    /**
     * 设置扫码支付参数
     * @param $data
     */
    private function setScanParameters($key, $value) {
        $this->scanPayParameters[$this->trimString($key)] = $this->trimString($value);
        return $this->scanPayParameters;
    }

    /**
     * 获取客户端IP...
     */
    private function get_client_ip()
    {
        if ($_SERVER['REMOTE_ADDR']) {
            $cip = $_SERVER['REMOTE_ADDR'];
        }
        elseif (getenv("REMOTE_ADDR")) {
            $cip = getenv("REMOTE_ADDR");
        }
        elseif (getenv("HTTP_CLIENT_IP")) {
            $cip = getenv("HTTP_CLIENT_IP");
        }
        else {
            $cip = "192.168.0.1";
        }
        return $cip;
    }

    private function trimString($value)
    {
        $ret = null;
        if (null != $value)
        {
            $ret = $value;
            if (strlen($ret) == 0)
            {
                $ret = null;
            }
        }
        return $ret;
    }

    /**
     * 	作用：产生随机字符串，不长于32位
     */
    private function createNoncestr( $length = 32 )
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }

    /**
     * 	作用：格式化参数，签名过程需要使用
     */
    private function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v)
        {
            if($urlencode)
            {
                $v = urlencode($v);
            }
            //$buff .= strtolower($k) . "=" . $v . "&";
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0)
        {
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        return $reqPar;
    }

    /**
     * 	作用：生成签名
     */
    private function getSign($Obj)
    {
        foreach ($Obj as $k => $v)
        {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //签名步骤二：在string后加入KEY
        $String = $String."&key=".config('wxpay.WxPayKey');
        //签名步骤三：MD5加密
        $String = md5($String);
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        return $result_;
    }

    private function checkSign($data)
    {
        $tempData = $data;
        unset($tempData['sign']);
        $sign = $this->getSign($tempData);//本地签名
        if ($data['sign'] == $sign) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * 	作用：array转xml
     */
    private function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
            if (is_numeric($val))
            {
                $xml.="<".$key.">".$val."</".$key.">";

            }
            else
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
        }
        $xml.="</xml>";
        return $xml;
    }

    /**
     * 	作用：将xml转为array
     */
    private function xmlToArray($xml)
    {
        //将XML转为array
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }

    private function postXmlCurlNew($xml, $url, $second=6){
        $ch = curl_init();
        $curlVersion = curl_version();
        $ua = "WXPaySDK/3.0.9 (".PHP_OS.") PHP/".PHP_VERSION." CURL/".$curlVersion['version']." "
            .config('wxpay.mchid');

        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        curl_setopt($ch,CURLOPT_USERAGENT, $ua);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            Log::info('postXmlCurl出错:'.$error);
            return false;
        }
    }

    /**
     * 	作用：以post方式提交xml到对应的接口url
     */
    private function postXmlCurl($xml, $url, $second=30)
    {
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data)
        {
            curl_close($ch);
            return $data;
        }
        else
        {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error"."<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            Log::info('postXmlCurl出错:'.$error);
            curl_close($ch);
            return false;
        }
    }

    /**
     * 	作用：使用证书，以post方式提交xml到对应的接口url
     */
    private function postXmlSSLCurl($xml,$url,$second=30)
    {
        $ch = curl_init();
        //超时时间
        curl_setopt($ch,CURLOPT_TIMEOUT,$second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        //设置header
        curl_setopt($ch,CURLOPT_HEADER,FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
        //设置证书
        //使用证书：cert 与 key 分别属于两个.pem文件
        //默认格式为PEM，可以注释
        curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
        curl_setopt($ch,CURLOPT_SSLCERT, config('wxpay.SSLCERT_PATH'));
        //默认格式为PEM，可以注释
        curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
        curl_setopt($ch,CURLOPT_SSLKEY, config('wxpay.SSLKEY_PATH'));
        //post提交方式
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        }
        else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error"."<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            Log::info('postXmlSSLCurl 出错:'.$error);
            curl_close($ch);
            return false;
        }
    }
}