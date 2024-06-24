<?php

if (!function_exists('lang')) {
    function lang($message)
    {
        return Blocs\Lang::get($message);
    }
}

if (!function_exists('prefix')) {
    function prefix()
    {
        return Blocs\Common::routePrefix();
    }
}

if (!function_exists('convert')) {
    function convert($str, $formName = null, $template = null)
    {
        isset($template) && Blocs\Option::set($template, $formName);

        if (isset($formName)) {
            return Blocs\Common::convertDefault($str, $formName);
        }

        return Blocs\Common::convertDefault($str);
    }
}

if (!function_exists('getOption')) {
    function getOption($formName, $template)
    {
        return Blocs\Option::get($template, $formName);
    }
}

if (!function_exists('addOption')) {
    function addOption($formName, $optionList)
    {
        Blocs\Option::add($formName, $optionList);
    }
}

if (!function_exists('setOption')) {
    function setOption($formName, $template)
    {
        Blocs\Option::set($template, $formName);
    }
}

if (!function_exists('clearCache')) {
    function clearCache($routeName, $arguments = [])
    {
        $staticFile = public_path(route($routeName, $arguments, false).'/index.html');
        file_exists($staticFile) && unlink($staticFile);
    }
}
