<?php

namespace Tests\Browser\Admin;

use Laravel\Dusk\Browser;

trait MacroTrait
{
    protected function macro(): void
    {
        // テーブルのセルをクリック
        Browser::macro('clickTableCell', function ($rows, $cols, $tag = null) {
            $selector = '//*[@id="inmaincontents"]/form[2]/div/div[1]/table/tbody/tr['.$rows.']/td['.$cols.']';
            isset($tag) && $selector .= '/'.$tag;

            return $this->clickAtXPath($selector);
        });

        // ファイルアップロード
        Browser::macro('uploadFile', function ($fileLoc) {
            return $this->attach('#file_upload > div.upload-buttonbar > span > input[type=file]', $fileLoc)->waitFor('#file_upload > table > tbody > tr > td:nth-child(3) > a');
        });

        // ファイル削除
        Browser::macro('deleteFile', function () {
            return $this->clickAtXPath('//*[@id="file_upload"]/table/tbody/tr/td[3]/a');
        });
    }
}
