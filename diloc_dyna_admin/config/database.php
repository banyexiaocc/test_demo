<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    /**
     * 戴纳总数据库数据库链接配置
     */
    // 数据库类型
    'type'            => '\think\mongo\Connection',
    // 服务器地址
    'hostname'        => '39.106.212.136',
    // 数据库名
    'database'        => 'smart_lab_admin',
    // 用户名
    'username'        => 'Dynazjs8888',
    // 密码
    'password'        => 'dyanflowzjs8888dyanflowzjs8888',
    // 端口
    'hostport'        => '27017',
    // 连接dsn
    'dsn'             => '',
    // 数据库连接参数
    'params'          => [],
    // 数据库编码默认采用utf8
    'charset'         => 'utf8',
    // 数据库表前缀
    'prefix'          => '',
    // 数据库调试模式
    'debug'           => true,
    // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
    'deploy'          => 0,
    // 数据库读写是否分离 主从式有效
    'rw_separate'     => false,
    // 读写分离后 主服务器数量
    'master_num'      => 1,
    // 指定从服务器序号
    'slave_no'        => '',
    // 自动读取主库数据
    'read_master'     => false,
    // 是否严格检查字段是否存在
    'fields_strict'   => true,
    // 数据集返回类型
    'resultset_type'  => 'array',
    // 自动写入时间戳字段
    'auto_timestamp'  => false,
    // 时间字段取出后的默认时间格式
    'datetime_format' => 'Y-m-d H:i:s',
    // 是否需要进行SQL性能分析
    'sql_explain'     => false,
    // Builder类
    'builder'         => '',
    // Query类
    'query'           => '\\think\\mongo\\Query',//,'\\think\\db\\Query',
    // 是否需要断线重连
    'break_reconnect' => true,
    // 断线标识字符串
    'break_match_str' => [],

    //workman 长连接 连接数据库使用
    'break_reconnect' => true,
    //mongo id字段当成mysql的id使用
    'pk_convert_id' => true,

];
