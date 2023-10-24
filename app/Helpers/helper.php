<?php

if (!function_exists('lang')) {
    function lang($message)
    {
        return \Blocs\Lang::get($message);
    }
}

if (!function_exists('prefix')) {
    function prefix()
    {
        return \Blocs\Common::routePrefix();
    }
}
