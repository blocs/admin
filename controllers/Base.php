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

    protected $paginateEachSide;

    protected $noticeItem;

    protected $searchItems;

    public function index()
    {
        $this->prepareIndex();

        if (session()->has($this->viewPrefix.'.confirm')) {
            // 確認画面からの戻りで選択済みデータを復元
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

        // 検索条件の準備
        $this->buildIndexSearchItems();

        // クエリビルダの初期化
        $mainTable = $this->mainTable::query();

        // 検索条件の適用（継承先でオーバーライド可能）
        $this->prepareIndexSearch($mainTable);

        docs('# データの取得');
        // データの取得（ページネーションの有無で分岐）
        $this->fetchIndexTableData($mainTable);

        // データの有無をフラグで保持
        $this->val['isLoop'] = ! $this->val[$this->loopItem]->isEmpty();
    }

    protected function prepareIndexSearch(&$mainTable) {}

    protected function prepareIndexPaginate(&$mainTable, $pagePath = null)
    {
        // ページネーション名のデフォルト設定
        $this->ensureIndexPaginateNameInitialized();

        // 検索条件が指定されていない場合のみページ番号を保持
        $this->preserveIndexPageNumber();

        // ページパスの設定
        $pagePath = $this->buildIndexPagePath($pagePath);

        // ページネーションの実行
        $this->executeIndexPagination($mainTable);

        docs(['データベース' => $this->loopItem], '<'.$this->loopItem.'>から、指定された<'.$this->paginateName.'>の'.$this->paginateNum."件を取得\n<search>を変更すると、<page>は先頭に戻す");

        // 不正なページ番号の処理（最終ページを超えている場合）
        $this->correctIndexInvalidPageNumber($mainTable);

        // ページネーションの追加設定
        $this->applyIndexPaginationSettings($pagePath);

        // ループ用のデータを設定
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
            if ($this->isIndexSequentialArray($value)) {
                if (count($value) && is_array($value[0])) {
                    continue;
                }

                // 配列形式の選択項目をタブ区切りに変換
                $requestData[$key] = implode("\t", $value);
            }
        }

        return $requestData;
    }

    private function buildIndexSearchItems()
    {
        $this->searchItems = [];

        if (empty($this->val['search'])) {
            return;
        }

        // 検索文字列をスペースまたはカンマで分割
        mb_regex_encoding('utf-8');
        $this->searchItems = mb_split("[\s,　]+", $this->val['search']);

        // SQL特殊文字のエスケープ
        foreach ($this->searchItems as $searchNum => $searchItem) {
            $this->searchItems[$searchNum] = addcslashes($searchItem, '%_\\');
        }
    }

    private function fetchIndexTableData(&$mainTable)
    {
        if (empty($this->paginateNum)) {
            // ページネーションなしで全件取得
            $this->val[$this->loopItem] = $mainTable->get();
            docs(['データベース' => $this->loopItem], '<'.$this->loopItem.'>から全件取得');
        } else {
            // ページネーションありで取得
            $this->prepareIndexPaginate($mainTable);
        }
    }

    private function ensureIndexPaginateNameInitialized()
    {
        // ページネーション名が未設定の場合はデフォルト値を設定
        isset($this->paginateName) || $this->paginateName = 'page';
    }

    private function preserveIndexPageNumber()
    {
        if (! isset($this->request) || ! $this->request->has('search')) {
            // 検索条件なしの場合は現在のページ番号を保持
            $this->keepItem($this->paginateName);
        }
    }

    private function buildIndexPagePath($pagePath)
    {
        // ページパスが指定されていない場合はデフォルトのインデックスルートを使用
        if (empty($pagePath)) {
            return route(prefix().'.index');
        }

        return $pagePath;
    }

    private function executeIndexPagination(&$mainTable)
    {
        if (isset($this->val[$this->paginateName])) {
            // ページ番号が指定されている場合
            $this->val[$this->paginateName] = intval($this->val[$this->paginateName]);
            $this->val['paginate'] = $mainTable->paginate(
                $this->paginateNum,
                ['*'],
                $this->paginateName,
                $this->val[$this->paginateName]
            )->setPageName($this->paginateName);
        } else {
            // ページ番号が指定されていない場合（1ページ目）
            $this->val['paginate'] = $mainTable->paginate(
                $this->paginateNum,
                ['*'],
                $this->paginateName
            )->setPageName($this->paginateName);
        }
    }

    private function correctIndexInvalidPageNumber(&$mainTable)
    {
        $lastPage = $this->val['paginate']->lastPage();
        $currentPage = $this->val['paginate']->currentPage();

        if ($lastPage < $currentPage) {
            // 最終ページを超えている場合は最終ページを表示
            $this->val['paginate'] = $mainTable->paginate(
                $this->paginateNum,
                ['*'],
                $this->paginateName,
                $lastPage
            )->setPageName($this->paginateName);
        }
    }

    private function applyIndexPaginationSettings($pagePath)
    {
        // ページネーションのURLパスを設定
        $this->val['paginate'] = $this->val['paginate']->withPath($pagePath);

        // 表示するページリンク数の設定（設定されている場合のみ）
        if (isset($this->paginateEachSide)) {
            $this->val['paginate'] = $this->val['paginate']->onEachSide($this->paginateEachSide);
        }
    }

    private function isIndexSequentialArray($value)
    {
        // 連番の配列かどうかをチェック
        return is_array($value) && array_values($value) === $value;
    }
}
