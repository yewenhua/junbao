<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Gate;
use UtilService;

class Privilege
{
    const AJAX_NO_AUTH = 99999;

    public function handle($request, Closure $next)
    {
        $path = $request->input('path');
        $permission = \App\Permission::where('desc', $path)->first();
        if ($permission && Gate::allows($path, $permission)) {
            return $next($request);
        }
        else{
            return UtilService::format_data(self::AJAX_NO_AUTH, '没有权限', '');
        }
    }
}
