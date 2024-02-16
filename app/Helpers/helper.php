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

if (!function_exists('convert')) {
    function convert($str, $formName = null, $template = null)
    {
        isset($template) && \Blocs\Option::set($template, $formName);

        if (isset($formName)) {
            return \Blocs\Common::convertDefault($str, $formName);
        }

        return \Blocs\Common::convertDefault($str);
    }
}

if (!function_exists('getOption')) {
    function getOption($formName, $template)
    {
        return \Blocs\Option::get($template, $formName);
    }
}

if (!function_exists('addOption')) {
    function addOption($formName, $optionList)
    {
        \Blocs\Option::add($formName, $optionList);
    }
}

if (!function_exists('setOption')) {
    function setOption($formName, $template)
    {
        \Blocs\Option::set($template, $formName);
    }
}

if (!function_exists('doc')) {
    function doc(...$argvs)
    {
        if (!isset($GLOBALS['DOC_GENERATOR'])) {
            return;
        }

        if (count($argvs) > 2) {
            $in = $argvs[0] ?? [];
            $main = $argvs[1] ?? [];
            $out = $argvs[2] ?? [];
            $validate = $argvs[3] ?? [];
        } elseif (count($argvs) > 1) {
            $in = $argvs[0] ?? [];
            $main = $argvs[1] ?? [];
            $out = [];
            $validate = [];
        } else {
            $in = [];
            $main = $argvs[0] ?? [];
            $out = [];
            $validate = [];
        }

        is_array($in) || $in = [$in => []];
        is_array($main) || $main = [$main];
        is_array($out) || $out = [$out => []];
        is_array($validate) || $validate = [$validate => []];

        $backtrace = debug_backtrace();
        $GLOBALS['DOC_GENERATOR'][] = [
            'path' => $backtrace[0]['file'],
            'function' => $backtrace[1]['function'],
            'line' => $backtrace[0]['line'],
            'in' => $in,
            'main' => $main,
            'out' => $out,
            'validate' => $validate,
        ];
    }
}
