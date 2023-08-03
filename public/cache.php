<?php

define('BLOCS_CACHE_SECOND', 2);
$cacheFile = __DIR__.'/../storage/framework/cache/'.md5($_SERVER['REQUEST_URI']);
if (file_exists($cacheFile) && time() < filemtime($cacheFile)) {
    echo file_get_contents($cacheFile);
    exit;
} else {
    file_exists($cacheFile) && touch($cacheFile, time() + BLOCS_CACHE_SECOND);
}
