<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

class ProfileController extends UserController
{
    public function __construct()
    {
        defined('ROUTE_PREFIX') || define('ROUTE_PREFIX', 'profile');

        $this->template_prefix = 'admin.profile';
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
