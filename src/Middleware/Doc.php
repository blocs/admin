<?php

namespace Blocs\Middleware;

use Blocs\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class Doc
{
    private $keyword;
    private $neglect;
    private $indent;
    private $comment;

    public function handle(Request $request, \Closure $next): Response
    {
        // ドキュメントグローバル変数
        $GLOBALS['DOC_GENERATOR'] = [];

        $response = $next($request);

        // コントローラー、メソッドを取得
        $currentRouteAction = explode('\\', Route::currentRouteAction());
        $currentRouteAction = end($currentRouteAction);
        list($routeClass, $routeMethod) = explode('@', $currentRouteAction, 2);

        // エクセルを準備
        $excelPath = base_path("docs/{$currentRouteAction}.xlsx");
        copy(base_path('docs/format.xlsx'), $excelPath);
        $excel = new Excel($excelPath);

        // 設定読み込み
        $this->readConfig($routeClass, $routeMethod, $excel);

        $startLine = 5;
        $mainNo = 1;
        $indentNo = 1;
        $steps = $GLOBALS['DOC_GENERATOR'];

        if (count($steps)) {
            $endNo = count($steps) - 1;

            if (!$steps[$endNo]['in'] && 200 === $response->getStatusCode()) {
                // 画面表示の入力を記述
                $viewPath = str_replace(resource_path('views/'), '', $response->original->getPath());
                $viewPath && $steps[$endNo]['in'] = ['TEMPLATE' => $viewPath];
            }

            if (!$steps[$endNo]['out']) {
                // 画面表示の出力を記述
                if (200 === $response->getStatusCode()) {
                    $contents = str_replace(["\r\n", "\r", "\n"], '', $response->getContent());
                    if (preg_match('/<title>(.*?)<\/title>/i', $contents, $match)) {
                        $steps[$endNo]['out'] = ['HTML' => trim($match[1])];
                    } else {
                        $steps[$endNo]['out'] = 'HTML';
                    }
                }
            }
        }

        foreach ($steps as $stepNo => $step) {
            // 非表示行
            if (in_array($stepNo, $this->neglect)) {
                continue;
            }

            $maxLine = $startLine;

            // 入力を記述
            $line = $this->writeIn($startLine, $step, $excel);
            $line > $maxLine && $maxLine = $line;

            // 処理機能を記述
            $line = $this->writeMain($startLine, $step, $excel, $mainNo, $indentNo);
            $line > $maxLine && $maxLine = $line;

            // 出力を記述
            $line = $this->writeOut($startLine, $step, $excel);
            $line > $maxLine && $maxLine = $line;

            // 開始行更新
            $startLine = $maxLine;
        }

        $excel->name(1, $routeMethod)->save($excelPath);

        return $response;
    }

    private function writeIn($line, $step, $excel)
    {
        foreach ($step['in'] as $key => $items) {
            $excel->set(1, 'A', $line, $key);
            $excel->set(1, 'J', $line, '→');
            ++$line;

            is_array($items) || $items = array_filter([$items], 'strlen');
            foreach ($items as $item) {
                $excel->set(1, 'B', $line, $this->replaceInOut($item));
                ++$line;
            }
        }

        return ++$line;
    }

    private function writeMain($line, $step, $excel, &$mainNo, &$indentNo)
    {
        foreach ($step['main'] as $main) {
            $indent = in_array($line + 1, $this->indent);

            $comments = explode("\n", $main);
            $main = array_shift($comments);

            $column = $indent ? 'L' : 'K';
            if ($indent) {
                $mainIndentNo = ($mainNo - 1).'.'.$indentNo;

                // インデントあり
                $excel->set(1, $column, $line, $indentNo.') '.$this->replaceMain($main));
                ++$indentNo;
            } else {
                $mainIndentNo = $mainNo;

                // インデントなし
                $excel->set(1, $column, $line, $mainNo.'. '.$this->replaceMain($main));
                ++$mainNo;
                $indentNo = 1;
            }
            ++$line;

            // 追加コメントを記述
            $column = $indent ? 'M' : 'L';
            isset($this->comment[$mainIndentNo]) && $comments = array_merge($comments, explode("\n", $this->comment[$mainIndentNo]));
            foreach ($comments as $comment) {
                $excel->set(1, $column, $line, $this->replaceMain($comment));
                ++$line;
            }
        }

        // 処理の箇所を記述
        $path = str_replace(base_path('/'), '', $step['path']);
        $column = $indent ? 'M' : 'L';
        $excel->set(1, $column, $line, $path.'@'.$step['function'].':'.$step['line']);
        ++$line;

        return ++$line;
    }

    private function writeOut($line, $step, $excel)
    {
        foreach ($step['out'] as $key => $items) {
            $excel->set(1, 'AO', $line, '→');
            $excel->set(1, 'AP', $line, $key);
            ++$line;

            is_array($items) || $items = array_filter([$items], 'strlen');
            foreach ($items as $item) {
                $excel->set(1, 'AQ', $line, $this->replaceInOut($item));
                ++$line;
            }
        }

        return ++$line;
    }

    private function readConfig($routeClass, $routeMethod, $excel)
    {
        $config = [];
        $keyword = [];
        $neglect = [];
        $indent = [];
        $comment = [];

        if (file_exists(base_path('docs/common.php'))) {
            include base_path('docs/common.php');

            $keyword = $config['keyword'] ?? [];
            $neglect = $config['neglect'] ?? [];
            $comment = $config['comment'] ?? [];
        }

        if (file_exists(base_path('docs/'.$routeClass.'.php'))) {
            include base_path('docs/'.$routeClass.'.php');

            // class、method概要を記述
            isset($config['description']) && $excel->set(1, 'Z', '1', $config['description']);
            $excel->set(1, 'AU', '1', date('Y/m/d'));
            $excel->set(1, 'E', '2', $routeClass.'@'.$routeMethod);
            isset($config[$routeMethod]['description']) && $excel->set(1, 'Q', '2', $config[$routeMethod]['description']);

            // キーワードを取得
            isset($config['keyword']) && $keyword = array_merge($keyword, $config['keyword']);
            isset($config[$routeMethod]['keyword']) && $keyword = array_merge($keyword, $config[$routeMethod]['keyword']);

            // 非表示行を取得
            isset($config['neglect']) && $neglect = array_merge($neglect, $config['neglect']);
            isset($config[$routeMethod]['neglect']) && $neglect = array_merge($neglect, $config[$routeMethod]['neglect']);

            // インデント行を取得
            isset($config[$routeMethod]['indent']) && $indent = $this->getIndent($config[$routeMethod]['indent']);

            // 追加コメントを取得
            isset($config['comment']) && $comment = $this->mergeArray($comment, $config['comment']);
            isset($config[$routeMethod]['comment']) && $comment = $this->mergeArray($comment, $config[$routeMethod]['comment']);
        }

        $this->keyword = $keyword;
        $this->neglect = $neglect;
        $this->indent = $indent;
        $this->comment = $comment;
    }

    private function mergeArray($before, $after)
    {
        foreach ($after as $key => $value) {
            $before[$key] = $value;
        }

        return $before;
    }

    private function replaceInOut($item)
    {
        foreach ($this->keyword as $key => $value) {
            // キーワード置き換え
            $item = str_replace($key, $key.': '.$value, $item);
        }

        return $item;
    }

    private function replaceMain($item)
    {
        foreach ($this->keyword as $key => $value) {
            // キーワード置き換え
            $item = str_replace('<'.$key.'>', '<'.$value.'>', $item);
        }

        return $item;
    }

    private function getIndent($configIndents)
    {
        $indent = [];
        foreach ($configIndents as $configIndent) {
            if (preg_match('/^[0-9]+$/', $configIndent)) {
                // 行指定
                $indent[] = $configIndent;
            } elseif (preg_match('/^[0-9\-]+$/', $configIndent)) {
                // 範囲指定
                list($start, $end) = explode('-', $configIndent);
                $start || $start = 1;
                if ($start > $end) {
                    continue;
                }

                while ($start <= $end) {
                    $indent[] = intval($start);
                    ++$start;
                }
            }
        }

        return $indent;
    }
}
