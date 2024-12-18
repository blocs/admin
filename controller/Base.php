<?php

namespace Blocs\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Base extends Controller
{
    use BackTrait;
    use CommonTrait;
    use CopyTrait;
    use DestroyTrait;
    use FileTrait;
    use LogTrait;
    use SelectTrait;
    use StoreTrait;
    use ToggleTrait;
    use UpdateTrait;

    protected $val = [];
    protected $request;
    protected $tableData;

    protected $viewPrefix;
    protected $mainTable;
    protected $loopItem;
    protected $paginateNum;
    protected $paginateName;
    protected $noticeItem;
    protected $searchItems;

    /* index */

    public function index()
    {
        $this->prepareIndex();

        if (session()->has($this->viewPrefix.'.confirm')) {
            // 確認画面からの遷移（selectedRows）
            $this->val = self::mergeTable($this->val, session($this->viewPrefix.'.confirm'));
        }

        docs('# 画面表示');

        return $this->outputIndex();
    }

    public function search(Request $request)
    {
        $this->request = $request;

        return $this->index();
    }

    protected function prepareIndex()
    {
        docs('# 検索条件とソート条件の設定');
        $this->keepItem('search');

        $this->searchItems = [];
        if (!empty($this->val['search'])) {
            mb_regex_encoding('utf-8');
            $this->searchItems = mb_split("[\s,　]+", $this->val['search']);

            foreach ($this->searchItems as $searchNum => $searchItem) {
                $this->searchItems[$searchNum] = addcslashes($searchItem, '%_\\');
            }
        }

        $mainTable = $this->mainTable::query();

        $this->prepareIndexSearch($mainTable);

        docs('# データの取得');
        if (empty($this->paginateNum)) {
            // ページネーションなし
            $this->val[$this->loopItem] = $mainTable->get();
            docs(['データベース' => $this->loopItem], '<'.$this->loopItem.'>から全件取得');
        } else {
            // ページネーションあり
            $this->prepareIndexPaginate($mainTable);
        }

        // データの有無
        $this->val['isLoop'] = !$this->val[$this->loopItem]->isEmpty();
    }

    protected function prepareIndexSearch(&$mainTable)
    {
    }

    protected function prepareIndexPaginate(&$mainTable, $pagePath = null)
    {
        isset($this->paginateName) || $this->paginateName = 'page';

        if (!isset($this->request) || !$this->request->has('search')) {
            // 検索条件の指定がない時はページをキープ
            $this->keepItem($this->paginateName);
        }

        empty($pagePath) && $pagePath = route(prefix().'.index');

        if (isset($this->val[$this->paginateName])) {
            $this->val[$this->paginateName] = intval($this->val[$this->paginateName]);
            $this->val['paginate'] = $mainTable->paginate($this->paginateNum, ['*'], $this->paginateName, $this->val[$this->paginateName])->setPageName($this->paginateName);
        } else {
            $this->val['paginate'] = $mainTable->paginate($this->paginateNum, ['*'], $this->paginateName)->setPageName($this->paginateName);
        }
        docs(['データベース' => $this->loopItem], '<'.$this->loopItem.'>から、指定された<'.$this->paginateName.'>の'.$this->paginateNum."件を取得\n<search>を変更すると、<page>は先頭に戻す");

        // 存在しないページ
        if ($this->val['paginate']->lastPage() < $this->val['paginate']->currentPage()) {
            $this->val['paginate'] = $mainTable->paginate($this->paginateNum, ['*'], $this->paginateName, $this->val['paginate']->lastPage())->setPageName($this->paginateName);
        }

        $this->val['paginate'] = $this->val['paginate']->withPath($pagePath);
        isset($this->paginateEachSide) && $this->val['paginate'] = $this->val['paginate']->onEachSide($this->paginateEachSide);

        $this->val[$this->loopItem] = $this->val['paginate'];
    }

    protected function outputIndex()
    {
        $this->setupMenu();

        $view = view($this->viewPrefix.'.index', $this->val);
        unset($this->val, $this->request, $this->tableData);
        docs('テンプレートを読み込んで、HTMLを生成');

        return $view;
    }

    private function prepareRequest()
    {
        $requestData = $this->request->all();

        foreach ($requestData as $key => $value) {
            if (is_array($value) && array_values($value) === $value) {
                if (count($value) && is_array($value[0])) {
                    continue;
                }

                // option項目
                $requestData[$key] = implode("\t", $value);
            }
        }

        return $requestData;
    }
}
