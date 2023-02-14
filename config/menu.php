<?php

return [
    'root' => [
        [
            'name' => 'home',
            'lang' => '管理トップ',
            'icon' => 'fa-cogs',
            'sub' => 'admin_menu',
        ],
    ],
    'admin_menu' => [
        [
            'name' => 'profile.edit',
            'argv' => ['id' => 0],
            'lang' => 'プロフィール',
            'icon' => 'fa-cog',
            'breadcrumb' => true,
        ],
        [
            'name' => 'user.index',
            'lang' => 'ユーザー管理',
            'icon' => 'fa-users',
        ],
    ],
];
