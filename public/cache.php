<?php

define('BLOCS_CACHE_SECOND', 2);
$cacheFile = __DIR__.'/../storage/framework/cache/'.$_SERVER['REQUEST_METHOD'].md5($_SERVER['REQUEST_URI']);

if (file_exists($cacheFile)) {
    // flashの有無を確認
    if (isset($_COOKIE['laravel_session_id'])) {
        $sessionFile = __DIR__.'/../storage/framework/sessions/'.$_COOKIE['laravel_session_id'];
        if (file_exists($sessionFile)) {
            $flash = unserialize(file_get_contents($sessionFile))['_flash'];

            // flash有り、キャッシュしない
            empty($flash['old']) || define('BLOCS_CACHE_IGNORE', true);
        }
    }

    if (!defined('BLOCS_CACHE_IGNORE')) {
        if (!defined('BLOCS_CACHE_SECOND') || time() < filemtime($cacheFile)) {
            // キャッシュ出力
            echo file_get_contents($cacheFile);
            exit;
        }
    }

    defined('BLOCS_CACHE_SECOND') && touch($cacheFile, time() + BLOCS_CACHE_SECOND);
}
