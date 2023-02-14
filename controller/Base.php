<?php

namespace Blocs\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class Base extends Controller
{
    protected $val = [];
    protected $request;

    protected $viewPrefix;
    protected $mainTable;
    protected $loopItem;
    protected $paginateNum;
    protected $noticeItem;

    private $selectedIdList = [];
    private $deletedNum = 0;
    private $tableData;

    use Common;

    public function __construct()
    {
        define('ROUTE_PREFIX', self::getRoutePrefix());
    }

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

        $this->index();
    }

    protected function prepareIndex()
    {
        $this->keepItem('search');

        $this->searchItems = [];
        if (!empty($this->request->search)) {
            mb_regex_encoding('utf-8');
            $this->searchItems = mb_split("[\s,　]+", $this->request->search);

            foreach ($this->searchItems as $searchNum => $searchItem) {
                $this->searchItems[$searchNum] = addcslashes($searchItem, '%_\\');
            }

            $this->val['search'] = $this->request->search;
        }

        $mainTable = call_user_func($this->mainTable.'::query');

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
        $this->val['loopIs'] = !$this->val[$this->loopItem]->isEmpty();
    }

    protected function prepareIndexSearch(&$mainTable)
    {
    }

    protected function prepareIndexPaginate(&$mainTable)
    {
        $this->val['paginate'] = $mainTable->paginate($this->paginateNum);
        $this->val[$this->loopItem] = $this->val['paginate'];
    }

    protected function outputIndex()
    {
        $this->setupMenu();

        return view($this->viewPrefix.'.index', $this->val);
    }

    /* create */

    public function create()
    {
        $this->prepareCreate();

        if (session()->has($this->viewPrefix.'.confirm')) {
            // 確認画面からの遷移
            $this->val = array_merge($this->val, session($this->viewPrefix.'.confirm'));
        }

        return $this->outputCreate();
    }

    protected function prepareCreate()
    {
    }

    protected function outputCreate()
    {
        $this->setupMenu();

        return view($this->viewPrefix.'.create', $this->val);
    }

    /* store */

    public function confirmStore(Request $request)
    {
        $this->request = $request;

        if ($redirect = $this->validateStore()) {
            return $redirect;
        }

        session()->flash($this->viewPrefix.'.confirm', $this->request->all());

        $this->prepareConfirmStore();

        return $this->outputConfirmStore();
    }

    protected function validateStore()
    {
        list($rules, $messages) = \Blocs\Validate::get($this->viewPrefix.'.create', $this->request);
        empty($rules) || $this->request->validate($rules, $messages);
    }

    protected function prepareConfirmStore()
    {
        $this->val = array_merge($this->request->all(), $this->val);
    }

    protected function outputConfirmStore()
    {
        $this->setupMenu();

        return view($this->viewPrefix.'.confirmStore', $this->val);
    }

    public function store(Request $request)
    {
        $this->request = $request;

        if (session()->has($this->viewPrefix.'.confirm')) {
            // 確認画面からの遷移
            $this->request->merge(session($this->viewPrefix.'.confirm'));
        } else {
            if ($redirect = $this->validateStore()) {
                return $redirect;
            }
        }

        $this->executeStore($this->prepareStore());

        return $this->outputStore();
    }

    protected function prepareStore()
    {
    }

    protected function executeStore($requestData = [])
    {
        if (empty($requestData)) {
            return;
        }

        call_user_func($this->mainTable.'::create', $requestData);
    }

    protected function outputStore()
    {
        return $this->backIndex('success', 'data_registered', $this->request->{$this->noticeItem});
    }

    /* edit */

    public function edit($id)
    {
        $this->tableData = call_user_func($this->mainTable.'::findOrFail', $id);
        $this->val['id'] = $id;

        empty(old()) && !empty($this->val['id']) && $this->getCurrent();

        $this->prepareEdit();

        if (session()->has($this->viewPrefix.'.confirm')) {
            // 確認画面からの遷移
            $this->val = array_merge($this->val, session($this->viewPrefix.'.confirm'));
        }

        return $this->outputEdit();
    }

    protected function getCurrent()
    {
        $tableData = $this->tableData->toArray();

        $this->val = array_merge($tableData, $this->val);
    }

    protected function prepareEdit()
    {
    }

    protected function outputEdit()
    {
        $this->setupMenu();

        return view($this->viewPrefix.'.edit', $this->val);
    }

    /* update */

    public function confirmUpdate($id, Request $request)
    {
        $this->tableData = call_user_func($this->mainTable.'::findOrFail', $id);
        $this->val['id'] = $id;
        $this->request = $request;

        if ($redirect = $this->validateUpdate()) {
            return $redirect;
        }

        session()->flash($this->viewPrefix.'.confirm', $this->request->all());

        $this->prepareConfirmUpdate();

        return $this->outputConfirmUpdate();
    }

    protected function validateUpdate()
    {
        list($rules, $messages) = \Blocs\Validate::get($this->viewPrefix.'.edit', $this->request);
        empty($rules) || $this->request->validate($rules, $messages);
    }

    protected function prepareConfirmUpdate()
    {
        $this->val = array_merge($this->request->all(), $this->val);
    }

    protected function outputConfirmUpdate()
    {
        $this->setupMenu();

        return view($this->viewPrefix.'.confirmUpdate', $this->val);
    }

    public function update($id, Request $request)
    {
        $this->tableData = call_user_func($this->mainTable.'::findOrFail', $id);
        $this->val['id'] = $id;
        $this->request = $request;

        if (session()->has($this->viewPrefix.'.confirm')) {
            // 確認画面からの遷移
            $this->request->merge(session($this->viewPrefix.'.confirm'));
        } else {
            if ($redirect = $this->validateUpdate()) {
                return $redirect;
            }
        }

        if ($redirect = $this->checkConflict()) {
            return $redirect;
        }

        $this->executeUpdate($this->prepareUpdate());

        return $this->outputUpdate();
    }

    protected function checkConflict()
    {
        if (empty($this->request->updated_at)) {
            return;
        }

        $tableData = $this->tableData->toArray();

        if ($this->request->updated_at !== $tableData['updated_at']) {
            return $this->backEdit('error', 'collision_happened');
        }
    }

    protected function prepareUpdate()
    {
    }

    protected function executeUpdate($requestData = [])
    {
        if (empty($requestData)) {
            return;
        }

        $this->tableData->fill($requestData)->save();
    }

    protected function outputUpdate()
    {
        return $this->backIndex('success', 'data_updated', $this->request->{$this->noticeItem});
    }

    /* destroy */

    public function confirmDestroy($id, Request $request)
    {
        $this->tableData = call_user_func($this->mainTable.'::findOrFail', $id);
        $this->val['id'] = $id;
        $this->request = $request;

        if ($redirect = $this->validateDestroy()) {
            return $redirect;
        }

        session()->flash($this->viewPrefix.'.confirm', $this->request->all());

        $this->prepareConfirmDestroy();

        return $this->outputConfirmDestroy();
    }

    protected function validateDestroy()
    {
    }

    protected function prepareConfirmDestroy()
    {
        $this->val = array_merge($this->request->all(), $this->val);
    }

    protected function outputConfirmDestroy()
    {
        $this->setupMenu();

        return view($this->viewPrefix.'.confirmDestroy', $this->val);
    }

    public function destroy($id, Request $request)
    {
        $this->tableData = call_user_func($this->mainTable.'::findOrFail', $id);
        $this->val['id'] = $id;
        $this->request = $request;

        if (session()->has($this->viewPrefix.'.confirm')) {
            // 確認画面からの遷移
            $this->request->merge(session($this->viewPrefix.'.confirm'));
        } else {
            if ($redirect = $this->validateDestroy()) {
                return $redirect;
            }
        }

        $this->prepareDestroy();
        $this->executeDestroy();

        return $this->outputDestroy();
    }

    protected function prepareDestroy()
    {
    }

    protected function executeDestroy()
    {
        $this->deletedNum = call_user_func($this->mainTable.'::destroy', $this->val['id']);
    }

    protected function outputDestroy()
    {
        return $this->backIndex('success', 'data_deleted', $this->deletedNum);
    }

    /* select */

    public function confirmSelect(Request $request)
    {
        $this->request = $request;

        if ($redirect = $this->validateSelect()) {
            return $redirect;
        }

        session()->flash($this->viewPrefix.'.confirm', $this->request->all());

        $this->prepareConfirmSelect();

        return $this->outputConfirmSelect();
    }

    protected function validateSelect()
    {
        if (empty($this->request->{$this->loopItem})) {
            return $this->backIndex('error', 'data_not_selected');
        }

        foreach ($this->request->{$this->loopItem} as $table) {
            empty($table['selectedRows']) || $this->selectedIdList[] = $table['selectedRows'][0];
        }

        if (empty($this->selectedIdList)) {
            return $this->backIndex('error', 'data_not_selected');
        }
    }

    protected function prepareConfirmSelect()
    {
    }

    protected function outputConfirmSelect()
    {
        $this->setupMenu();

        return view($this->viewPrefix.'.confirmSelect', $this->val);
    }

    public function select(Request $request)
    {
        $this->request = $request;

        if (session()->has($this->viewPrefix.'.confirm')) {
            // 確認画面からの遷移
            $this->request->merge(session($this->viewPrefix.'.confirm'));

            foreach ($this->request->{$this->loopItem} as $table) {
                empty($table['selectedRows']) || $this->selectedIdList[] = $table['selectedRows'][0];
            }
        } else {
            if ($redirect = $this->validateSelect()) {
                return $redirect;
            }
        }

        $this->prepareSelect();
        $this->executeSelect();

        return $this->outputSelect();
    }

    protected function prepareSelect()
    {
    }

    protected function executeSelect()
    {
        if (empty($this->selectedIdList)) {
            return;
        }

        $this->deletedNum = call_user_func($this->mainTable.'::destroy', $this->selectedIdList);
    }

    protected function outputSelect()
    {
        return $this->backIndex('success', 'data_deleted', $this->deletedNum);
    }

    /* toggle */

    public function toggle($id)
    {
        $this->tableData = call_user_func($this->mainTable.'::findOrFail', $id);
        $this->val['id'] = $id;

        if (empty($this->tableData->disabled_at)) {
            $this->tableData->disabled_at = Carbon::now();
        } else {
            $this->tableData->disabled_at = null;
        }

        $this->tableData->save();

        return $this->outputToggle();
    }

    protected function outputToggle()
    {
        if (empty($this->tableData->disabled_at)) {
            return $this->backIndex('success', 'data_valid', $this->tableData->{$this->noticeItem});
        }

        return $this->backIndex('success', 'data_invalid', $this->tableData->{$this->noticeItem});
    }

    /* copy */

    public function copy($id)
    {
        $this->tableData = call_user_func($this->mainTable.'::findOrFail', $id);
        $this->val['id'] = $id;

        $tableData = $this->tableData->toArray();

        foreach (['id', 'created_at', 'updated_at', 'deleted_at', 'disabled_at'] as $unsetItem) {
            unset($tableData[$unsetItem]);
        }

        call_user_func($this->mainTable.'::create', $tableData);

        return $this->outputCopy();
    }

    protected function outputCopy()
    {
        return $this->backIndex('success', 'data_registered', $this->tableData[$this->noticeItem]);
    }

    /* upload */

    public function upload(Request $request)
    {
        $this->request = $request;
        $paramname = $this->request->name;

        if (isset($this->request->uploadedFile)) {
            $uploadedFile = $this->request->uploadedFile;
            is_array($uploadedFile) || $uploadedFile = json_decode($uploadedFile, true);

            $html = view(ADMIN_VIEW_PREFIX.'.autoinclude.upload_list', ['fileList' => $uploadedFile])->render();

            return json_encode([
                'paramname' => $paramname,
                'html' => $html,
            ]);
        }

        $fileupload = $this->request->file('upload');
        $mimeType = $fileupload->getMimeType();

        $extension = $fileupload->extension();
        if (!$extension) {
            return json_encode([
                'paramname' => $paramname,
                'error' => \Blocs\Lang::get('error:fileupload_error_php'),
            ]);
        }

        $this->validateUpload($paramname);

        $filename = md5($fileupload->get()).'.'.$extension;
        $fileupload->storeAs('upload', $filename);

        $existThumbnail = $this->createThumbnail('upload/'.$filename, 'thumbnail') ? 1 : 0;
        $file = [
            'paramname' => $paramname,
            'filename' => $filename,
            'name' => $fileupload->getClientOriginalName(),
            'size' => $fileupload->getSize(),
            'thumbnail' => $existThumbnail,
        ];
        $file['html'] = view(ADMIN_VIEW_PREFIX.'.autoinclude.upload_list', ['fileList' => [$file]])->render();

        return json_encode($file);
    }

    protected function validateUpload($paramname)
    {
        list($rules, $messages) = \Blocs\Validate::upload($this->viewPrefix, $paramname);
        empty($rules) || $this->request->validate($rules, $messages);
    }

    /* download */

    public function download($filename, $size = null)
    {
        if ($redirect = $this->checkDownload($filename)) {
            return $redirect;
        }

        $storage = \Storage::disk();
        $filename = 'upload/'.$filename;
        $mimeType = $storage->mimeType($filename);

        if (isset($size)) {
            $thumbnail = $this->createThumbnail($filename, $size);
            if ($thumbnail) {
                return response(\File::get($thumbnail))->header('Content-Type', $mimeType);
            }
        } else {
            $thumbnail = $this->createThumbnail($filename, 'thumbnail');
            if ($thumbnail) {
                return response($storage->get($filename))->header('Content-Type', $mimeType);
            }
        }

        return $storage->download($filename);
    }

    protected function checkDownload($filename)
    {
        // return \App::abort(404);
    }

    protected function getSize($size)
    {
        // 画像のサイズを指定できるように
        $downloadSize = [
            'thumbnail' => [80, 10000, false],
            's' => [380, 10000, false],
            's@2x' => [760, 10000, false],
            'm' => [585, 10000, false],
            'm@2x' => [1170, 10000, false],
            'l' => [1200, 10000, false],
            'l@2x' => [2400, 10000, false],
        ];

        return $downloadSize[$size];
    }

    /* common */

    protected function backIndex($category, $code)
    {
        $resirectIndex = redirect()->route(ROUTE_PREFIX.'.index');

        $category && $resirectIndex = $resirectIndex->with([
            'category' => $category,
            'message' => \Blocs\Lang::get(implode(':', func_get_args())),
        ]);

        return $resirectIndex;
    }

    protected function backEdit($category, $code, $noticeForm = '', ...$msgArgList)
    {
        $resirectEdit = redirect()->route(ROUTE_PREFIX.'.edit', $this->val)->withInput();

        if ($category) {
            $msgArgList = array_merge([$category, $code], $msgArgList);
            $resirectEdit = $resirectEdit->with([
                'category' => $category,
                'message' => \Blocs\Lang::get(implode(':', $msgArgList)),
            ]);
        } else {
            $msgArgList = array_merge([$code], $msgArgList);
        }

        if ($noticeForm) {
            $resirectEdit = $resirectEdit->withErrors([
                $noticeForm => \Blocs\Lang::get(implode(':', $msgArgList)),
            ]);
        }

        return $resirectEdit;
    }

    // テーブルのデータと入力値をマージ
    protected static function mergeTable($table, $request)
    {
        if (!is_array($table) || !is_array($request)) {
            return $table;
        }

        foreach ($request as $sKey => $mValue) {
            if (isset($table[$sKey]) && is_array($mValue) && is_array($table[$sKey])) {
                $table[$sKey] = self::mergeTable($table[$sKey], $mValue);
            } else {
                $table[$sKey] = $mValue;
            }
        }

        return $table;
    }

    protected function setupMenu()
    {
        list($menu, $headline, $breadcrumb) = \Blocs\Menu::get();
        $this->val['menu'] = $menu;
        $this->val['headline'] = $headline;
        $this->val['breadcrumb'] = $breadcrumb;
    }

    private function createThumbnail($filename, $size)
    {
        // ストレージからサムネイルファイル作成
        $path = \Storage::path($filename);
        list($width, $height, $crop) = $this->getSize($size);
        $thumbnail = \Blocs\Thumbnail::create($path, $width, $height, $crop);

        return $thumbnail;
    }
}
