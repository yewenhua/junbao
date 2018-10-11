<?php

/*
 * wxpay
 */

return [
    'appid' => env('APPID',"wx15fdc36bd1791df0"),
    'appsecret' => env('APPSECRET',"a7577b800e11aa8bfafd2b97ab3136d5"),
    'api_domain' => 'http://wx.junbao518.com',
    'wechat_menu' => array(
        'button'=>array(
            array(
                "type"=>"view",
                "name"=>"历史消息",
                "url"=> "https://mp.weixin.qq.com/mp/profile_ext?action=home&__biz=MzU3OTY4NDc4Mg==&scene=126#wechat_redirect"
            ),
            array(
                "type"=>"scancode_push",
                "name"=>"扫码充电",
                "key" => "scancode_push"
            ),
            array(
                'name'=>"更多",
                'sub_button'=>array(
                    array(
                        "type"=>"view",
                        "name"=>"代理商登录",
                        "url"=> "http://admin.junbao518.com"
                    ),
                    array(
                        "type"=>"view",
                        "name"=>"商家登录",
                        "url"=> "http://admin.junbao518.com"
                    ),
                    array(
                        "type"=>"view",
                        "name"=>"业务员登录",
                        "url"=> "http://wx.junbao518.com/devices/add"
                    ),
                    array(
                        "type" => "click",
                        "name" => "在线客服",
                        "key" => "V3003_SERVICE"
                    )
                )
            )
        )
    )
];