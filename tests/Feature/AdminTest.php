<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminTest extends TestCase
{
    private $response;
    private $data;

    public function test(): void
    {
        // Excel形式
        $scriptFile = __DIR__.'/script.xlsx';
        $scriptList = $this->parseExcel($scriptFile);

        // JSON形式でexport
        //      file_put_contents(__DIR__.'/script.json', json_encode($scriptList, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n") && chmod($scriptFile, 0666);

        // JSON形式
        //      $scriptFile = __DIR__.'/script.json';
        //      $scriptList = json_decode(file_get_contents($scriptFile), true);

        empty($scriptList) && $this->outputFatal("Error: {$scriptFile}");
        $this->outputMessage("Script: {$scriptFile}");

        // キャッシュクリア
        \Artisan::call('view:clear');

        foreach ($scriptList as $scriptNo => $testScript) {
            // メッセージ表示
            if (!empty($testScript['description'])) {
                // データを置換
                $message = ($scriptNo + 1).'.'.$this->replaceDataKeyList($testScript['description']);
                $this->outputMessage($message);
            }

            $this->executeScript($scriptNo, $testScript);
        }
    }

    private function executeScript($scriptNo, $testScript)
    {
        $this->response = $this;

        $assertList = [];
        foreach ($testScript as $method => $arguments) {
            // データを置換
            'description' !== $method && $arguments = $this->replaceDataKeyList($arguments);

            if (in_array($method, ['description', 'method', 'uri', 'query', 'file', 'data', 'dump'])) {
                $testScript[$method] = $arguments;
                continue;
            }

            if (!strncmp($method, 'assert', 6)) {
                // Assertは後でまとめて実行
                $assertList[$method] = $arguments;
                continue;
            }

            $this->response = $this->executeMethod($method, $arguments);
        }

        // HTTP Request
        if ('post' === $testScript['method']) {
            $postQuery = empty($testScript['query']) ? [] : $testScript['query'];
            $this->response = $this->executeMethod('post', [$testScript['uri'], $postQuery]);
        } elseif ('upload' === $testScript['method']) {
            // アップロードファイルの生成
            $uploadFile = new UploadedFile(__DIR__.'/'.$testScript['file'], basename($testScript['file']));
            $this->response = $this->executeMethod('post', [$testScript['uri'], [
                'upload' => $uploadFile,
            ]]);
        } else {
            $this->response = $this->executeMethod('get', $testScript['uri']);
        }

        $this->prepareAssert($scriptNo, $testScript);

        // データの準備
        isset($testScript['data']) && $this->prepareData($testScript['data']);

        // コンテンツをdump
        if (isset($testScript['dump'])) {
            if (200 === $this->response->getStatusCode()) {
                file_put_contents($testScript['dump'], $this->response->getContent()) && chmod($testScript['dump'], 0666);
                $this->outputMessage(" -> {$testScript['dump']}");
            } else {
                $this->outputMessage(' -> '.$this->response->getStatusCode());
            }
        }

        if (empty($testScript['assertInvalid_0']) && 302 === $this->response->getStatusCode()) {
            $this->response = $this->followRedirects($this->response);
        }

        // Assert
        foreach ($assertList as $method => $arguments) {
            list($method) = explode('_', $method, 2);

            if ('assertSee' == $method) {
                // HTMLをエスケープしない
                $this->executeMethod($method, [$arguments, false]);
            } else {
                $this->executeMethod($method, $arguments);
            }
        }
    }

    protected function prepareAssert($scriptNo, $testScript)
    {
        /*
        if (0 === $scriptNo) {
            dd($testScript);
        }
        */
    }

    private function executeMethod($method, $arguments)
    {
        if (is_array($arguments)) {
            return call_user_func_array([$this->response, $method], $arguments);
        }

        return call_user_func([$this->response, $method], $arguments);
    }

    private function prepareData($dataList)
    {
        foreach ($dataList as $dataKey => $value) {
            if ('lastInsertId' === $value) {
                // storeしたidを取得
                $this->data[$dataKey] = \DB::getPdo()->lastInsertId();
                continue;
            }

            if ('content' === $value) {
                // 取得したコンテンツを保存
                $this->data[$dataKey] = $this->response->getContent();
                continue;
            }

            if (false == strpos($value, '.')) {
                // 値を代入
                $this->data[$dataKey] = $value;
                continue;
            }

            list($type, $value) = explode('.', $value, 2);
            if ('fake' === $type) {
                // ダミーデータを作成
                $this->data[$dataKey] = fake()->{$value}();
                continue;
            }

            if ('json' === $type) {
                $jsonData = $this->response->getContent();
                $jsonList = json_decode($jsonData, true);

                // JSONデータを保存
                isset($jsonList[$dataKey]) && $this->data[$dataKey] = $jsonList[$dataKey];
                continue;
            }

            if ('maxId' === $type) {
                // ダミーデータを作成
                $this->data[$dataKey] = \DB::table($value)->max('id');
                continue;
            }
        }
    }

    private function replaceDataKeyList($queryList)
    {
        if (!is_array($queryList)) {
            is_string($queryList) && $queryList = $this->replaceDataKey($queryList);

            return $queryList;
        }

        foreach ($queryList as $key => $value) {
            $queryList[$key] = $this->replaceDataKeyList($value);
        }

        return $queryList;
    }

    private function replaceDataKey($query)
    {
        preg_match_all('/\<([^\>]+)\>/', $query, $dataKeyList);

        foreach ($dataKeyList[1] as $dataKey) {
            if (!strncmp($dataKey, 'fake.', 5)) {
                // ダミーデータを作成
                $dataKey = substr($dataKey, 5);
                $this->data[$dataKey] = fake()->{$dataKey}();

                // データを置換
                $query = preg_replace("/<fake.{$dataKey}>/", $this->data[$dataKey], $query, 1);

                continue;
            }

            if (is_file(__DIR__.'/'.$dataKey)) {
                // データをファイルから読み込み
                $this->data[$dataKey] = $this->replaceDataKey(file_get_contents(__DIR__.'/'.$dataKey));
            }

            if (isset($this->data[$dataKey])) {
                // データを置換
                $query = str_replace("<{$dataKey}>", $this->data[$dataKey], $query);

                continue;
            }

            // データが未定義
            $this->outputFatal('Data Error: Not defined '.$dataKey);
        }

        return $query;
    }

    private function parseExcel($scriptExcel)
    {
        $excel = new \Blocs\Excel($scriptExcel);

        $scriptList = [];
        for ($scriptNo = 0; $description = $excel->get(1, 0, $scriptNo + 1); ++$scriptNo) {
            // description
            $scriptList[$scriptNo]['description'] = $description;

            // method
            $method = $excel->get(1, 1, $scriptNo + 1);
            empty($method) && $method = 'get';
            $scriptList[$scriptNo]['method'] = $method;

            // uri
            $scriptList[$scriptNo]['uri'] = $excel->get(1, 2, $scriptNo + 1);

            // query
            $query = $excel->get(1, 3, $scriptNo + 1);
            if (!empty($query)) {
                $query = json_decode($query, true);
                empty($query) && $this->outputFatal('JSON Error: Line'.($scriptNo + 1).' query');
                $scriptList[$scriptNo]['query'] = $query;
            }

            // file
            ($file = $excel->get(1, 4, $scriptNo + 1)) && $scriptList[$scriptNo]['file'] = $file;

            // assertSee
            if ($assertSeeList = $excel->get(1, 5, $scriptNo + 1)) {
                $assertSeeList = explode("\n", $assertSeeList);

                foreach ($assertSeeList as $assertNum => $assertSee) {
                    $scriptList[$scriptNo]['assertSee_'.$assertNum] = $assertSee;
                }
            }

            // assertStatus
            ($assertStatus = $excel->get(1, 6, $scriptNo + 1)) && $scriptList[$scriptNo]['assertStatus'] = intval($assertStatus);

            // assertInvalid
            if ($assertInvalidList = $excel->get(1, 7, $scriptNo + 1)) {
                $assertInvalidList = explode("\n", $assertInvalidList);

                foreach ($assertInvalidList as $assertNum => $assertInvalid) {
                    $scriptList[$scriptNo]['assertInvalid_'.$assertNum] = $assertInvalid;
                }
            }

            // data
            $data = $excel->get(1, 8, $scriptNo + 1);
            if (!empty($data)) {
                $data = json_decode($data, true);
                empty($data) && $this->outputFatal('JSON Error: Line'.($scriptNo + 1).' data');
                $scriptList[$scriptNo]['data'] = $data;
            }

            // dump
            ($dump = $excel->get(1, 9, $scriptNo + 1)) && $scriptList[$scriptNo]['dump'] = $dump;
        }

        return $scriptList;
    }

    private function outputMessage($message)
    {
        echo "{$message}\n";
        ob_flush();
    }

    private function outputFatal($message)
    {
        echo "\e[7;31m{$message}\e[m\n";
        exit;
    }
}
