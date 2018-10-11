<?php

namespace App\Http\Middleware;

use Closure;

class Activity
{
    //前置操作
    public function handle($request, Closure $next, $guard = null)
    {
        if (time() < strtotime('2017-06-15')) {
            return redirect('member/activity0');
        }
        elseif (time() > strtotime('2017-06-18')) {
            return redirect('member/activity2');
        }

        return $next($request);
    }
}
