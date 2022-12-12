<?php

/**
 * Copyright (C) 2010 LINEAR JAPAN Co., Ltd. All Rights Reserved.
 *
 * This source code or any portion thereof must not be
 * reproduced or used in any manner whatsoever.
 */

namespace Blocs;

class Notice
{
    public static function get()
    {
        if (empty(session('noticeCategory'))) {
            return [];
        }

        return [
            'noticeCategory' => session('noticeCategory'),
            'noticeMessage' => session('noticeMessage'),
        ];
    }

    public static function set($category, $code, ...$msgArgs)
    {
        \Session::flash('noticeCategory', $category);
        \Session::flash('noticeMessage', \Blocs\Lang::get(implode(':', func_get_args())));
    }
}

/* End of file */
