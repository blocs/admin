<?php

return [
    'root' => [
        [
            'name' => 'home',
            'lang' => 'template:admin_menu_home',
            'icon' => 'fa-home',
            'role' => true,
        ],
        [
            'lang' => 'template:admin_menu_top',
            'icon' => 'fa-cogs',
            'sub' => 'admin_menu',
            'role' => true,
        ],
    ],
    'admin_menu' => [
        [
            'name' => 'admin.user.index',
            'lang' => 'template:admin_menu_user',
            'icon' => 'fa-users',
        ],
    ],
];
