<?php
/**
 * Created by PhpStorm.
 * User: fu.hy
 * Date: 2019/3/28
 * Time: 9:07
 *
 * 程序设计模式
 * 单例模式
 * 工厂模式
 * 注册树模式
 *
 *
 *
 */

//单例模式
class Site{

    //当前雷属性
    public $siteName;
    //本类的静态实例
    protected static $instance = null;
    //禁用掉构造器
    private function __construct($siteName){

        $this->siteName=$siteName;
    }
   //获取本类的唯一实例
    static function getInstance($siteName="徐小明"){

        //判断属性是否是当前类的一个实例,如果不是,就实例化本类
        if(!self::$instance instanceof self) {
            self::$instance = new self($siteName);
        }else{
            return self::$instance;

        }
    }
}

//用工厂模式,生成奔雷的单一实例
class factory{
    //创建指定类的实例
    static function create(){
        return Site::getInstance("徐小明1");
    }
}



/**
 * 对象注册树
 * 功能 :set 对象上树
 *     :get  取下树上对象进行使用
 *     :_unset  注销对象
*/
class Register{

    //栽树
    protected static $objectTree=array();

    //生成对象,并且上树
    static function set($alias,$object){//别名,对象
        self::$objectTree[$alias]=$object;
    }

    //取下对象并使用
    static function get($alias){
        return self::$objectTree[$alias];
    }

    //销毁树上对象
    static function _unset($alias){
        unset(self::$objectTree[$alias]);
    }

}


//使用
/**
 * 1.将site类上树
 * 2.树上取对象
 */

Register::set('object_site',Factory::create());
$get_object=Register::get("object_site");
var_dump($get_object);




