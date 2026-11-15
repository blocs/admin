<?php

namespace Blocs\Controllers;

use Blocs\Common;
use Blocs\Compiler\BlocsCompiler;
use Blocs\Menu;
use Blocs\Option;
use Blocs\Validate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait CommonTrait
{
    protected function addOption($formName, $optionList)
    {
        Option::add($formName, $optionList);
    }

    /**
     * 検索条件やページ番号などを、画面を移動しても引き継げるようセッションに保持する。
     * URLパラメータ、フォーム送信、セッションの順に値を探す。
     */
    protected function keepItem($keyItem)
    {
        // 既に値が設定されている場合は処理をスキップ
        if (isset($this->val[$keyItem])) {
            return;
        }

        $sessionKey = $this->viewPrefix.'_'.$keyItem;

        // クリアリクエストがあればセッションを削除
        if (request()->has('clear')) {
            session()->forget($sessionKey);

            return;
        }

        // viewPrefixが変更された場合はセッションをクリア
        if (session('viewPrefix') !== $this->viewPrefix) {
            session()->forget($sessionKey);
        }

        // 「0」や空文字も届いた値として扱うため、真偽ではなく有無で判定する
        if (request()->query->has($keyItem)) {
            $this->storeKeepItem($keyItem, request()->query($keyItem), $sessionKey);
            docs(['GET' => $keyItem], 'URLパラメータで<'.$keyItem.'>を受け取ったら、その値を一時保存する', ['セッション' => $keyItem]);

            return;
        }

        if (request()->has($keyItem)) {
            $this->storeKeepItem($keyItem, request()->$keyItem, $sessionKey);
            docs(['POST' => $keyItem], 'フォーム送信で<'.$keyItem.'>が届いたら、その値を一時保存する', ['セッション' => $keyItem]);

            return;
        }

        // セッションから値を復元
        if (session()->has($sessionKey)) {
            $this->val[$keyItem] = session($sessionKey);
        }
        docs(['セッション' => $keyItem], 'セッションに<'.$keyItem.'>が残っていれば、その値を読み戻す');
    }

    /**
     * 空でない文字列はそのまま、空でない配列は保持済みの値とマージして、セッションと$this->valに保存する。
     * それ以外の無効な値の場合はセッションを削除する。
     */
    private function storeKeepItem(string $keyItem, $keyValue, string $sessionKey): void
    {
        if (is_array($keyValue) && count($keyValue)) {
            $keptValue = session($sessionKey);
            $keyValue = array_merge(is_array($keptValue) ? $keptValue : [], $keyValue);
        } elseif (! is_string($keyValue) || $keyValue === '') {
            session()->forget($sessionKey);

            return;
        }

        session([$sessionKey => $keyValue]);
        $this->val[$keyItem] = $keyValue;
    }

    protected function getCurrent($id)
    {
        docs(['GET' => 'id', 'データベース' => $this->loopItem], '# 対象レコードを取り出す');
        $this->tableData = $this->mainTable::findOrFail($id);
    }

    // テーブルのデータと入力値を再帰的にマージ
    protected static function mergeTable($table, $request)
    {
        // 両方が配列でない場合はそのまま返す
        if (! is_array($table) || ! is_array($request)) {
            return $table;
        }

        foreach ($request as $key => $value) {
            if (isset($table[$key]) && is_array($value) && is_array($table[$key])) {
                // 両方が配列の場合は再帰的にマージ
                $table[$key] = self::mergeTable($table[$key], $value);
            } else {
                // それ以外の場合は値を上書き
                $table[$key] = $value;
            }
        }

        return $table;
    }

    protected function setupMenu()
    {
        // メニュー設定を取得してビューに渡す
        [$this->val['menu'], $this->val['headline'], $this->val['breadcrumb']] = Menu::get();
        docs(['設定ファイル' => 'config/menu.php'], '設定ファイルからメニューや見出しを読み込み、画面に渡す');

        // keepItemメソッドで使用するviewPrefixをセッションに保存
        isset($this->viewPrefix) && session(['viewPrefix' => $this->viewPrefix]);
    }

    /**
     * メニューを用意して画面を生成する。生成後は保持していた入力値などを手放す。
     */
    protected function outputView(string $viewName, string $docsMessage)
    {
        $this->setupMenu();

        $view = view($this->viewPrefix.'.'.$viewName, $this->val);
        $this->releaseState();
        docs($docsMessage);

        return $view;
    }

    /**
     * 画面生成やリダイレクトの前に、保持していたプロパティをクリーンアップする。
     */
    protected function releaseState(): void
    {
        unset($this->val, $this->request, $this->tableData);
    }

    /**
     * テンプレートに書かれた条件で入力値を検証する。条件が無ければ何もせず false を返す。
     */
    protected function validateWithTemplate($request, string $template, $docsInput = null, ?string $docsMessage = null): bool
    {
        [$rules, $messages] = Validate::get($template, $request);
        if (empty($rules)) {
            return false;
        }

        $this->validateWithRules($request, $rules, $messages, $template, $docsInput, $docsMessage);

        return true;
    }

    /**
     * 入力値を検証する。エラーがあれば ValidationException が投げられ、元の画面に戻る。
     */
    protected function validateWithRules($request, array $rules, array $messages, string $labelTemplate, $docsInput = null, ?string $docsMessage = null): void
    {
        $labels = $this->getLabel($labelTemplate);
        $request->validate($rules, $messages, $labels);
        docs(
            $docsInput,
            $docsMessage ?? '入力値を以下の条件で検証して、エラーがあればメッセージをセットする',
            null,
            $this->getValidate($rules, $messages, $labels)
        );
    }

    protected function getLabel($template)
    {
        // 設定ファイルからラベル情報を読み込む
        $path = Common::getPath($template);
        $config = Common::readConfig($path);

        $labels = [];
        foreach ($config['label'][$path] ?? [] as $formName => $label) {
            $labels[$formName] = $this->processLabel($label);
        }

        return $labels;
    }

    /**
     * 項目名として使えるよう、タグと末尾の必須マーク（*）を取り除く。
     */
    protected function processLabel($label)
    {
        if (strpos($label, 'data-') !== false) {
            // data-属性を含む場合はBlocsCompilerでレンダリング
            static $blocsCompiler;
            $blocsCompiler ??= new BlocsCompiler;
            $label = $blocsCompiler->render($label);
        }

        $labelText = trim(strip_tags($label));

        return preg_replace('/\s*[*＊]+$/u', '', $labelText) ?? $labelText;
    }

    protected function getValidate($rules, $messages, $labels)
    {
        $validates = [];

        foreach ($rules as $formName => $formValidates) {
            foreach ($formValidates as $formValidate) {
                // ルールが文字列ならそのまま、オブジェクトならクラス名の末尾部分を表示に使う
                $validateString = is_string($formValidate) ? $formValidate : class_basename($formValidate);

                // メッセージキーはルール名（コロン以前の部分）から作る
                [$ruleName] = explode(':', $validateString, 2);

                $validates[] = [
                    'name' => $labels[$formName] ?? $formName,
                    'validate' => $validateString,
                    'message' => $messages[$formName.'.'.$ruleName] ?? '',
                ];
            }
        }

        return $validates;
    }

    /**
     * 対象レコードを読み込み、IDと（渡されていれば）リクエストを保持する。
     */
    protected function loadCurrent($id, ?Request $request = null)
    {
        $this->getCurrent($id);
        $this->val['id'] = $id;

        if ($request !== null) {
            $this->request = $request;
        }
    }

    /**
     * トランザクション内で新規データを作成する。
     */
    protected function createRecord(array $requestData)
    {
        return DB::transaction(fn () => $this->mainTable::create($requestData), 10);
    }

    /**
     * データ削除を実行（エラーは上位に投げる）
     */
    protected function deleteRecords($ids)
    {
        try {
            return $this->mainTable::destroy($ids);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * ログ用のデータを準備（保存した内容 + ID）
     */
    protected function setLogData(array $requestData, $id)
    {
        $this->logData = (object) $requestData;
        $this->logData->id = $id;
    }
}
