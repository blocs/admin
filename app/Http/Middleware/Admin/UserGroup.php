<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Support\Facades\Route;

class UserGroup
{
    public function handle($request, Closure $next)
    {
        // 必要な権限を取得
        $current_name = Route::currentRouteName();
        $config_group = config('group');
        $current_groups = [];
        foreach ($config_group as $group_name => $route_names) {
            if (in_array($current_name, $route_names)) {
                $current_groups[] = $group_name;
            }
        }

        if (!empty($current_groups)) {
            // 自分の権限を取得
            $_user_data = \Auth::user();
            $my_groups = explode("\t", $_user_data['group']);

            $permit = false;
            foreach ($my_groups as $my_group) {
                in_array($my_group, $current_groups) && $permit = true;
            }
            $permit || abort(403);
        }

        return $next($request);
    }
}
