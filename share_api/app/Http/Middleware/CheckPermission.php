<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Gate;
use UtilService;
use JWTAuth;

class CheckPermission
{
    const AJAX_NO_AUTH = 99999;
    private $privateKey = "233f4def5c875875";
    private $iv = "233f4def5c875875";

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $userObj = JWTAuth::parseToken()->authenticate();
        $path = $request->input('path');
        $path = urldecode($path); //前端用encodeURIComponent编码
        $path = $this->aesdecrypt($path);

        $privilege = $request->input('privilege');
        $privilege = urldecode($privilege); //前端用encodeURIComponent编码
        $privilege = $this->aesdecrypt($privilege);

        $permission = \App\Permission::where('desc', $path)->first();
        if ($permission && Gate::allows($path, $permission)) {
            $flag = false;
            foreach ($userObj->roles as $role) {
                foreach ($role->permissions as $permission){
                    if($privilege == 'read' && $permission->pivot->read_permission == 1){
                        $flag = true;
                        break;
                    }
                    elseif($privilege == 'add' && $permission->pivot->add_permission == 1){
                        $flag = true;
                        break;
                    }
                    elseif($privilege == 'delete' && $permission->pivot->delete_permission == 1){
                        $flag = true;
                        break;
                    }
                    elseif($privilege == 'update' && $permission->pivot->update_permission == 1){
                        $flag = true;
                        break;
                    }
                }

                if($flag){
                    break;
                }
            }

            if($flag){
                return $next($request);
            }
            else{
                return response(UtilService::format_data(self::AJAX_NO_AUTH, '没有权限', ''), 402);
            }
        }
        else{
            return response(UtilService::format_data(self::AJAX_NO_AUTH, '没有权限', ''), 402);
        }
    }

    private function aesencrypt($str){
        $data = json_encode($str);
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->privateKey, $data, MCRYPT_MODE_CBC, $this->iv));

        return $encrypted;
    }

    private function aesdecrypt($str){
        $encryptedData = base64_decode($str);
        $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->privateKey, $encryptedData, MCRYPT_MODE_CBC, $this->iv);
        $decrypted = rtrim($decrypted,"\0");
        $decrypted = json_decode($decrypted);

        return $decrypted;
    }
}
