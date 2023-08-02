<?php

$cacheSecond = 2;
$cacheFile = __DIR__.'/../storage/framework/cache/'.md5($_SERVER['REQUEST_URI']);
if (file_exists($cacheFile) && time() - filemtime($cacheFile) < $cacheSecond) {
    echo file_get_contents($cacheFile);
    exit;
} else {
    file_exists($cacheFile) && touch($cacheFile);
}
