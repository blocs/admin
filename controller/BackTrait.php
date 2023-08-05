<?php

namespace Blocs\Controllers;

trait BackTrait
{
    protected function backIndex($category = null, $message = null)
    {
        $resirectIndex = redirect()->route(\Blocs\Common::routePrefix().'.index');

        if (!$category) {
            return $resirectIndex;
        }

        // langからメッセージを取得
        $code = implode(':', func_get_args());
        ($langMessage = $this->getMessage($code)) != false && $message = $langMessage;

        return $resirectIndex->with([
            'category' => $category,
            'message' => $message,
        ]);
    }

    protected function backCreate($category = null, $message = null, $noticeForm = null, ...$msgArgList)
    {
        $resirectCreate = redirect()->route(\Blocs\Common::routePrefix().'.create', $this->val)->withInput();

        if (!$category && !$noticeForm) {
            return $resirectCreate;
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
            return $resirectCreate->with([
                'category' => $category,
                'message' => $message,
            ]);
        }

        return $resirectCreate->withErrors([
            $noticeForm => $message,
        ]);
    }

    protected function backEdit($category = null, $message = null, $noticeForm = null, ...$msgArgList)
    {
        $resirectEdit = redirect()->route(\Blocs\Common::routePrefix().'.edit', $this->val)->withInput();

        if (!$category && !$noticeForm) {
            return $resirectEdit;
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
            return $resirectEdit->with([
                'category' => $category,
                'message' => $message,
            ]);
        }

        return $resirectEdit->withErrors([
            $noticeForm => $message,
        ]);
    }

    private function getMessage($code)
    {
        $langMessage = \Blocs\Lang::get($code);
        if ($langMessage == $code) {
            // langからメッセージを取得できない
            return false;
        }

        return $langMessage;
    }
}
