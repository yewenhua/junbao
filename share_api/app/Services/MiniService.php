<?php

namespace App\Services;
use UtilService;
use Illuminate\Support\Facades\Log;

/**
 * 微信小程序
 */

class MiniService
{
    public function getOpenidAndSessionkey($code){
        $appid = config('mini.appid');
        $appsecret = config('mini.appsecret');
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=".$appid."&secret=".$appsecret."&js_code=".$code."&grant_type=authorization_code";

        $result = UtilService::curl_get($url);
        if($result&& isset($result['openid'])){
            return $result;
        }
        else{
            return null;
        }
    }

    public function getAccessToken() {
        $appId = config('mini.appid');
        $appSecret = config('mini.appsecret');
        $data = json_decode(file_get_contents(dirname(__FILE__)."/mini_access_token.json"));
        if ($data == null || empty($data) || $data->expire_time < time()) {
            if ($data == null || empty($data)){
                //accesstoken为空
                $data = (object)array();
            }

            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appId."&secret=".$appSecret;
            $result = UtilService::curl_get($url);
            if($result && isset($result['access_token'])){
                //请求接口成功
                $accessToken = $result['access_token'];
                $data->expire_time = time() + 6000;
                $data->access_token = $accessToken;
                $fp = fopen(dirname(__FILE__)."/mini_access_token.json", "w");
                fwrite($fp, json_encode($data));
                fclose($fp);
            }
            else{
                //请求接口失败
                $accessToken = null;
                $fileName = dirname(__FILE__)."/mini_access_token.json";
                if(file_exists($fileName)){
                    //accesstoken清空
                    file_put_contents($fileName, '');
                }
            }
        }
        else {
            //accesstoken有效
            $accessToken = $data->access_token;
        }

        return $accessToken;
    }

    public function templateMsg($params){
        header("content-type:text/html; charset=UTF-8");
        $postData = array(
            'touser' => $params['openid'],
            'template_id' => $params['templateid'],
            'page' => $params['page'],
            'form_id' => $params['formid'],

            'data' =>array(
                'keyword1'=>array("value"=>$params['keyword1'], "color"=>"#173177"),
                'keyword2'=>array("value"=>$params['keyword2'], "color"=>"#173177"),
                'keyword3'=>array("value"=>$params['keyword3'], "color"=>"#173177"),
                'keyword4'=>array("value"=>$params['keyword4'], "color"=>"#173177"),
                'keyword5'=>array("value"=>$params['keyword5'], "color"=>"#173177"),
            ),
        );

        $access_token = $this->getAccessToken();
        if($access_token != null){
            $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.$access_token;
            $postData = $this->json_encode_ex($postData);
            $return = UtilService::curl_post($url, $postData);
            if(isset($return['$return']) && $return['errcode'] != 0){
                return false;
            }
            else{
                return true;
            }
        }
        else{
            return false;
        }
    }

    private function json_encode_ex($value)
    {
        if (version_compare(PHP_VERSION,'5.4.0','<'))
        {
            $str = $this->my_encode_json($value);
            return $str;
        }
        else
        {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
    }

    //5.3之前中文转码
    private function my_encode_json($str) {
        return urldecode(json_encode($this->my_url_encode($str)));
    }

    private function my_url_encode($str) {
        if(is_array($str)) {
            foreach($str as $key=>$value) {
                $str[urlencode($key)] = $this->my_url_encode($value);
            }
        } else {
            $str = urlencode($str);
        }

        return $str;
    }
}