<?php

namespace Blocs\Controllers;

use Illuminate\Http\Request;

trait FileTrait
{
    protected $uploadStorage;

    public function upload(Request $request)
    {
        $this->request = $request;
        $paramname = $this->request->name;

        if ($this->request->has('uploadedFile')) {
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

        $this->validateUpload($paramname);

        isset($this->uploadStorage) || $this->uploadStorage = 'upload';
        $filename = md5(file_get_contents($fileupload->getPathname()));
        $fileupload->storeAs($this->uploadStorage, $filename);

        $existThumbnail = $this->createThumbnail($this->uploadStorage.'/'.$filename, 'thumbnail') ? 1 : 0;
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

        isset($this->uploadStorage) || $this->uploadStorage = 'upload';
        $filename = $this->uploadStorage.'/'.$filename;

        $storage = \Storage::disk();
        $mimeType = $storage->mimeType($filename);

        if (isset($size)) {
            $thumbnail = $this->createThumbnail($filename, $size);
            if ($thumbnail) {
                return response(\File::get($thumbnail))->header('Content-Type', $mimeType);
            }
        }

        $thumbnail = $this->createThumbnail($filename, 'thumbnail');
        if ($thumbnail) {
            return response($storage->get($filename))->header('Content-Type', $mimeType);
        }

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

    private function createThumbnail($filename, $size)
    {
        // ストレージからサムネイルファイル作成
        $path = \Storage::path($filename);
        list($width, $height, $crop) = $this->getSize($size);
        $thumbnail = \Blocs\Thumbnail::create($path, $width, $height, $crop);

        return $thumbnail;
    }
}
