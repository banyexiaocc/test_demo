<?php
/**
 * Created by PhpStorm.
 * User: fu.hy
 * Date: 2019/3/27
 * Time: 16:04
 * 单例模式
 */
Class Site{

    //属性
    public $siteName;
    //本类的静态实例
    protected static $instance=null;
    //禁用掉构造方法
    private function __construct($siteName)
    {
        $this->siteName=$siteName;
    }

    //获取本类的唯一实例
    static function getInstance($siteName="茶啊二中"){
        //判断$instance属性,是否属于当前类
        if(!self::$instance instanceof self){
            self::$instance=new self("茶啊二中1");
        }else{
           return self::$instance;
        }


    }
}



//使用工厂模式生成本类的单一实例
class Factory{
    //创建指定类的实例
    static function create(){
        return pattern::getInstance("茶啊二中2");
    }
}


//=================================
//对象注册树

/**
 * 注册:set() 把对象挂到树上
 * 获取:get() 把对象取下来用
 * 注销:_unset() 销毁对象
 */
class Register{

    //(array)对象池,栽树
    protected static $object=array();

    //生成对象,并放入对象池,上树
    static function set($alias,$object){//别名,对象
        self::$object[$alias]=$object;
    }

    //取下对象
    static function get($alias){
        return self::$object[$alias];
    }

    //注销对象
    static function _unset($alias){
        unset(self::$object[$alias]);
    }

}

//实例化并使用对象树模式

//site对象上树
Register::set("alias_site",Factory::create());
//取下使用
$obj=Register::get("alias_site");
//销毁对象
Register::_unset("alias_site");


//trait 类的使用,有点像interface 不能被实例化
//想在其他类中使用trait类中方法,需要"use trait类名"引入,之后使用方式$this->方法名即可
//优先级:
/**
 * 当前类中的方法和trait类中的方法名和父类的方法名重名了
 * 本类>trait类>父类
 *
 * 当trait类中有相同方法名->重命名
 * use Demo1,Demo2{
    Demo1::hello insteadof Demo2;
 *  Demo2::hello insteadof Demo2hello;
 * }
 *
 */












