<?php

namespace Blocs\Controllers;

use Illuminate\Support\Carbon;

trait Toggle
{
    private $tableData;

    public function toggle($id)
    {
        $this->tableData = call_user_func($this->mainTable.'::findOrFail', $id);
        $this->val['id'] = $id;

        if (empty($this->tableData->disabled_at)) {
            $this->tableData->disabled_at = Carbon::now();
        } else {
            $this->tableData->disabled_at = null;
        }

        $this->tableData->save();

        return $this->outputToggle();
    }

    protected function outputToggle()
    {
        if (empty($this->tableData->disabled_at)) {
            return $this->backIndex('success', 'data_valid', $this->tableData->{$this->noticeItem});
        }

        return $this->backIndex('success', 'data_invalid', $this->tableData->{$this->noticeItem});
    }
}
