<?php

namespace Blocs\Controllers;

use Illuminate\Support\Facades\DB;

trait ToggleTrait
{
    public function toggle($id)
    {
        $this->loadCurrent($id);

        // トランザクション内でデータの有効/無効を切り替える
        docs(['GET' => 'id'], '# データの更新');
        DB::transaction(function () {
            $this->tableData->disabled_at = empty($this->tableData->disabled_at);
            $this->tableData->save();
        }, 10);

        docs(
            ['データベース' => $this->loopItem],
            "取得したデータの有効・無効を入れ替えます\n今が有効なら無効にし、無効なら有効にします",
            ['データベース' => $this->loopItem]
        );

        docs('# 画面遷移');

        return $this->outputToggle();
    }

    protected function outputToggle()
    {
        // 有効になったか無効になったかに応じたメッセージと共に一覧画面へ戻る
        $message = empty($this->tableData->disabled_at) ? 'data_valid' : 'data_invalid';

        return $this->backIndex('success', $message, $this->tableData->{$this->noticeItem});
    }
}
