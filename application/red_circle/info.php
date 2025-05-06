<?php
return [
    // 模块名[必填]
    'name'        => 'red_circle',
    // 模块标题[必填]
    'title'       => '红圈系统岗位及操作手册',
    // 模块唯一标识[必填]，格式：模块名.开发者标识.module
    'identifier'  => 'red_circle.zqk.module',
    // 开发者[必填]
    'author'      => 'zhengqk',
    // 版本[必填],格式采用三段式：主版本号.次版本号.修订版本号
    'version'     => '1.0.0',
    // 模块描述[必填]
    'description' => '红圈系统岗位及操作手册',
    'tables' => [
        'red_circle_position',
        'red_circle_position_document',
    ],
    'database_prefix' => 'dp_'
];