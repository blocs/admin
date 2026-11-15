<?php

namespace App\Http\Controllers\Admin;

use Blocs\Controllers\Base;

class UserController extends Base
{
    use UserUpdateTrait;

    public function __construct()
    {
        $this->viewPrefix = 'admin.user';
        $this->mainTable = 'App\Models\Admin\User';
        $this->loopItem = 'users';
        $this->paginateNum = 20;
        $this->noticeItem = 'email';

        // 設定ファイルから取得した役割一覧をメニュー選択肢として登録する
        $roleList = config('role');
        empty($roleList) || addOption('role', array_keys($roleList));
    }

    protected function prepareIndexSearch(&$mainTable)
    {
        // 検索条件をもとに部分一致フィルタを設定
        $this->applySearchFilters($mainTable);

        // ソート条件として使用する入力値を保持する
        $this->keepItem('sort');

        // ソート条件を正規化して初期値を整備
        $this->prepareSortState();

        // 受け付けたソート条件を実行
        $this->applySortOrders($mainTable);
    }

    protected function prepareStore()
    {
        $this->val['name'] = $this->resolveUserName();
        $this->val['role'] = $this->aggregateRoles();

        return [
            'email' => $this->request->email,
            'name' => $this->val['name'],
            'role' => $this->val['role'],
            'password' => bcrypt($this->request->password),
        ];
    }

    private function applySearchFilters(&$mainTable): void
    {
        foreach ($this->searchItems as $searchItem) {
            $mainTable->where(function ($query) use ($searchItem) {
                $query
                    ->where('name', 'LIKE', '%'.$searchItem.'%')
                    ->orWhere('email', 'LIKE', '%'.$searchItem.'%')
                    ->orWhere('role', 'LIKE', '%'.$searchItem.'%');
            });
        }

        docs([
            '<search>を受け取ったら、<'.$this->loopItem.'>のnameに同じ文字が入るか探す',
            '<search>を受け取ったら、<'.$this->loopItem.'>のemailに同じ文字が入るか探す',
            '<search>を受け取ったら、<'.$this->loopItem.'>のroleに同じ文字が入るか探す',
        ]);
        docs('<search>が空なら、登録済みの<'.$this->loopItem.'>をそのまま表示する');
    }

    private function prepareSortState(): void
    {
        if (empty($this->val['sort']) || ! is_array($this->val['sort'])) {
            $this->val['sort'] = [];
        } else {
            // 並び順は asc / desc だけを受け付ける（不正な値で一覧が開けなくなるのを防ぐ）
            $this->val['sort'] = array_filter(
                $this->val['sort'],
                fn ($direction) => is_string($direction) && in_array(strtolower($direction), ['asc', 'desc'], true)
            );
        }

        if (empty($this->val['sort'])) {
            $this->val['sort'] = ['email' => 'asc'];
        }
        docs('<sort>が届かなければ、emailを昇順で並べる');
    }

    private function applySortOrders(&$mainTable): void
    {
        $allowedSortItems = ['email', 'role', 'created_at'];

        foreach ($allowedSortItems as $sortItem) {
            if (! empty($this->val['sort'][$sortItem])) {
                $mainTable->orderBy($sortItem, $this->val['sort'][$sortItem]);
            }
        }

        docs('並び替えの希望があれば、その順で<'.$this->loopItem.'>を並べる');
    }
}
