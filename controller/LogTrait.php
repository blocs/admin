<?php

namespace Blocs\Controllers;

use Illuminate\Support\Facades\Log;

trait LogTrait
{
    protected $logData;

    protected function logStore()
    {
        Log::info('Store '.$this->mainTable.' id='.$this->logData->id);
    }

    protected function logUpdate()
    {
        Log::info('Update '.$this->mainTable.' id='.$this->logData->id);
    }

    protected function logDestroy()
    {
        Log::info('Destroy '.$this->mainTable.' id='.$this->logData->id);
    }

    protected function logSelect()
    {
        Log::info('Destroy '.$this->mainTable.' id='.implode(',', $this->logData->id));
    }
}
