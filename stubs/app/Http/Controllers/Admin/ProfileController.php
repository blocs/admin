<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

class ProfileController extends UserController
{
    public function __construct()
    {
        defined('VIEW_PREFIX') || define('VIEW_PREFIX', 'admin');
        defined('ROUTE_PREFIX') || define('ROUTE_PREFIX', 'profile');

        $this->view_prefix = VIEW_PREFIX.'.'.ROUTE_PREFIX;
        $this->table_main = 'App\User';
        $this->notice_item = 'email';
    }

    public function entry($id = 0)
    {
        return parent::entry(\Auth::id());
    }

    public function update($id, Request $request)
    {
        return parent::update(\Auth::id(), $request);
    }

    protected function output_update()
    {
        \Blocs\Notice::set('success', 'admin_profile_updated');

        return redirect()->route('home');
    }
}
