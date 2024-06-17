<?php

namespace Blocs\Controllers;

trait CopyTrait
{
    protected $copyId;

    public function copy($id)
    {
        $this->getCurrent($id);
        $this->copyId = $id;

        docs('# データのコピー');
        $this->executeCopy($this->prepareCopy());

        docs('# 画面遷移');

        return $this->outputCopy();
    }

    protected function prepareCopy()
    {
        $requestData = $this->tableData->toArray();
        foreach (['id', 'created_at', 'updated_at', 'deleted_at', 'disabled_at'] as $unsetItem) {
            unset($requestData[$unsetItem]);
        }

        return $requestData;
    }

    protected function executeCopy($requestData = [])
    {
        if (empty($requestData)) {
            return;
        }

        \DB::beginTransaction();
        try {
            $lastInsert = $this->mainTable::create($requestData);
            \DB::commit();
        } catch (\Throwable $e) {
            \DB::rollBack();
            throw $e;
        }
        $this->val['id'] = $lastInsert->id;
        docs(null, 'データを追加', ['データベース' => $this->loopItem]);

        $this->logData = (object) $requestData;
        $this->logData->id = $lastInsert->id;
    }

    protected function outputCopy()
    {
        return $this->backIndex('success', 'data_registered', $this->tableData[$this->noticeItem]);
    }
}
