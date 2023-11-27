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

        return $this->outputIndex();
    }

    public function search(Request $request)
    {
        $this->request = $request;

        return $this->index();
    }

    protected function prepareIndex()
    {
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

        // 検索条件、ソートなど
        $this->prepareIndexSearch($mainTable);

        if (empty($this->paginateNum)) {
            // ページネーションなし
            $this->val[$this->loopItem] = $mainTable->get();
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

    protected function prepareIndexPaginate(&$mainTable)
    {
        isset($this->paginateName) || $this->paginateName = 'page';

        if (!isset($this->request) || !$this->request->has('search')) {
            // 検索条件の指定がない時はページをキープ
            $this->keepItem($this->paginateName);
        }

        $pagePath = route(prefix().'.index');

        if (isset($this->val[$this->paginateName])) {
            $this->val['paginate'] = $mainTable->paginate($this->paginateNum, ['*'], $this->paginateName, $this->val[$this->paginateName])->setPageName($this->paginateName)->withPath($pagePath);
        } else {
            $this->val['paginate'] = $mainTable->paginate($this->paginateNum, ['*'], $this->paginateName)->setPageName($this->paginateName)->withPath($pagePath);
        }

        // 存在しないページ
        if ($this->val['paginate']->lastPage() < $this->val['paginate']->currentPage()) {
            $this->val['paginate'] = $mainTable->paginate($this->paginateNum, ['*'], $this->paginateName, $this->val['paginate']->lastPage())->setPageName($this->paginateName)->withPath($pagePath);
        }

        $this->val[$this->loopItem] = $this->val['paginate'];
    }

    protected function outputIndex()
    {
        $this->setupMenu();

        return view($this->viewPrefix.'.index', $this->val);
    }
}
