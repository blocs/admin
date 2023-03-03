<?php

namespace Tests\Browser\Admin;

use Laravel\Dusk\Browser;

trait MacroTrait
{
    protected function macro(): void
    {
        // テーブルのセルをクリック
        Browser::macro('clickTableCell', function ($rows, $cols, $additional = null) {
            $selector = '#inmaincontents > form > div > div.box-body.no-padding > table > tbody > tr:nth-child('.$rows.') > td:nth-child('.$cols.')';

            isset($additional) && $selector .= ' > '.$additional;

            return $this->click($selector);
        });

        // ファイルアップロード
        Browser::macro('uploadFile', function ($fileLoc) {
            return $this->attach('#file_upload > div.upload-buttonbar > span > input[type=file]', $fileLoc)->waitFor('#file_upload > table > tbody > tr > td:nth-child(3) > a');
        });

        // ファイル削除
        Browser::macro('deleteFile', function () {
            return $this->click('#file_upload > table > tbody > tr > td:nth-child(3) > a');
        });
    }
}
