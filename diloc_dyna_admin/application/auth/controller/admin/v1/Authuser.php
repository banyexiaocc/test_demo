<?php
/**
 * Created by PhpStorm.
 * User: fu.hy
 * Date: 2020/2/24
 * Time: 15:41
 */
namespace app\auth\controller\admin\v1;
use think\Controller;
use think\Db;
use think\facade\Request;//数据接收-静态代理
class Authuser extends Controller
{

    static $redis;//redis连接
    static $app_name="应用名称";
    static $db_config_name="";
    static $uid;
    public function __construct()
    {
        header("Access-Control-Allow-Origin: *");
        ini_set('default_socket_timeout', -1);
        //实例化redis,并连接
        self::$redis = new \redis();
        //self::$redis->pconnect('127.0.0.1',6379);
        self::$redis->connect('39.106.212.136', 6255);
        self::$redis->auth("dyanflowzjs8888dyanflowzjs8888");//连接密码*/
        
        //api请求路径信息
        $param_must = Request::instance()->only('access_token');
        if(!isset($param_must['access_token'])||empty($param_must['access_token'])){
            return_json(200101,"ACCESSTOKEN未传",array());
        }
		$user_info = self::$redis->hGetAll("diloc_dyna_access_token".$param_must['access_token']);
		if(empty($user_info)){
			$user_info=self::$redis->hGetAll("diloc_project_pc_access_token".$param_must['access_token']);
			if(empty($user_info)){
				return_json("200500","用户token失效(不存在)",(object)array());
			}
		}
        self::$uid=$user_info['uid'];
        parent::__construct();
    }
}