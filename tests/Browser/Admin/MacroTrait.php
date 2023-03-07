<?php

namespace Tests\Browser\Admin;

use Laravel\Dusk\Browser;

trait MacroTrait
{
    protected function macro(): void
    {
        // テーブルのセルをクリック
        Browser::macro('clickTableCell', function ($rows, $cols, $tag = null) {
            $selector = '#inmaincontents > form > div > div.box-body.no-padding > table > tbody > tr:nth-child('.$rows.') > td:nth-child('.$cols.')';
            isset($tag) && $selector .= ' > '.$tag;

            return $this->click($selector);
        });

        // ファイルアップロード
        Browser::macro('uploadFile', function ($fileLoc) {
            return $this->attach('#file_upload > div.upload-buttonbar > span > input[type=file]', $fileLoc);
        });

        // ファイル削除
        Browser::macro('deleteFile', function () {
            $deleteLink = '#file_upload > table > tbody > tr > td:nth-child(3) > a';

            return $this->waitFor($deleteLink)
            ->click($deleteLink);
        });
    }
}
