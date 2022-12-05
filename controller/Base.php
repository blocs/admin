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
    protected $paginateNum;
    protected $noticeItem;

    private $selected_ids = [];
    private $deleted_num = 0;

    /* index */

    public function index(Request $request)
    {
        $this->request = $request;
        $this->val = array_merge($this->val, \Blocs\Notice::get());

        $this->prepareIndex();

        if (session()->has($this->viewPrefix.'confirm')) {
            // 確認画面からの遷移（selected_rows）
            $this->val = self::mergeTable($this->val, session($this->viewPrefix.'confirm'));
        }

        return $this->outputIndex();
    }

    protected function prepareIndex()
    {
        $this->search_items = [];
        if (!empty($this->request->search)) {
            mb_regex_encoding('utf-8');
            $this->search_items = mb_split("[\s,　]+", $this->request->search);

            foreach ($this->search_items as $search_num => $search_item) {
                $this->search_items[$search_num] = addcslashes($search_item, '%_\\');
            }

            $this->val['search'] = $this->request->search;
        }

        $mainTable = call_user_func($this->mainTable.'::query');

        // 検索条件、ソートなど
        $this->prepareIndexSearch($mainTable);

        if (empty($this->paginateNum)) {
            // ページネーションなし
            $this->val['table'] = $mainTable->get()->toArray();
        } else {
            // ページネーションあり
            $this->prepareIndexPaginate($mainTable);
        }
    }

    protected function prepareIndexSearch(&$mainTable)
    {
    }

    protected function prepareIndexPaginate(&$mainTable)
    {
        $this->val['paginate'] = $mainTable->paginate($this->paginateNum);
        $paginate = $this->val['paginate']->toArray();
        $this->val['table'] = $paginate['data'];
    }

    protected function outputIndex()
    {
        $this->setupNavigation();

        return view($this->viewPrefix.'.index', $this->val);
    }

    /* entry */

    public function entry($id = 0)
    {
        if ($redirect = $this->checkId($id)) {
            return $redirect;
        }
        $this->val['id'] = $id;

        empty(old()) && !empty($this->val['id']) && $this->getCurrent();

        $this->val = array_merge($this->val, \Blocs\Notice::get());

        $this->prepareEntry();

        if (session()->has($this->viewPrefix.'confirm')) {
            // 確認画面からの遷移
            $this->val = array_merge($this->val, session($this->viewPrefix.'confirm'));
        }

        return $this->outputEntry();
    }

    protected function getCurrent()
    {
        $table_data = call_user_func($this->mainTable.'::find', $this->val['id']);
        $table_data = $table_data->toArray();

        $this->val = array_merge($table_data, $this->val);
    }

    protected function prepareEntry()
    {
    }

    protected function outputEntry()
    {
        $this->setupNavigation();

        if ($this->val['id']) {
            // 編集
            return view($this->viewPrefix.'.update', $this->val);
        }

        // 新規登録
        return view($this->viewPrefix.'.insert', $this->val);
    }

    /* insert */

    public function confirmInsert(Request $request)
    {
        $this->request = $request;

        if ($redirect = $this->validateInsert()) {
            return $redirect;
        }

        session()->flash($this->viewPrefix.'confirm', $this->request->all());

        $this->prepareConfirmInsert();

        return $this->outputConfirmInsert();
    }

    protected function validateInsert()
    {
        list($validate, $message) = \Blocs\Validate::get($this->viewPrefix.'.insert', $this->request);
        empty($validate) || $this->request->validate($validate, $message);
    }

    protected function prepareConfirmInsert()
    {
        $this->val = array_merge($this->request->all(), $this->val);
    }

    protected function outputConfirmInsert()
    {
        $this->setupNavigation();

        return view($this->viewPrefix.'.confirmInsert', $this->val);
    }

    public function insert(Request $request)
    {
        $this->request = $request;

        if (session()->has($this->viewPrefix.'confirm')) {
            // 確認画面からの遷移
            $this->request->merge(session($this->viewPrefix.'confirm'));
        } else {
            if ($redirect = $this->validateInsert()) {
                return $redirect;
            }
        }

        $this->prepareInsert();
        $this->executeInsert();

        return $this->outputInsert();
    }

    protected function prepareInsert()
    {
    }

    protected function executeInsert($request_data = [])
    {
        if (empty($request_data)) {
            return;
        }

        call_user_func($this->mainTable.'::create', $request_data);
    }

    protected function outputInsert()
    {
        return $this->backIndex('success', 'data_registered', $this->request->{$this->noticeItem});
    }

    /* update */

    public function confirmUpdate($id, Request $request)
    {
        if ($redirect = $this->checkId($id)) {
            return $redirect;
        }
        $this->val['id'] = $id;
        $this->request = $request;

        if ($redirect = $this->validateUpdate()) {
            return $redirect;
        }

        session()->flash($this->viewPrefix.'confirm', $this->request->all());

        $this->prepareConfirmUpdate();

        return $this->outputConfirmUpdate();
    }

    protected function validateUpdate()
    {
        list($validate, $message) = \Blocs\Validate::get($this->viewPrefix.'.update', $this->request);
        empty($validate) || $this->request->validate($validate, $message);
    }

    protected function prepareConfirmUpdate()
    {
        $this->val = array_merge($this->request->all(), $this->val);
    }

    protected function outputConfirmUpdate()
    {
        $this->setupNavigation();

        return view($this->viewPrefix.'.confirmUpdate', $this->val);
    }

    public function update($id, Request $request)
    {
        if ($redirect = $this->checkId($id)) {
            return $redirect;
        }
        $this->val['id'] = $id;
        $this->request = $request;

        if (session()->has($this->viewPrefix.'confirm')) {
            // 確認画面からの遷移
            $this->request->merge(session($this->viewPrefix.'confirm'));
        } else {
            if ($redirect = $this->validateUpdate()) {
                return $redirect;
            }
        }

        if ($redirect = $this->checkConflict()) {
            return $redirect;
        }

        $this->prepareUpdate();
        $this->executeUpdate();

        return $this->outputUpdate();
    }

    protected function checkConflict()
    {
        if (empty($this->request->updated_at)) {
            return;
        }

        $table_data = call_user_func($this->mainTable.'::find', $this->val['id']);
        $table_data = $table_data->toArray();

        if ($this->request->updated_at !== $table_data['updated_at']) {
            return $this->backEntry('error', 'collision_happened');
        }
    }

    protected function prepareUpdate()
    {
    }

    protected function executeUpdate($request_data = [])
    {
        if (empty($request_data)) {
            return;
        }

        $table_data = call_user_func($this->mainTable.'::find', $this->val['id']);
        $table_data->fill($request_data)->save();
    }

    protected function outputUpdate()
    {
        return $this->backIndex('success', 'data_updated', $this->request->{$this->noticeItem});
    }

    /* delete */

    public function confirmDelete($id, Request $request)
    {
        if ($redirect = $this->checkId($id)) {
            return $redirect;
        }
        $this->val['id'] = $id;
        $this->request = $request;

        if ($redirect = $this->validateDelete()) {
            return $redirect;
        }

        session()->flash($this->viewPrefix.'confirm', $this->request->all());

        $this->prepareConfirmDelete();

        return $this->outputConfirmDelete();
    }

    protected function validateDelete()
    {
    }

    protected function prepareConfirmDelete()
    {
        $this->val = array_merge($this->request->all(), $this->val);
    }

    protected function outputConfirmDelete()
    {
        $this->setupNavigation();

        return view($this->viewPrefix.'.confirmDelete', $this->val);
    }

    public function delete($id, Request $request)
    {
        if ($redirect = $this->checkId($id)) {
            return $redirect;
        }
        $this->val['id'] = $id;
        $this->request = $request;

        if (session()->has($this->viewPrefix.'confirm')) {
            // 確認画面からの遷移
            $this->request->merge(session($this->viewPrefix.'confirm'));
        } else {
            if ($redirect = $this->validateDelete()) {
                return $redirect;
            }
        }

        $this->prepareDelete();
        $this->executeDelete();

        return $this->outputDelete();
    }

    protected function prepareDelete()
    {
    }

    protected function executeDelete()
    {
        $this->deleted_num = call_user_func($this->mainTable.'::destroy', $this->val['id']);
    }

    protected function outputDelete()
    {
        return $this->backIndex('success', 'data_deleted', $this->deleted_num);
    }

    /* select */

    public function confirmSelect(Request $request)
    {
        $this->request = $request;

        if ($redirect = $this->validateSelect()) {
            return $redirect;
        }

        session()->flash($this->viewPrefix.'confirm', $this->request->all());

        $this->prepareConfirmSelect();

        return $this->outputConfirmSelect();
    }

    protected function validateSelect()
    {
        if (empty($this->request->table)) {
            return $this->backIndex('error', 'data_not_selected');
        }

        foreach ($this->request->table as $table) {
            empty($table['selected_rows']) || $this->selected_ids[] = $table['selected_rows'];
        }

        if (empty($this->selected_ids)) {
            return $this->backIndex('error', 'data_not_selected');
        }
    }

    protected function prepareConfirmSelect()
    {
    }

    protected function outputConfirmSelect()
    {
        $this->setupNavigation();

        return view($this->viewPrefix.'.confirmSelect', $this->val);
    }

    public function select(Request $request)
    {
        $this->request = $request;

        if (session()->has($this->viewPrefix.'confirm')) {
            // 確認画面からの遷移
            $this->request->merge(session($this->viewPrefix.'confirm'));

            foreach ($this->request->table as $table) {
                empty($table['selected_rows']) || $this->selected_ids[] = $table['selected_rows'];
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
        if (empty($this->selected_ids)) {
            return;
        }

        $this->deleted_num = call_user_func($this->mainTable.'::destroy', $this->selected_ids);
    }

    protected function outputSelect()
    {
        return $this->backIndex('success', 'data_deleted', $this->deleted_num);
    }

    /* toggle */

    public function toggle($id)
    {
        if ($redirect = $this->checkId($id)) {
            return $redirect;
        }
        $this->val['id'] = $id;

        $table_data = call_user_func($this->mainTable.'::find', $this->val['id']);

        if (empty($table_data->disabled_at)) {
            $table_data->disabled_at = Carbon::now();
        } else {
            $table_data->disabled_at = null;
        }

        $table_data->save();

        $this->val['disabled_at'] = $table_data->disabled_at;
        $this->val[$this->noticeItem] = $table_data->{$this->noticeItem};

        return $this->outputToggle();
    }

    protected function outputToggle()
    {
        if (empty($this->val['disabled_at'])) {
            return $this->backIndex('success', 'data_valid', $this->val[$this->noticeItem]);
        }

        return $this->backIndex('success', 'data_invalid', $this->val[$this->noticeItem]);
    }

    /* copy */

    public function copy($id)
    {
        if ($redirect = $this->checkId($id)) {
            return $redirect;
        }
        $this->val['id'] = $id;

        $table_data = call_user_func($this->mainTable.'::find', $this->val['id']);
        $table_data = $table_data->toArray();

        foreach (['id', 'created_at', 'updated_at', 'deleted_at', 'disabled_at'] as $unset_item) {
            unset($table_data[$unset_item]);
        }

        call_user_func($this->mainTable.'::create', $table_data);

        $this->val[$this->noticeItem] = $table_data[$this->noticeItem];

        return $this->outputCopy();
    }

    protected function outputCopy()
    {
        return $this->backIndex('success', 'data_registered', $this->val[$this->noticeItem]);
    }

    /* upload */

    public function upload(Request $request)
    {
        $this->request = $request;
        $paramname = $this->request->name;

        if (isset($this->request->uploaded_files)) {
            $uploaded_files = $this->request->uploaded_files;
            is_array($uploaded_files) || $uploaded_files = json_decode($uploaded_files, true);

            $html = view(VIEW_PREFIX.'.autoinclude.upload_list', ['files' => $uploaded_files])->render();

            return json_encode([
                'paramname' => $paramname,
                'html' => $html,
            ]);
        }

        $fileupload = $this->request->file('upload');
        $mime_type = $fileupload->getMimeType();

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

        $exist_thumbnail = $this->createThumbnail('upload/'.$filename, 'thumbnail') ? 1 : 0;
        $file = [
            'paramname' => $paramname,
            'filename' => $filename,
            'name' => $fileupload->getClientOriginalName(),
            'size' => $fileupload->getSize(),
            'thumbnail' => $exist_thumbnail,
        ];
        $file['html'] = view(VIEW_PREFIX.'.autoinclude.upload_list', ['files' => [$file]])->render();

        return json_encode($file);
    }

    protected function validateUpload($paramname)
    {
        list($validate, $message) = \Blocs\Validate::upload($this->viewPrefix, $paramname);
        empty($validate) || $this->request->validate($validate, $message);
    }

    /* download */

    public function download(...$argv)
    {
        if (count($argv) > 1) {
            list($size, $filename) = $argv;
        } else {
            list($filename) = $argv;
        }

        $storage = \Storage::disk();
        $filename = 'upload/'.$filename;
        $mime_type = $storage->mimeType($filename);

        if (isset($size)) {
            $thumbnail = $this->createThumbnail($filename, $size);
            if ($thumbnail) {
                return response(\File::get($thumbnail))->header('Content-type', $mime_type);
            }
        } else {
            $thumbnail = $this->createThumbnail($filename, 'thumbnail');
            if ($thumbnail) {
                return response($storage->get($filename))->header('Content-type', $mime_type);
            }
        }

        return $storage->download($filename);
    }

    protected function getSize($size)
    {
        // 画像のサイズを指定できるように
        $download_size = [
            'thumbnail' => [80, 10000, false],
            's' => [380, 10000, false],
            's@2x' => [760, 10000, false],
            'm' => [585, 10000, false],
            'm@2x' => [1170, 10000, false],
            'l' => [1200, 10000, false],
            'l@2x' => [2400, 10000, false],
        ];

        return $download_size[$size];
    }

    /* common */

    protected function backIndex($category, $code, ...$msg_args)
    {
        $category && call_user_func_array('\Blocs\Notice::set', func_get_args());

        return redirect()->route(ROUTE_PREFIX.'.index');
    }

    protected function backEntry($category, $code, $notice_form = '', ...$msg_args)
    {
        if ($category) {
            $msg_args = array_merge([$category, $code], $msg_args);
            call_user_func_array('\Blocs\Notice::set', $msg_args);
        } else {
            $msg_args = array_merge([$code], $msg_args);
        }

        if ($notice_form) {
            return redirect()->route(ROUTE_PREFIX.'.entry', $this->val)
                ->withInput()
                ->withErrors([$notice_form => \Blocs\Lang::get(implode(':', $msg_args))]);
        }

        return redirect()->route(ROUTE_PREFIX.'.entry', $this->val)
            ->withInput();
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

    protected function setupNavigation($navigation_name = null)
    {
        isset($navigation_name) || $navigation_name = VIEW_PREFIX;

        list($navigation, $headline, $breadcrumb) = \Blocs\Navigation::get($navigation_name);
        $this->val['navigation'] = $navigation;
        $this->val['headline'] = $headline;
        $this->val['breadcrumb'] = $breadcrumb;
    }

    /* Private function */

    private function checkId($id)
    {
        if (!$id) {
            return;
        }

        $table_data = call_user_func($this->mainTable.'::find', $id);

        // データが見つからない
        if (empty($table_data)) {
            return $this->backIndex('error', 'data_not_found');
        }
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
