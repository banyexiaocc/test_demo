<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------



// [ 应用入口文件 ]
namespace think;

// [ 应用入口文件 ]
header("Access-Control-Allow-Origin: *");
// 响应类型
header('Access-Control-Allow-Methods:OPTIONS');
header('Access-Control-Allow-Methods:POST');
header('Access-Control-Allow-Methods:PUT');
header('Access-Control-Allow-Methods:GET');
header('Access-Control-Allow-Methods:DELETE');

// 响应头设置
header('Access-Control-Allow-Headers: *');


// 加载基础文件
require __DIR__ . '/../thinkphp/base.php';

// 支持事先使用静态方法设置Request对象和Config对象

// 执行应用并响应
Container::get('app')->run()->send();
