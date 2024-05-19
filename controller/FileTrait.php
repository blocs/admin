<?php

namespace Blocs\Controllers;

use Illuminate\Http\Request;

trait FileTrait
{
    protected $uploadStorage;

    public function upload(Request $request)
    {
        $this->request = $request;

        $this->validateUpload(request()->get('name'));

        $fileupload = $this->request->file('upload');
        $filename = md5(file_get_contents($fileupload->getPathname()));

        isset($this->uploadStorage) || $this->uploadStorage = 'upload';
        $fileupload->storeAs($this->uploadStorage, $filename);

        $existThumbnail = $this->createThumbnail($this->uploadStorage.'/'.$filename, 'thumbnail') ? 1 : 0;
        $file = [
            'filename' => $filename,
            'name' => $fileupload->getClientOriginalName(),
            'size' => $fileupload->getSize(),
            'thumbnail' => $existThumbnail,
        ];

        return json_encode($file);
    }

    protected function validateUpload($paramname)
    {
        list($rules, $messages) = \Blocs\Validate::upload($this->viewPrefix, $paramname);
        if (empty($rules)) {
            return;
        }

        $labels = $this->getLabel($this->viewPrefix.'.create');
        $this->request->validate($rules, $messages, $labels);
        $validates = $this->getValidate($rules, $messages, $labels);
        doc(['POST' => '入力値'], '入力値を以下の条件で検証して、エラーがあればメッセージをセット', null, $validates);
    }

    /* download */

    public function download($filename, $size = null)
    {
        if ($redirect = $this->checkDownload($filename)) {
            return $redirect;
        }

        isset($this->uploadStorage) || $this->uploadStorage = 'upload';
        $filename = $this->uploadStorage.'/'.$filename;

        $storage = \Storage::disk();
        $mimeType = $storage->mimeType($filename);

        if (isset($size)) {
            // 画像ファイル
            $thumbnail = $this->createThumbnail($filename, $size);
            if ($thumbnail) {
                return response(\File::get($thumbnail))->header('Content-Type', $mimeType);
            }

            // 画像以外のファイル
            return response(base64_decode('R0lGODlhAQABAGAAACH5BAEKAP8ALAAAAAABAAEAAAgEAP8FBAA7'), 200)->header('Content-Type', 'image/gif');
        }

        // 画像ファイル
        $thumbnail = $this->createThumbnail($filename, 'thumbnail');
        if ($thumbnail) {
            return response($storage->get($filename))->header('Content-Type', $mimeType);
        }

        // 画像以外のファイル
        return $storage->download($filename, basename($filename).'.'.\Blocs\Thumbnail::extension($storage->get($filename)));
    }

    protected function checkDownload($filename)
    {
        // abort(404);
    }

    protected function getSize($size)
    {
        // 画像のサイズを指定できるように
        $downloadSize = [
            'thumbnail' => [120, 10000, false],
            's' => [380, 10000, false],
            's@2x' => [760, 10000, false],
            'm' => [585, 10000, false],
            'm@2x' => [1170, 10000, false],
            'l' => [1200, 10000, false],
            'l@2x' => [2400, 10000, false],
        ];

        return $downloadSize[$size];
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
