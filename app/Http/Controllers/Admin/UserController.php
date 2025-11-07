<?php

namespace App\Http\Controllers\Admin;

class UserController extends \Blocs\Controllers\Base
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
        // nameが未入力ならばemailを代入して補完
        $this->val['name'] = strlen($this->request->name) ? $this->request->name : $this->request->email;
        $this->val['role'] = empty($this->request->role) ? '' : implode("\t", $this->request->role);
        docs('<name>がなければ、<email>を指定する');

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
            '<search>があれば、<'.$this->loopItem.'>のnameを<search>で部分一致検索',
            '<search>があれば、<'.$this->loopItem.'>のemailを<search>で部分一致検索',
            '<search>があれば、<'.$this->loopItem.'>のroleを<search>で部分一致検索',
        ]);
    }

    private function prepareSortState(): void
    {
        if (empty($this->val['sort']) || ! is_array($this->val['sort'])) {
            $this->val['sort'] = [];
        } else {
            $this->val['sort'] = array_filter($this->val['sort'], 'strlen');
        }

        if (empty($this->val['sort'])) {
            $this->val['sort'] = ['email' => 'asc'];
        }
    }

    private function applySortOrders(&$mainTable): void
    {
        $allowedSortItems = ['email', 'role', 'created_at'];

        foreach ($allowedSortItems as $sortItem) {
            if (! empty($this->val['sort'][$sortItem])) {
                $mainTable->orderBy($sortItem, $this->val['sort'][$sortItem]);
            }
        }

        docs('指定された条件でソート');
    }
}
