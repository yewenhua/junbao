<?php

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::group(['namespace' => 'API', 'middleware'=>['cros']], function () {
    Route::post('login', 'AuthController@login');
    Route::get('logout', 'AuthController@logout');
    Route::post('register', 'AuthController@register');

    Route::group(['middleware' => 'jwt.auth'], function () {
        Route::get('data', function () {
            // Just acting as a ping service.
            return response()->json(['data' => '9999'], 200);
        });
    });
});


Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['jwt.auth', 'permission']], function () {
    //用户
    Route::get('admins', 'AdminController@index'); //用户列表页
    Route::get('admins/create', 'AdminController@create'); //用户创建页
    Route::post('admins/store', 'AdminController@store'); //创建用户保存
    Route::get('admins/{user}/role', 'AdminController@role');  //用户角色页   路由模型绑定
    Route::post('admins/{user}/role', 'AdminController@storeRole'); //保存用户角色页   路由模型绑定
    Route::post('admins/delete', 'AdminController@delete');
    Route::post('admins/batchdelete', 'AdminController@batchdelete');
    Route::post('admins/chgpwd', 'AdminController@chgpwd');
    Route::post('admins/check', 'AdminController@check');
    Route::post('admins/resetpwd', 'AdminController@resetpwd');
    Route::get('admins/children', 'AdminController@children');
    Route::get('admins/allchild', 'AdminController@allchild');
    Route::post('admins/childdelete', 'AdminController@childdelete');


    //角色
    Route::get('roles', 'RoleController@index');   //列表展示页面
    Route::get('roles/create', 'RoleController@create'); //创建页面
    Route::post('roles/store', 'RoleController@store'); //创建提交页面
    Route::get('roles/{role}/permission', 'RoleController@permission'); //角色权限页面  路由模型绑定
    Route::post('roles/{role}/permission', 'RoleController@storePermission'); //角色权限提交页面  路由模型绑定
    Route::post('roles/delete', 'RoleController@delete');
    Route::get('roles/lists', 'RoleController@lists');


    //权限
    Route::get('permissions', 'PermissionController@index');
    Route::get('permissions/create', 'PermissionController@create');
    Route::post('permissions/store', 'PermissionController@store');
    Route::post('permissions/delete', 'PermissionController@delete');
    Route::post('permissions/batchdelete', 'PermissionController@batchdelete');
    Route::get('allpermissions', 'PermissionController@permissions');

    //设备
    Route::get('devices', 'DeviceController@index');
    Route::post('devices/store', 'DeviceController@store');
    Route::post('devices/delete', 'DeviceController@delete');
    Route::post('devices/batchdelete', 'DeviceController@batchdelete');
    Route::get('devices/consume', 'DeviceController@consume');
    Route::get('devices/statistic', 'DeviceController@statistic');
    Route::post('devices/batchcancel', 'DeviceController@batchcancel');
    Route::post('devices/batchreset', 'DeviceController@batchreset');
    Route::post('devices/batchassign', 'DeviceController@batchassign');
    Route::post('devices/diyassign', 'DeviceController@diyassign');

    //运维信息
    Route::get('maintenance/ptype', 'MaintenanceController@ptype');
    Route::get('maintenance/ptopen', 'MaintenanceController@ptopen');
    Route::post('maintenance/ptstore', 'MaintenanceController@ptstore');
    Route::post('maintenance/ptbatchelete', 'MaintenanceController@ptbatchelete');
    Route::get('maintenance/pbrand', 'MaintenanceController@pbrand');
    Route::get('maintenance/pbopen', 'MaintenanceController@pbopen');
    Route::post('maintenance/pbstore', 'MaintenanceController@pbstore');
    Route::post('maintenance/pbbatchelete', 'MaintenanceController@pbbatchelete');
    Route::get('maintenance/pricetpl', 'MaintenanceController@pricetpl');
    Route::get('maintenance/priceopen', 'MaintenanceController@priceopen');
    Route::post('maintenance/pricestore', 'MaintenanceController@pricestore');
    Route::post('maintenance/pricebatchelete', 'MaintenanceController@pricebatchelete');
    Route::get('maintenance/ptplagent', 'MaintenanceController@ptplagent');
    Route::get('maintenance/lists', 'MaintenanceController@lists');
    Route::post('maintenance/check', 'MaintenanceController@check');
    Route::get('maintenance/agents', 'MaintenanceController@agents');
    Route::post('maintenance/batchdelete', 'MaintenanceController@batchdelete');

    //设置
    Route::get('setting/discount', 'SettingController@index');
    Route::post('setting/discount', 'SettingController@discount');

    //现金记录相关
    Route::get('cash/cashlog', 'CashController@cashlog');
    Route::get('cash/money', 'CashController@money');
    Route::post('cash/takecash', 'CashController@takecash');
    Route::post('cash/checkcash', 'CashController@checkcash');
    Route::get('cash/statistic', 'CashController@statistic');
});

Route::group(['namespace' => 'API'], function () {
    Route::get('auth/refresh', 'AuthController@refresh');
});

Route::group(['middleware' => ['jwt.auth']], function () {
    Route::get('admins/info', 'AdminController@userInfo');
    Route::get('admins/lists', 'AdminController@lists');

    Route::get('devices/consumeexcel', 'DeviceController@consumeExcel');
    Route::get('devices/statisticexcel', 'DeviceController@statisticExcel');
});


Route::get('tree/lists', 'TreeController@lists');
Route::post('tree/insert', 'TreeController@insert');
Route::post('tree/update', 'TreeController@update');
Route::post('tree/delete', 'TreeController@delete');
Route::post('tree/singlefile', 'TreeController@singlefile');
Route::get('tree/column', 'TreeController@column');
Route::get('tree/children', 'TreeController@children');
Route::get('tree/home', 'TreeController@home');

Route::get('share/wxlogin', 'ShareController@wxlogin');
Route::get('share/signin', 'ShareController@signinByOpenid');
Route::post('share/order', 'ShareController@order');

Route::get('news', 'NewsController@index');
Route::post('news/store', 'NewsController@store');
Route::post('news/delete', 'NewsController@delete');
Route::post('news/batchdelete', 'NewsController@batchdelete');
Route::get('news/home', 'NewsController@home');
Route::get('news/detail', 'NewsController@detail');

Route::get('cases', 'CaseController@index');
Route::post('cases/store', 'CaseController@store');
Route::post('cases/delete', 'CaseController@delete');
Route::post('cases/batchdelete', 'CaseController@batchdelete');
Route::get('cases/home', 'CaseController@home');

Route::get('banners', 'BannerController@index');
Route::post('banners/store', 'BannerController@store');
Route::post('banners/delete', 'BannerController@delete');
Route::post('banners/batchdelete', 'BannerController@batchdelete');
Route::get('banners/home', 'BannerController@home');

Route::get('devices/qrcode', 'DeviceController@qrcode');
Route::post('devices/tpls', 'DeviceController@tpls');




