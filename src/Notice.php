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
        if (empty(session('notice_category'))) {
            return [];
        }

        return [
            'notice_category' => session('notice_category'),
            'notice_message' => session('notice_message'),
        ];
    }

    public static function set($category, $code, ...$msg_args)
    {
        \Session::flash('notice_category', $category);
        \Session::flash('notice_message', \Blocs\Lang::get(implode(':', func_get_args())));
    }
}

/* End of file */
