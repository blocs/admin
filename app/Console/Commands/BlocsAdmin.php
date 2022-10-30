<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BlocsAdmin extends Command
{
    protected $signature = 'blocs:admin';
    protected $description = 'Deploy blocs/admin package';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        define('ROOT_DIR', str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname(__FILE__).'/../../../')));
        define('STUB_DIR', ROOT_DIR.'/vendor/blocs/admin/stubs');

        /* アップデート状況把握のため更新情報を取得 */

        $file_loc = ROOT_DIR.'/storage/blocs_update.json';
        if (is_file($file_loc)) {
            $update_json_data = json_decode(file_get_contents($file_loc), true);
        } else {
            $update_json_data = [];
        }

        /* ルーティング設定 */

        $blocs_routes_loc = STUB_DIR.'/routes/web.php';
        $laravel_routes_loc = ROOT_DIR.'/routes/web.php';
        if (is_file($blocs_routes_loc) && $laravel_routes_loc) {
            $laravel_routes = file_get_contents($laravel_routes_loc);
            if (false === strpos($laravel_routes, 'Auth::routes();')) {
                // ルーティングを追加
                $blocs_routes = file_get_contents($blocs_routes_loc);
                file_put_contents($laravel_routes_loc, "\n".$blocs_routes, FILE_APPEND);
            }
        }

        /* モデルを置き換え */

        $laravel_user_loc = ROOT_DIR.'/app/User.php';
        if (is_file($laravel_user_loc)) {
            $laravel_user = file_get_contents($laravel_user_loc);
            if (false === strpos($laravel_user, 'SoftDeletes;')) {
                unlink($laravel_user_loc);
            }
        }

        /* ディレクトリを配置 */

        foreach (['app', 'config', 'database', 'public', 'resources'] as $target_dir) {
            $update_json_data = self::_copy_dir(STUB_DIR.'/'.$target_dir, ROOT_DIR.'/'.$target_dir, $update_json_data);
            echo <<< END_of_TEXT
Copy "{$target_dir}"

END_of_TEXT;
        }

        ksort($update_json_data);
        file_put_contents($file_loc, json_encode($update_json_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) && chmod($file_loc, 0666);
    }

    /* Private function */

    private static function _copy_dir($dir_name, $new_dir, $update_json_data)
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
                $update_json_data = self::_copy_dir($dir_name.'/'.$file, $new_dir.'/'.$file, $update_json_data);
            } else {
                $update_json_data = self::_copy_file($dir_name.'/'.$file, $new_dir.'/'.$file, $update_json_data);
            }
        }

        return $update_json_data;
    }

    private static function _copy_file($original_file, $target_file, $update_json_data)
    {
        $original_file = str_replace(DIRECTORY_SEPARATOR, '/', realpath($original_file));
        $new_contents = file_get_contents($original_file);
        $file_key = substr($target_file, strlen(ROOT_DIR));

        if (!is_file($target_file) || !filesize($target_file)) {
            // コピー先にファイルがない
            if (!empty($update_json_data[$file_key])) {
                // ファイルを意図的に消した時はコピーしない
                return $update_json_data;
            }

            file_put_contents($target_file, $new_contents) && chmod($target_file, 0666);
            $target_file = str_replace(DIRECTORY_SEPARATOR, '/', realpath($target_file));
            $update_json_data[$file_key] = md5($new_contents);

            return $update_json_data;
        }

        // コピー先にファイルがある
        $target_file = str_replace(DIRECTORY_SEPARATOR, '/', realpath($target_file));
        $old_contents = file_get_contents($target_file);

        if ($new_contents === $old_contents) {
            // ファイルが更新されていない
            $update_json_data[$file_key] = md5($new_contents);

            return $update_json_data;
        }

        if (isset($update_json_data[$file_key]) && $update_json_data[$file_key] === md5($old_contents)) {
            // ファイルが更新された
            file_put_contents($target_file, $new_contents) && chmod($target_file, 0666);
            $update_json_data[$file_key] = md5($new_contents);

            return $update_json_data;
        }

        // 違う内容のファイルがある
        echo <<< END_of_TEXT
\e[7;31m"{$target_file}" already exists.\e[m

END_of_TEXT;

        return $update_json_data;
    }
}