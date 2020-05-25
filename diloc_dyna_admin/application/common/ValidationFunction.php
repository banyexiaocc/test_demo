<?php
/**
 * Created by PhpStorm.
 * User: fu.hy
 * Date: 2020/2/24
 * Time: 14:46
 */

namespace app\common;
use think\Request;


class ValidationFunction
{

    public $redis;
    function __construct()
    {
        //实例化redis,并连接
        $this->redis = new \redis();
    }
    /**
     * access_token验证
     */
    function validationToken(){
        $param=Request::instance()->only('access_token');
        //self::$redis->pconnect('127.0.0.1',6379);
        $this->redis->connect('39.106.212.136',6255);
        $this->redis->auth("dyanflowzjs8888dyanflowzjs8888");


        dump($param);



        //return $this->redis;
    }
}