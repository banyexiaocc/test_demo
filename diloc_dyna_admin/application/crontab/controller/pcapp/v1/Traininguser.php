<?php
/**
 * Created by PhpStorm.
 * User: fu.hy
 * Date: 2020/2/24
 * Time: 15:03
 */
namespace app\crontab\controller\pcapp\v1;
use think\Controller;
use think\facade\Request;//数据接收-静态代理
use think\Db;

class Traininguser
{


    public function trainingBeOverdue()
    {
        $project_idlist=Db::table("project")
            ->where([['state','=','1'],['status','in','1,2']])
            ->field("id")
            ->select();
        if(empty($project_idlist))
        {
            return true;
        }
        $app_id = array_column($project_idlist,'id');
        $db_name_list = Db::table("project_config")
            ->where([['app_id', 'in', $app_id]])
            ->field("db_name,app_id")
            ->select();
        foreach ($db_name_list as $k => $v )
        {
            if(empty($v['db_name'])){
                continue;
            }
            $list = Db::table($v['db_name'].'.training_certification_user_relation')
                ->where('state','1')
                ->whereIn('status','2,3,7')
                ->where('end_date','<',date('Y-m-d'))
                ->update(['status' => '4']);
        }


    }


	/**
	 * 程序代码版本号输出
	 */
    function codeVersion(){
    	$version=Db::table("application_version")->where([['state','=','1'],['status','=','1']])->field("version_num")->order("create_time",'desc')->find();
    	if(empty($version)){
    		return_json(200204,"版本号不存在",array("version"=>""));
		}else{
    		return_json(200200,"版本号获取成功",array("version"=>$version['version_num']));
		}
	}



}
