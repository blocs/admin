<?php

namespace Blocs\Controllers;

use Blocs\Thumbnail;
use Blocs\Validate;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

trait FileTrait
{
    protected $uploadStorage;

    public function upload(Request $request)
    {
        $this->request = $request;

        // アップロードするファイルのバリデーション
        $this->validateUpload(request()->get('name'));

        $fileupload = $this->request->file('upload');
        if ($fileupload === null || ! $fileupload->isValid()) {
            abort(422);
        }

        // 同じ内容のファイルは同じ名前になるよう、内容のハッシュ値をファイル名にする
        $extension = strtolower((string) $fileupload->guessExtension());
        if ($extension === '' || preg_match('/[^a-z0-9]/', $extension)) {
            abort(422);
        }
        $filename = md5_file($fileupload->getPathname()).'.'.$extension;

        // アップロードストレージ（未設定なら upload）にファイルを保存
        $this->uploadStorage ??= 'upload';
        $fileupload->storeAs($this->uploadStorage, $filename);

        // レスポンス用のファイル情報（サムネイルが作成可能かどうかを含む）を返す
        return json_encode([
            'filename' => $filename,
            'name' => $fileupload->getClientOriginalName(),
            'size' => $fileupload->getSize(),
            'thumbnail' => $this->createFileThumbnail($this->uploadStorage.'/'.$filename, 'thumbnail') ? 1 : 0,
        ]);
    }

    protected function validateUpload($paramname)
    {
        if (! is_string($paramname) || $paramname === '') {
            abort(403);
        }

        // テーブルタイプに対応しているフォーム名を取得
        if (preg_match('/\[([^][]+)\]$/', $paramname, $matches)) {
            $paramname = $matches[1];
        }

        // アップロード用のバリデーションルールを取得
        // null = config['upload'] に無い未知フィールド → 403
        // [[], []] = ai-upload 宣言済みだが validate 無し → 従来どおりスキップ
        $uploadValidation = Validate::upload($this->viewPrefix, $paramname);
        if ($uploadValidation === null) {
            abort(403);
        }

        [$rules, $messages] = $uploadValidation;
        if (! empty($rules)) {
            $this->validateWithRules($this->request, $rules, $messages, $this->viewPrefix.'.create');
        }
    }

    public function download($filename, $size = null)
    {
        return $this->respondFile($filename, $size);
    }

    public function thumbnail($filename, $size)
    {
        return $this->respondFile($filename, $size);
    }

    protected function checkDownload($filename)
    {
        // ダウンロード前のチェック処理（必要に応じてオーバーライド）
        // abort(404);
    }

    protected function getHeaders($filename) {}

    /**
     * 画像以外のファイルを返すときの Content-Disposition。
     * ブラウザ内で表示させたい種類（PDFなど）があれば、継承先で inline を返す。
     */
    protected function getDisposition($filename, $mimeType): string
    {
        return 'attachment';
    }

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

        // 未定義のサイズで原本を返してしまわないよう、ここで弾く
        if (! isset($downloadSize[$size])) {
            abort(404);
        }

        return $downloadSize[$size];
    }

    /**
     * 保存済みのファイルを返す。サイズ指定があれば、その大きさのサムネイル画像を返す。
     */
    private function respondFile($filename, $size)
    {
        // ダウンロード前のチェック処理（必要に応じてオーバーライド）
        if ($redirect = $this->checkDownload($filename)) {
            return $redirect;
        }

        // アップロードストレージのパスを解決
        $this->uploadStorage ??= 'upload';
        $path = $this->uploadStorage.'/'.$this->sanitizeStoredFilename($filename);

        // ファイルの存在確認とMIMEタイプを取得
        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk();
        if (! $storage->exists($path)) {
            abort(404);
        }
        $mimeType = $storage->mimeType($path);

        if (isset($size)) {
            $thumbnail = $this->createFileThumbnail($path, $size);
            if (! $thumbnail) {
                // サムネイルが生成できない場合（画像以外など）は1x1の透明なGIF画像を返す
                $transparentGif = base64_decode('R0lGODlhAQABAGAAACH5BAEKAP8ALAAAAAABAAEAAAgEAP8FBAA7');

                return response($transparentGif, 200)->header('Content-Type', 'image/gif');
            }

            return response(File::get($thumbnail))->header('Content-Type', $mimeType)->withHeaders($this->getHeaders($path) ?: []);
        }

        // サムネイルが作成できるファイルは画像とみなし、ストレージから読み込んでそのまま表示する
        $isImage = (bool) $this->createFileThumbnail($path, 'thumbnail');

        // カスタムヘッダーを取得（継承先の指定を優先する）
        $headers = array_merge(['X-Content-Type-Options' => 'nosniff'], $this->getHeaders($path) ?: []);

        if ($isImage) {
            return response($storage->get($path))->header('Content-Type', $mimeType)->withHeaders($headers);
        }

        // 画像以外は、HTMLなどが管理画面と同じオリジンで開かれないよう、既定では添付として返す
        return $storage->response($path, basename($path), $headers, $this->getDisposition($path, $mimeType));
    }

    /**
     * ディレクトリをさかのぼる指定などを弾き、ファイル名だけを受け付ける。
     */
    private function sanitizeStoredFilename($filename): string
    {
        $normalized = str_replace('\\', '/', (string) $filename);
        if ($normalized === '' || $normalized === '.' || $normalized !== basename($normalized) || str_contains($normalized, '..')) {
            abort(404);
        }

        return $normalized;
    }

    private function createFileThumbnail($path, $size)
    {
        // サムネイルサイズの設定を取得（幅、高さ、クロップの有無）して、ストレージのファイルから生成する
        [$width, $height, $crop] = $this->getSize($size);

        return Thumbnail::create(Storage::path($path), $width, $height, $crop);
    }
}
