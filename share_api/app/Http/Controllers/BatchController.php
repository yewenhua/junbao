<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Models\Device;
use App\Http\Models\Pricetpl;
use QrCode;
use UtilService;
use WechatService;

class BatchController extends Controller
{
    public function insert(){
        return false;
        $ptpls = Pricetpl::findMany([1]);
        for($i=12205; $i<14200; $i++){
            $param = array(
                "sn"=>$i,
                "type"=>'充电器',
                "brand"=>'骏宝闪充',
                "isopen"=>1,
                "category"=>'手机充电器',
                "address"=>'长沙',
                "location"=>'长沙'
            );
            $obj = Device::create($param);
            if ($obj) {
                $this->createimg($obj->sn);
                //要增加的价格模板
                foreach ($ptpls as $ptpl) {
                    $obj->grantPtpl($ptpl);
                }
            }
            if($i % 1000 == 0 ){
                sleep(1);
            }
        }

        echo 'OK';
        die;
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

    public function menu(){
        $res = WechatService::createMenu();
        return $res;
    }

    public function migrate(){
        $lists_1 = \App\User::whereNull('deleted_at')
            ->where('is_child', 1)
            ->where('type', 'agent')
            ->get();

        foreach ($lists_1 as $item){
            $item->router = config('user.admin_id').','.$item->parent_id.','.$item->id;
            $item->owner_id = $item->parent_id;
            $item->save();
        }

        $lists_2 = \App\User::whereNull('deleted_at')
            ->where('is_child', 0)
            ->where('type', 'agent')
            ->where('level', 2)
            ->get();

        foreach ($lists_2 as $item){
            $item->router = config('user.admin_id').','.$item->id;
            $item->owner_id = config('user.admin_id');
            $item->save();
        }

        echo 'OK';
    }

    public function straight(){
        $lists_1 = \App\User::whereNull('deleted_at')
            ->where('level', 2)
            ->where('type', 'agent')
            ->get();

        foreach ($lists_1 as $item){
            $roles = $item->roles;
            if($roles && count($roles) > 0 && $roles[0]->name == '直营客户') {
                $item->type = 'straight';
                $item->save();
            }
        }

        echo 'OK';
    }
}
