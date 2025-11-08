<?php

namespace Blocs\Controllers;

use Illuminate\Http\Request;

trait FileTrait
{
    protected $uploadStorage;

    public function upload(Request $request)
    {
        $this->request = $request;

        // アップロードするファイルのバリデーション
        $this->validateUpload(request()->get('name'));

        $fileupload = $this->request->file('upload');

        // ファイルのハッシュ値を元にファイル名を生成
        $filename = $this->buildFileHashedName($fileupload);

        // アップロードストレージにファイルを保存
        $storagePath = $this->storeFileToUploadStorage($fileupload, $filename);

        // レスポンス用のファイル情報を構築
        $file = $this->buildFileUploadResponse($fileupload, $filename, $storagePath);

        return json_encode($file);
    }

    protected function validateUpload($paramname)
    {
        // アップロード用のバリデーションルールを取得
        [$rules, $messages] = \Blocs\Validate::upload($this->viewPrefix, $paramname);
        if (empty($rules)) {
            return;
        }

        // バリデーションを実行してエラーがあればメッセージをセット
        $labels = $this->getLabel($this->viewPrefix.'.create');
        $this->request->validate($rules, $messages, $labels);
        $validates = $this->getValidate($rules, $messages, $labels);
        docs(null, '入力値を以下の条件で検証して、エラーがあればメッセージをセットする', null, $validates);
    }

    public function download($filename, $size = null)
    {
        // ダウンロード前のチェック処理（必要に応じてオーバーライド）
        if ($redirect = $this->checkDownload($filename)) {
            return $redirect;
        }

        // アップロードストレージのパスを解決
        $fullPath = $this->buildFileStoragePath($filename);

        // ファイルの存在確認とMIMEタイプを取得
        /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = \Illuminate\Support\Facades\Storage::disk();
        $this->abortFileIfNotExists($storage, $fullPath);
        $mimeType = $storage->mimeType($fullPath);

        // サイズ指定がある場合はサムネイル画像として返す
        if (isset($size)) {
            return $this->buildFileResizedResponse($fullPath, $size, $mimeType);
        }

        // サイズ指定がない場合は元のファイルを返す
        return $this->buildFileOriginalResponse($storage, $fullPath, $mimeType);
    }

    public function thumbnail($filename, $size)
    {
        // サムネイル取得前のチェック処理（必要に応じてオーバーライド）
        if ($redirect = $this->checkDownload($filename)) {
            return $redirect;
        }

        // アップロードストレージのパスを解決
        $fullPath = $this->buildFileStoragePath($filename);

        // ファイルの存在確認とMIMEタイプを取得
        /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = \Illuminate\Support\Facades\Storage::disk();
        $this->abortFileIfNotExists($storage, $fullPath);
        $mimeType = $storage->mimeType($fullPath);

        // 指定サイズのサムネイル画像を返す
        return $this->buildFileResizedResponse($fullPath, $size, $mimeType);
    }

    protected function checkDownload($filename)
    {
        // ダウンロード前のチェック処理（必要に応じてオーバーライド）
        // abort(404);
    }

    protected function getHeaders($filename) {}

    protected function getSize($size)
    {
        // サムネイル生成時の画像サイズ設定（幅、高さ、クロップの有無）
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

    private function buildFileHashedName($fileupload)
    {
        // ファイルの内容をハッシュ化して一意なファイル名を生成
        $hash = md5(file_get_contents($fileupload->getPathname()));
        $extension = $fileupload->getClientOriginalExtension();

        return $hash.'.'.$extension;
    }

    private function storeFileToUploadStorage($fileupload, $filename)
    {
        // アップロードストレージが未設定の場合はデフォルト値を設定
        $this->ensureFileUploadStorageInitialized();

        // ファイルをストレージに保存
        $fileupload->storeAs($this->uploadStorage, $filename);

        return $this->uploadStorage.'/'.$filename;
    }

    private function buildFileUploadResponse($fileupload, $filename, $storagePath)
    {
        // サムネイルが作成可能かチェック
        $existThumbnail = $this->createFileThumbnail($storagePath, 'thumbnail') ? 1 : 0;

        // レスポンス用のファイル情報を配列で返す
        return [
            'filename' => $filename,
            'name' => $fileupload->getClientOriginalName(),
            'size' => $fileupload->getSize(),
            'thumbnail' => $existThumbnail,
        ];
    }

    private function ensureFileUploadStorageInitialized()
    {
        // アップロードストレージが未設定の場合はデフォルトの'upload'を設定
        isset($this->uploadStorage) || $this->uploadStorage = 'upload';
    }

    private function buildFileStoragePath($filename)
    {
        // アップロードストレージのパスを解決して完全なパスを返す
        $this->ensureFileUploadStorageInitialized();

        return $this->uploadStorage.'/'.$filename;
    }

    private function abortFileIfNotExists($storage, $filename)
    {
        // ファイルが存在しない場合は404エラーを返す
        if (! $storage->exists($filename)) {
            abort(404);
        }
    }

    private function buildFileResizedResponse($filename, $size, $mimeType)
    {
        // 指定サイズのサムネイルを生成
        $thumbnail = $this->createFileThumbnail($filename, $size);

        if ($thumbnail) {
            // サムネイルが生成できた場合は画像レスポンスを返す
            return $this->buildFileImageResponse($thumbnail, $filename, $mimeType);
        }

        // サムネイルが生成できない場合は透明なGIF画像を返す
        return $this->buildFileTransparentGifResponse();
    }

    private function buildFileImageResponse($thumbnail, $filename, $mimeType)
    {
        // カスタムヘッダーを取得
        $headers = $this->getHeaders($filename);

        // 画像レスポンスを作成
        $response = response(\Illuminate\Support\Facades\File::get($thumbnail))->header('Content-Type', $mimeType);

        // カスタムヘッダーが設定されている場合は追加
        return $this->addFileCustomHeaders($response, $headers);
    }

    private function buildFileOriginalResponse($storage, $filename, $mimeType)
    {
        // サムネイルが作成可能かチェック
        $thumbnail = $this->createFileThumbnail($filename, 'thumbnail');

        if ($thumbnail) {
            // 画像ファイルの場合はストレージから読み込んでレスポンスを返す
            $headers = $this->getHeaders($filename);
            $response = response($storage->get($filename))->header('Content-Type', $mimeType);

            return $this->addFileCustomHeaders($response, $headers);
        }

        // 画像以外のファイルの場合はストレージのレスポンスを使用
        $headers = $this->getHeaders($filename);

        if (empty($headers)) {
            return $storage->response($filename, basename($filename));
        }

        return $storage->response($filename, basename($filename), $headers);
    }

    private function addFileCustomHeaders($response, $headers)
    {
        // カスタムヘッダーが設定されている場合はレスポンスに追加
        if ($headers) {
            foreach ($headers as $key => $value) {
                $response->header($key, $value);
            }
        }

        return $response;
    }

    private function buildFileTransparentGifResponse()
    {
        // 画像以外のファイル用に1x1の透明なGIF画像を返す
        $transparentGif = base64_decode('R0lGODlhAQABAGAAACH5BAEKAP8ALAAAAAABAAEAAAgEAP8FBAA7');

        return response($transparentGif, 200)->header('Content-Type', 'image/gif');
    }

    private function createFileThumbnail($filename, $size)
    {
        // サムネイルサイズの設定を取得（幅、高さ、クロップの有無）
        $path = \Illuminate\Support\Facades\Storage::path($filename);
        [$width, $height, $crop] = $this->getSize($size);

        // ストレージのファイルからサムネイルを生成して返す
        $thumbnail = \Blocs\Thumbnail::create($path, $width, $height, $crop);

        return $thumbnail;
    }
}
