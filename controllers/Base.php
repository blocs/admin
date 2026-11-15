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
        docs('# 検索条件とソート条件の設定');
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

        docs(['POST' => '入力値'], '# 検索条件とソート条件の設定');
        $this->prepareIndex();

        docs('# 画面表示');

        return $this->outputIndex();
    }

    protected function prepareIndex()
    {
        $this->keepItem('search');

        // 検索条件の準備
        $this->buildIndexSearchItems();

        // 検索条件の適用（継承先でオーバーライド可能）
        $mainTable = $this->mainTable::query();
        $this->prepareIndexSearch($mainTable);

        docs('# 表示に使うデータを取得');
        if (empty($this->paginateNum)) {
            // ページネーションなしで全件取得
            $this->val[$this->loopItem] = $mainTable->get();
            docs(['データベース' => $this->loopItem], '表示に使うデータをすべて取り出す');
        } else {
            $this->prepareIndexPaginate($mainTable);
        }

        // データの有無をフラグで保持
        $this->val['isLoop'] = ! $this->val[$this->loopItem]->isEmpty();
        docs('データなし表示のため、データがあるか判断する');
    }

    protected function prepareIndexSearch(&$mainTable) {}

    protected function prepareIndexPaginate(&$mainTable, $pagePath = null)
    {
        // ページネーション名のデフォルト設定
        $this->paginateName ??= 'page';

        // 検索条件が指定されていない場合のみページ番号を保持（検索条件を変えたら1ページ目に戻す）
        if (! isset($this->request) || ! $this->request->has('search')) {
            $this->keepItem($this->paginateName);
        }

        // ページパスが指定されていない場合はデフォルトのインデックスルートを使用
        $pagePath = $pagePath ?: route(prefix().'.index');

        // ページ番号が指定されていなければ1ページ目
        $page = null;
        if (isset($this->val[$this->paginateName])) {
            $page = $this->val[$this->paginateName] = intval($this->val[$this->paginateName]);
        }

        $paginate = $this->paginateIndex($mainTable, $page);
        docs(['データベース' => $this->loopItem], '<'.$this->loopItem.'>から、表示リスト1ページ分の'.$this->paginateNum."件を読み込む\n検索条件を変えたらページ番号を1に戻す");

        // 不正なページ番号の処理（最終ページを超えている場合は最終ページを表示）
        if ($paginate->lastPage() < $paginate->currentPage()) {
            $paginate = $this->paginateIndex($mainTable, $paginate->lastPage());
        }

        // ページネーションのURLパスと、表示するページリンク数（設定されている場合のみ）を設定
        $paginate = $paginate->withPath($pagePath);
        if (isset($this->paginateEachSide)) {
            $paginate = $paginate->onEachSide($this->paginateEachSide);
        }

        // ページネーションとループ用のデータを設定
        $this->val['paginate'] = $this->val[$this->loopItem] = $paginate;
    }

    protected function outputIndex()
    {
        return $this->outputView('index', '画面のテンプレートを読み込み、HTMLを生成する');
    }

    private function paginateIndex(&$mainTable, $page)
    {
        return $mainTable->paginate($this->paginateNum, ['*'], $this->paginateName, $page)
            ->setPageName($this->paginateName);
    }

    /**
     * 入力値を保存用の配列にする。配列形式の選択項目はタブ区切りの文字列に変換する。
     */
    private function prepareRequest()
    {
        $requestData = $this->request->all();

        foreach ($requestData as $key => $value) {
            // 連番の配列だけを対象にし、表形式（配列の配列）はそのまま残す
            if (! is_array($value) || ! array_is_list($value) || is_array($value[0] ?? null)) {
                continue;
            }

            $requestData[$key] = implode("\t", $value);
        }

        return $requestData;
    }

    /**
     * 検索文字列をスペースまたはカンマで分割し、SQL特殊文字をエスケープする。
     */
    private function buildIndexSearchItems()
    {
        $this->searchItems = [];

        if (empty($this->val['search']) || ! is_string($this->val['search'])) {
            return;
        }

        mb_regex_encoding('utf-8');
        foreach (mb_split("[\s,　]+", $this->val['search']) as $searchItem) {
            // 前後の区切り文字でできる空の語は、全件一致になってしまうため除く
            if ($searchItem !== '') {
                $this->searchItems[] = addcslashes($searchItem, '%_\\');
            }
        }
    }
}
