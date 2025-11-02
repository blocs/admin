<?php

return [
    'root' => [
        0 => [
            'name' => 'home',
            'lang' => 'template:admin_menu_home',
            'icon' => 'fa-home',
            'role' => true,
        ],
        1 => [
            'lang' => 'template:admin_menu_top',
            'icon' => 'fa-cogs',
            'sub' => 'admin_menu',
        ],
    ],
    'admin_menu' => [
        0 => [
            'name' => 'admin.user.index',
            'lang' => 'template:admin_menu_user',
            'icon' => 'fa-users',
        ],
    ],
];
