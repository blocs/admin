<?php

namespace App\Admin\Middleware;

use Closure;

class StaticGenerator
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $staticPath = realpath(dirname(public_path()).'/static');
        if (!$staticPath) {
            return $response;
        }

        $staticName = self::getStaticName($_SERVER['REQUEST_URI']);
        $staticLoc = $staticPath.$staticName;

        if (200 != $response->status()) {
            // 対象ファイルを削除
            file_exists($staticLoc) && unlink($staticLoc);

            return $response;
        }

        // 保存するディレクトリを準備
        $staticDir = dirname($staticLoc);
        is_dir($staticDir) || mkdir($staticDir, 0777, true) && chmod($staticDir, 0777);

        $content = self::convertStaticContent($response->content());
        file_put_contents($staticLoc, $content);

        return $response;
    }

    // 静的ファイル名を生成
    private static function getStaticName($staticLoc)
    {
        $uriForPath = request()->getUriForPath('');
        $staticName = str_replace($uriForPath, '', $staticLoc);
        $staticName = str_replace('?', '_', $staticName);

        return $staticName;
    }

    // HTMLのリンクを静的ファイル名に置換
    private static function convertStaticContent($content)
    {
        $htmlArray = \Blocs\Compiler\Parser::parse($content);

        $convertedContent = '';
        while ($htmlArray) {
            $htmlBuff = array_shift($htmlArray);

            if (!is_array($htmlBuff)) {
                $convertedContent .= $htmlBuff;
                continue;
            }

            $attrArray = $htmlBuff['attribute'];
            foreach (['href', 'src'] as $arrtibute) {
                if (!isset($attrArray[$arrtibute])) {
                    continue;
                }

                // 相対パスに変換
                $staticName = self::getStaticName($attrArray[$arrtibute]);
                $htmlBuff['raw'] = str_replace($attrArray[$arrtibute], $staticName, $htmlBuff['raw']);
            }

            $convertedContent .= $htmlBuff['raw'];
        }

        return $convertedContent;
    }
}
