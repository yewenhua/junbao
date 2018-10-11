<?php

/**
 * 微信支付
 */

return [
    'appid' => env('WXPAY_APPID',"wx15fdc36bd1791df0"),
    'appsecret' => env('WXPAY_APPSECRET',"a7577b800e11aa8bfafd2b97ab3136d5"),
    'mchid' => env('WXPAY_MCHID',"1511228171"),
    'WxPayKey' => env('WXPAY_KEY',"4d5b358f56b8558afb15674b4136ca43"),
    'SSLCERT_PATH' => dirname(dirname(__FILE__)).'/wxpaycer/apiclient_cert.pem',
    'SSLKEY_PATH' => dirname(dirname(__FILE__)).'/wxpaycer/apiclient_key.pem',
    'token' => '08525b857f40f7b87ee4a0206e8e318f'
];