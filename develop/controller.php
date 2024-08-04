<?php

namespace App\Http\ControllersCONTROLLER_DIRNAME;

class CONTROLLER_BASENAME extends \Blocs\Controllers\Base
{
    public function __construct()
    {
        $this->viewPrefix = 'VIEW_PREFIX';
        $this->mainTable = 'App\ModelsMODEL_DIRNAME\MODEL_BASENAME';
        $this->loopItem = 'LOOP_ITEM';
        $this->paginateNum = PAGINATE_NUM;
        $this->noticeItem = 'NOTICE_ITEM';
    }
}
