<?php

return [
    'admin' => [
        [
            'name' => 'home',
            'lang' => '管理トップ',
            'icon' => 'fa-cogs',
            'sub' => 'admin_menu',
        ],
    ],
    'admin_menu' => [
        [
            'name' => 'profile',
            'lang' => 'プロフィール',
            'icon' => 'fa-cog',
            'breadcrumb' => true,
        ],
        [
            'name' => 'user',
            'lang' => 'ユーザー管理',
            'icon' => 'fa-users',
        ],
    ],
];
