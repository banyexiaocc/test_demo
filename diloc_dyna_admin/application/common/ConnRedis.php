<?php
/**
 * Created by PhpStorm.
 * User: fu.hy
 * Date: 2019/4/9
 * Time: 10:04
 */

namespace app\common;


class ConnRedis
{

    public $redis;
    function __construct()
    {
        //实例化redis,并连接
        $this->redis = new \redis();
    }

    /**
     * 连接redis
     */
    function createConn(){
        //self::$redis->pconnect('127.0.0.1',6379);
        $this->redis->connect('39.106.212.136',6255);
        $this->redis->auth("dyanflowzjs8888dyanflowzjs8888");
        return $this->redis;
    }

}