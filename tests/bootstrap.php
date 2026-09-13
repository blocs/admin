<?php

$packageRoot = dirname(__DIR__);
$autoloadCandidates = [
    dirname($packageRoot, 2).'/test-admin/vendor/autoload.php',
    dirname($packageRoot, 2).'/autoload.php',
    $packageRoot.'/vendor/autoload.php',
];

foreach ($autoloadCandidates as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;
        break;
    }
}

spl_autoload_register(static function (string $class) use ($packageRoot): bool {
    $prefix = 'Blocs\\';
    if (! str_starts_with($class, $prefix)) {
        return false;
    }

    $file = $packageRoot.'/src/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
    if (is_file($file)) {
        require $file;

        return true;
    }

    return false;
}, true, true);

if (! defined('BLOCS_CACHE_DIR')) {
    $cacheDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'blocs-admin'.DIRECTORY_SEPARATOR;
    is_dir($cacheDir) || mkdir($cacheDir, 0777, true);
    define('BLOCS_CACHE_DIR', $cacheDir);
}
