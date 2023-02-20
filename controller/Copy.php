<?php

namespace Blocs\Controllers;

trait Copy
{
    private $tableData;

    public function copy($id)
    {
        $this->tableData = call_user_func($this->mainTable.'::findOrFail', $id);
        $this->val['id'] = $id;

        $tableData = $this->tableData->toArray();

        foreach (['id', 'created_at', 'updated_at', 'deleted_at', 'disabled_at'] as $unsetItem) {
            unset($tableData[$unsetItem]);
        }

        call_user_func($this->mainTable.'::create', $tableData);

        return $this->outputCopy();
    }

    protected function outputCopy()
    {
        return $this->backIndex('success', 'data_registered', $this->tableData[$this->noticeItem]);
    }
}
