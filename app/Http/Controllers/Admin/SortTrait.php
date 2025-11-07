<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

trait SortTrait
{
    public function sort(Request $request)
    {
        $orderEntries = $this->collectSortPayload($request);

        if (empty($orderEntries)) {
            return $this->backIndex('success', 'data_ordered');
        }

        [$orderIds, $sortedOrderValues] = $this->separateSortPayload($orderEntries);

        // 順序の入れ替え: ソート済みのorder_byを順番に更新する
        $this->updateOrderSequence($orderIds, $sortedOrderValues);

        return $this->backIndex('success', 'data_ordered');
    }

    private function collectSortPayload(Request $request): array
    {
        return array_values(array_filter(
            (array) ($request->{$this->loopItem} ?? []),
            static fn ($entry) => is_array($entry) && isset($entry['order_id'], $entry['order_by'])
        ));
    }

    private function separateSortPayload(array $orderEntries): array
    {
        $orderIds = array_column($orderEntries, 'order_id');
        $orderValues = array_column($orderEntries, 'order_by');

        sort($orderValues);

        return [$orderIds, $orderValues];
    }

    private function updateOrderSequence(array $orderIds, array $sortedOrderValues): void
    {
        if (empty($orderIds) || empty($sortedOrderValues)) {
            return;
        }

        $models = $this->mainTable::findMany($orderIds)->keyBy('id');

        foreach ($orderIds as $orderId) {
            if (! isset($models[$orderId])) {
                continue;
            }

            $nextOrderValue = array_shift($sortedOrderValues);

            if ($nextOrderValue === null) {
                break;
            }

            $models[$orderId]->update([
                'order_by' => $nextOrderValue,
            ]);
        }
    }
}
