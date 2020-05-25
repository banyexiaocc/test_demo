<?php
/**
 * Created by PhpStorm.
 * User: fu.hy
 * Date: 2019/3/28
 * Time: 15:41
 */

namespace app\common;

/**
 * Class EmptyFunction
 * @package app\common
 * 空操作默认调用
 */

class EmptyFunction
{

    private $mess;
    function __construct($mess="调用方法不存在,请重试!")
    {
        $this->mess=$mess;
    }
    /**
     * 设置空方法错误提示页面
     */
    function setErrorMess($mess)
    {
        $this->mess=$mess;
    }
    function getErrorMess(){

        return return_json("200204",$this->mess,array());
    }
}