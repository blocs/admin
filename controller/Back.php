<?php

namespace Blocs\Controllers;

trait Back
{
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
}
