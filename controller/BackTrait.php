<?php

namespace Blocs\Controllers;

trait BackTrait
{
    protected function backIndex($category = null, $message = null)
    {
        $redirectIndex = redirect()->route(prefix().'.index');
        unset($this->val, $this->request, $this->tableData);

        if (!$category) {
            return $redirectIndex;
        }

        // langからメッセージを取得
        $code = implode(':', func_get_args());
        ($langMessage = $this->getMessage($code)) != false && $message = $langMessage;
        docs("メッセージをセット\n・".$message);
        docs(null, '一覧画面に戻る', ['FORWARD' => '!'.prefix().'.index']);

        return $redirectIndex->with([
            'category' => $category,
            'message' => $message,
        ]);
    }

    protected function backCreate($category = null, $message = null, $noticeForm = null, ...$msgArgList)
    {
        $redirectCreate = redirect()->route(prefix().'.create', $this->val)->withInput();
        unset($this->val, $this->request, $this->tableData);
        docs("メッセージをセット\n・".$message);
        docs(null, '新規作成画面に戻る', ['FORWARD' => '!'.prefix().'.create']);

        return $this->backCreateEdit($redirectCreate, $category, $message, $noticeForm, $msgArgList);
    }

    protected function backEdit($category = null, $message = null, $noticeForm = null, ...$msgArgList)
    {
        $redirectEdit = redirect()->route(prefix().'.edit', $this->val)->withInput();
        unset($this->val, $this->request, $this->tableData);
        docs("メッセージをセット\n・".$message);
        docs(null, '編集画面に戻る', ['FORWARD' => '!'.prefix().'.edit']);

        return $this->backCreateEdit($redirectEdit, $category, $message, $noticeForm, $msgArgList);
    }

    private function getMessage($code)
    {
        $langMessage = lang($code);
        if ($langMessage == $code) {
            // langからメッセージを取得できない
            return false;
        }

        return $langMessage;
    }

    private function backCreateEdit($redirect, $category, $message, $noticeForm, $msgArgList)
    {
        if (!$category && !$noticeForm) {
            return $redirect;
        }

        // langからメッセージを取得
        if ($category) {
            $msgArgList = array_merge([$category, $message], $msgArgList);
        } else {
            $msgArgList = array_merge([$message], $msgArgList);
        }
        $code = implode(':', $msgArgList);
        ($langMessage = $this->getMessage($code)) != false && $message = $langMessage;

        if ($category) {
            return $redirect->with([
                'category' => $category,
                'message' => $message,
            ]);
        }

        return $redirect->withErrors([
            $noticeForm => $message,
        ]);
    }
}
