<?php

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

    public static function set($category, $code)
    {
        \Session::flash('noticeCategory', $category);
        \Session::flash('noticeMessage', \Blocs\Lang::get(implode(':', func_get_args())));
    }
}

/* End of file */
