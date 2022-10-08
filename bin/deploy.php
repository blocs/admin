<?php

/**
 * Copyright (C) 2010 LINEAR JAPAN Co., Ltd. All Rights Reserved.
 *
 * This source code or any portion thereof must not be
 * reproduced or used in any manner whatsoever.
 */
define('COMPOSER_DIR', str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname(__FILE__).'/../../../../')));

$file_loc = COMPOSER_DIR.'/storage/blocs_update.json';
if (is_file($file_loc)) {
    $update_json_data = json_decode(file_get_contents($file_loc), true);
} else {
    $update_json_data = [];
}

/* Copy directory */

foreach (['app', 'config', 'public', 'resources'] as $target_dir) {
    $update_json_data = _copy_dir(COMPOSER_DIR.'/vendor/blocs/admin/'.$target_dir, COMPOSER_DIR.'/'.$target_dir, $update_json_data);
    echo <<< END_of_TEXT
Copy "{$target_dir}"

END_of_TEXT;
}

ksort($update_json_data);
file_put_contents($file_loc, json_encode($update_json_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) && chmod($file_loc, 0666);

/* Private function */

function _copy_dir($dir_name, $new_dir, $update_json_data)
{
    is_dir($new_dir) || mkdir($new_dir, 0777, true) && chmod($new_dir, 0777);

    if (!(is_dir($dir_name) && $files = scandir($dir_name))) {
        return $update_json_data;
    }

    foreach ($files as $file) {
        if ('.' == substr($file, 0, 1) && '.gitkeep' != $file && '.htaccess' != $file) {
            continue;
        }

        if (is_dir($dir_name.'/'.$file)) {
            $update_json_data = _copy_dir($dir_name.'/'.$file, $new_dir.'/'.$file, $update_json_data);
        } else {
            $update_json_data = _copy_file($dir_name.'/'.$file, $new_dir.'/'.$file, $update_json_data);
        }
    }

    return $update_json_data;
}

function _copy_file($original_file, $target_file, $update_json_data)
{
    $original_file = str_replace(DIRECTORY_SEPARATOR, '/', realpath($original_file));
    $new_contents = file_get_contents($original_file);

    $file_key = substr($target_file, strlen(COMPOSER_DIR));
    if (is_file($target_file) && filesize($target_file)) {
        $target_file = str_replace(DIRECTORY_SEPARATOR, '/', realpath($target_file));
        $old_contents = file_get_contents($target_file);
        if ($new_contents === $old_contents) {
            $update_json_data[$file_key] = md5($new_contents);
        } elseif (isset($update_json_data[$file_key]) && $update_json_data[$file_key] === md5($old_contents)) {
            file_put_contents($target_file, $new_contents) && chmod($target_file, 0666);
            $update_json_data[$file_key] = md5($new_contents);
        } else {
            echo <<< END_of_TEXT
\e[7;31m"{$target_file}" already exists.\e[m

END_of_TEXT;
        }
    } else {
        // ファイルを意図的に消した時はコピーしない
        if (empty($update_json_data[$file_key])) {
            file_put_contents($target_file, $new_contents) && chmod($target_file, 0666);
            $target_file = str_replace(DIRECTORY_SEPARATOR, '/', realpath($target_file));
            $update_json_data[$file_key] = md5($new_contents);
        }
    }

    return $update_json_data;
}

/* End of file */
