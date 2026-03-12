<?php

use Blocs\Common;
use Blocs\Lang;
use Blocs\Option;

if (! function_exists('lang')) {
    function lang(...$messages)
    {
        return Lang::get(implode(':', $messages));
    }
}

if (! function_exists('val')) {
    function val($str, $formName = null, $template = null)
    {
        if (isset($template)) {
            Option::set($template, $formName);
        }

        $arguments = [$str];

        if (isset($formName)) {
            $arguments[] = $formName;
        }

        return Common::convertDefault(...$arguments);
    }
}

if (! function_exists('prefix')) {
    function prefix()
    {
        return Common::routePrefix();
    }
}

if (! function_exists('path')) {
    function path($name, $parameters = [])
    {
        return route($name, $parameters, false);
    }
}

if (! function_exists('getOption')) {
    function getOption($formName, $template)
    {
        return Option::get($template, $formName);
    }
}

if (! function_exists('addOption')) {
    function addOption($formName, $optionList)
    {
        Option::add($formName, $optionList);
    }
}

if (! function_exists('setOption')) {
    function setOption($formName, $template)
    {
        Option::set($template, $formName);
    }
}
