<?php

namespace Blocs\Controllers;

trait LogTrait
{
    // ログ記録用のデータを保持
    protected $logData;

    // 新規登録時のログを記録（必要に応じて子クラスでオーバーライド）
    protected function logStore() {}

    // 更新時のログを記録（必要に応じて子クラスでオーバーライド）
    protected function logUpdate() {}

    // 削除時のログを記録（必要に応じて子クラスでオーバーライド）
    protected function logDestroy() {}

    // 一括選択時のログを記録（必要に応じて子クラスでオーバーライド）
    protected function logSelect() {}
}
