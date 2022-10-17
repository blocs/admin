<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

class ProfileController extends UserController
{
    public function __construct()
    {
        define('TEMPLATE_PREFIX', 'admin.profile');
        define('ROUTE_PREFIX', 'profile');
        define('TABLE_MAIN', '\App\User');

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
