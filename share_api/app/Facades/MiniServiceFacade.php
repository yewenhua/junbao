<?php namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class MiniServiceFacade extends Facade {

    protected static function getFacadeAccessor() {
        //创建一个facade，可以将某个service注册个门面，这样，使用的时候就不需要麻烦地use 了
        //返回服务容器绑定类的别名
        return 'MiniService'; //service privider 里返回的实例名称
    }

}