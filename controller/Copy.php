<?php

namespace Blocs\Controllers;

trait Copy
{
    private $tableData;

    public function copy($id)
    {
        $this->getCurrent($id);
        $this->val['id'] = $id;

        $this->executeCopy($this->prepareCopy());

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

        call_user_func($this->mainTable.'::create', $requestData);
    }

    protected function outputCopy()
    {
        return $this->backIndex('success', 'data_registered', $this->tableData[$this->noticeItem]);
    }
}
