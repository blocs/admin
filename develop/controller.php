<?php

namespace App\Http\Controllers;

class CONTROLLER_NAME extends \Blocs\Controllers\Base
{
    public function __construct()
    {
        $this->viewPrefix = 'VIEW_PREFIX';
        $this->mainTable = 'App\Models\MODEL_NAME';
        $this->loopItem = 'LOOP_ITEM';
        $this->paginateNum = PAGINATE_NUM;
        $this->noticeItem = 'NOTICE_ITEM';
    }
}
