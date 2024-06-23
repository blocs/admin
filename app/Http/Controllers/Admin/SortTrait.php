<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

trait SortTrait
{
    public function sort(Request $request)
    {
        $orderIds = [];
        $orderBys = [];
        foreach ($request->{$this->loopItem} as $table) {
            $orderIds[] = $table['order_id'];
            $orderBys[] = $table['order_by'];
        }
        sort($orderBys);

        // 順序の入れ替え
        foreach ($orderIds as $orderId) {
            $this->mainTable::find($orderId)->update([
                'order_by' => array_shift($orderBys),
            ]);
        }

        return $this->backIndex('success', 'data_ordered');
    }
}
