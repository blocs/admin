<?php

if (! function_exists('lang')) {
    function lang(...$messages)
    {
        return Blocs\Lang::get(implode(':', $messages));
    }
}

if (! function_exists('val')) {
    function val($str, $formName = null, $template = null)
    {
        if (isset($template)) {
            Blocs\Option::set($template, $formName);
        }

        $arguments = [$str];

        if (isset($formName)) {
            $arguments[] = $formName;
        }

        return Blocs\Common::convertDefault(...$arguments);
    }
}

if (! function_exists('prefix')) {
    function prefix()
    {
        return Blocs\Common::routePrefix();
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
        return Blocs\Option::get($template, $formName);
    }
}

if (! function_exists('addOption')) {
    function addOption($formName, $optionList)
    {
        Blocs\Option::add($formName, $optionList);
    }
}

if (! function_exists('setOption')) {
    function setOption($formName, $template)
    {
        Blocs\Option::set($template, $formName);
    }
}
