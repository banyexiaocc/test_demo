<?php
/**
 * Created by PhpStorm.
 * User: fu.hy
 * Date: 2020/2/24
 * Time: 15:03
 */
namespace app\project\controller\admin\v1;
use think\Controller;
use think\facade\Request;//数据接收-静态代理
use think\Db;
use app\project\validate;
use app\auth\controller\admin\v1\Authuser;

class Projectconfig extends Authuser
{

    //方法已经可用
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 项目通用设置信息获取
     */
    function configInfo(){
        header("Access-Control-Allow-Origin: *");
        $param=Request::instance()->only("app_id,show_type");
        if(!isset($param['app_id'])||empty($param['app_id'])){
            return_json(200101,"项目编号未传",array());
        }
        if(!isset($param['show_type'])||empty($param['show_type'])){
            return_json(200101,"要显示的配置类型未传",array());
        }

        switch($param['show_type']){
            case 'General':
                $fields="db_name,registry_set,user_default_headimg";
                $sign="通用配置";
                break;
            case 'Ronglian':
                $fields="cloud_account,cloud_token,cloud_appid,code_minimum_interval,code_savetime_interval";
                $sign="短信配置";
                break;
            case 'Domain':
                $fields="project_domain_name,website_name,project_password,search_keywords";
                $sign="域名配置";
                break;
            case 'Shared':
                $fields="share_permissions";
                $sign="共享配置";
                break;
            case 'Wechat':
                $fields="wechat_publicnumber_name,wechat_appid,wechat_secret";
                $sign="公众号配置";
                break;
			case 'Apply':
				$fields="registered_fields";
				$sign="用户加入项目必须完善的字段";
				break;
            default:
                return_json(200101,"配置类型标记传值有误",array());
        }

        $info=Db::table("project_config")->where([['app_id','=',$param['app_id']],['state','=','1'],['status','=','1']])->field("app_id,".$fields)->find();
        if(empty($info)){
            return_json(200204,"配置数据不存在,请先设置",array());
        }else{

            if(isset($info['db_name'])&&!empty($info['db_name'])){
                $db_name_arr=explode("_",$info['db_name']);
                unset($db_name_arr[0],$db_name_arr[1]);
                $info['db_name']=implode("_",$db_name_arr);
            }
            return_json(200200,$sign."配置数据获取成功",$info);
        }


    }
    /**
     *项目配置-通用配置
     */
    function configGeneralEdit(){
        header("Access-Control-Allow-Origin: *");
        $param=Request::instance()->only("app_id,db_name,registry_set,user_default_headimg");
        $validate = new validate\Projectconfig;
        if (!$validate->scene('configGeneralEdit')->check($param)) {
            return_json(200101,$validate->getError(),array());
        }
        if(!isset($param['user_default_headimg'])||empty($param['user_default_headimg'])){
            return_json(200101,"用户默认头像未传",array());
        }
        $if_exits=Db::table("project_config")->where([['app_id','=',$param['app_id']],['state','=','1'],['status','=','1']])->find();
        if(!empty($if_exits)){
            if(isset($param['db_name'])&&isset($if_exits['db_name'])&&"smart_lab_".$param['db_name']!=$if_exits['db_name']&&$if_exits['db_name']!=""){

                return_json(200101,"不能修改数据库名称!",array());
            }
        }else{
            //判断重命名数据库
            $if_exitsdb_name=Db::table("project_config")->where([['state','=','1'],['status','=','1'],['db_name','=',$param['db_name']]])->find();
            if(!empty($if_exitsdb_name)){
                return_json(200101,"数据库名称被占用",array());
            }
        }

        if(isset($param['db_name'])&&!empty($param['db_name'])){
            $param['db_name']="smart_lab_".$param['db_name'];
        }



        if(!isset($if_exits['cloud_account'])){
            $param['cloud_account']="";
        }
        if(!isset($if_exits['cloud_token'])){
            $param['cloud_token']="";
        }
		if(!isset($if_exits['registered_fields'])){
			$param['registered_fields']=array();
		}
        if(!isset($if_exits['cloud_appid'])){
            $param['cloud_appid']="";
        }
        if(!isset($if_exits['code_minimum_interval'])){
            $param['code_minimum_interval']="";
        }
        if(!isset($if_exits['code_savetime_interval'])){
            $param['code_savetime_interval']="";
        }
        if(!isset($if_exits['project_domain_name'])){
            $param['project_domain_name']="";
        }
        if(!isset($if_exits['website_name'])){
            $param['project_domain_name']="";
        }
        if(!isset($if_exits['project_password'])){
            $param['project_domain_name']="";
        }
        if(!isset($if_exits['search_keywords'])){
            $param['search_keywords']="";
        }
        if(!isset($if_exits['share_permissions'])){
            $param['share_permissions']=array();
        }
        if(!isset($if_exits['wechat_publicnumber_name'])){
            $param['wechat_publicnumber_name']=array();
        }
        if(!isset($if_exits['wechat_appid'])){
            $param['wechat_appid']=array();
        }
        if(!isset($if_exits['wechat_secret'])){
            $param['wechat_secret']=array();
        }




        if(empty($if_exits)){

            if(!isset($param['db_name'])){
                $param['db_name']="";
            }
            if(!isset($param['registry_set'])){
                $param['registry_set']="3";
            }
            if(!isset($param['user_default_headimg'])){
                $param['user_default_headimg']="";
            }

            $param['remark']="暂无";
            $param['state']="1";
            $param['status']="1";
            $param['sort']="1";
            $param['create_time']=fun_date(1);
            $add=Db::table("project_config")->insertGetId($param);
            if($add){
                return_json(200200,"项目通用配置设置成功",array());
            }else{
                return_json(200204,"项目通用配置设置失败",array());
            }
        }else{
            $edit=Db::table("project_config")->where([['app_id','=',$param['app_id']]])->update($param);
            if($edit!==false){
                return_json(200200,"项目通用配置设置成功",array());
            }else{
                return_json(200204,"项目通用配置设置失败",array());
            }
        }
    }
    /**
     * 项目配置-短信配置
     */
    function configRonglianEdit(){
        header("Access-Control-Allow-Origin: *");
        $param=Request::instance()->only("app_id,cloud_account,cloud_token,cloud_appid,code_minimum_interval,code_savetime_interval");
        $validate = new validate\Projectconfig;
        if (!$validate->scene('configRonglianEdit')->check($param)) {
            return_json(200101,$validate->getError(),array());
        }
        $if_exits=Db::table("project_config")->where([['app_id','=',$param['app_id']],['state','=','1'],['status','=','1']])->find();


        if(!isset($if_exits['db_name'])){
            $param['db_name']="";
        }
        if(!isset($if_exits['registry_set'])){
            $param['registry_set']="";
        }
		if(!isset($if_exits['registered_fields'])){
			$param['registered_fields']=array();
		}
        if(!isset($if_exits['user_default_headimg'])){
            $param['user_default_headimg']="";
        }

        if(!isset($if_exits['project_domain_name'])){
            $param['project_domain_name']="";
        }
        if(!isset($if_exits['website_name'])){
            $param['website_name']="";
        }
        if(!isset($if_exits['project_password'])){
            $param['project_password']="";
        }
        if(!isset($if_exits['search_keywords'])){
            $param['search_keywords']="";
        }

        if(!isset($if_exits['share_permissions'])){
            $param['share_permissions']=array();
        }

        if(!isset($if_exits['wechat_publicnumber_name'])){
            $param['wechat_publicnumber_name']="";
        }
        if(!isset($if_exits['wechat_appid'])){
            $param['wechat_appid']="";
        }
        if(!isset($if_exits['wechat_secret'])){
            $param['wechat_secret']="";
        }


        if(empty($if_exits)){
            if(!isset($param['cloud_account'])){
                $param['cloud_account']="";
            }
            if(!isset($param['cloud_token'])){
                $param['cloud_token']="";
            }
            if(!isset($param['cloud_appid'])){
                $param['cloud_appid']="";
            }
            if(!isset($param['code_minimum_interval'])){
                $param['code_minimum_interval']="";
            }
            if(!isset($param['code_savetime_interval'])){
                $param['code_savetime_interval']="";
            }
            $param['remark']="暂无";
            $param['state']="1";
            $param['status']="1";
            $param['sort']="1";
            $param['create_time']=fun_date(1);
            $add=Db::table("project_config")->insertGetId($param);
            if($add){
                return_json(200200,"项目短信配置设置成功",array());
            }else{
                return_json(200204,"项目短信配置设置失败",array());
            }
        }else{
           if(!isset($param)||empty($param)){
               return_json(200101,"要修改的字段未传",array());
           }
            $edit=Db::table("project_config")->where([['app_id','=',$param['app_id']]])->update($param);
            if($edit!==false){
                return_json(200200,"项目短信配置设置成功",array());
            }else{
                return_json(200204,"项目短信设置失败",array());
            }

        }

    }
    /**
     * 项目配置-域名配置
     */
    function configDomainEdit(){
        header("Access-Control-Allow-Origin: *");
        $param=Request::instance()->only("app_id,project_domain_name,website_name,project_password,search_keywords");
        $validate = new validate\Projectconfig;
        if (!$validate->scene('configDomainEdit')->check($param)) {
            return_json(200101,$validate->getError(),array());
        }
        $if_exits=Db::table("project_config")->where([['app_id','=',$param['app_id']],['state','=','1'],['status','=','1']])->find();

        if(!isset($if_exits['db_name'])){
            $param['db_name']="";
        }
        if(!isset($if_exits['registry_set'])){
            $param['registry_set']="";
        }
        if(!isset($if_exits['user_default_headimg'])){
            $param['user_default_headimg']="";
        }
		if(!isset($if_exits['registered_fields'])){
			$param['registered_fields']=array();
		}

        if(!isset($if_exits['cloud_account'])){
            $param['cloud_account']="";
        }
        if(!isset($if_exits['cloud_token'])){
            $param['cloud_token']="";
        }
        if(!isset($if_exits['cloud_appid'])){
            $param['cloud_appid']="";
        }
        if(!isset($if_exits['code_minimum_interval'])){
            $param['code_minimum_interval']="";
        }
        if(!isset($if_exits['code_savetime_interval'])){
            $param['code_savetime_interval']="";
        }
        if(!isset($if_exits['share_permissions'])){
            $param['share_permissions']=array();
        }

        if(!isset($if_exits['wechat_publicnumber_name'])){
            $param['wechat_publicnumber_name']="";
        }
        if(!isset($if_exits['wechat_appid'])){
            $param['wechat_appid']="";
        }
        if(!isset($if_exits['wechat_secret'])){
            $param['wechat_secret']="";
        }

        if(empty($if_exits)){

            if(!isset($param['project_domain_name'])){
                $param['project_domain_name']="";
            }
            if(!isset($param['website_name'])){
                $param['website_name']="";
            }
            if(!isset($param['project_password'])){
                $param['project_password']="";
            }
            if(!isset($param['search_keywords'])){
                $param['search_keywords']="";
            }

            $param['remark']="暂无";
            $param['state']="1";
            $param['status']="1";
            $param['sort']="1";
            $param['create_time']=fun_date(1);
            $add=Db::table("project_config")->insertGetId($param);
            if($add){
                return_json(200200,"项目域名配置设置成功",array());
            }else{
                return_json(200204,"项目域名配置设置失败",array());
            }
        }else{
            if(!isset($param)||empty($param)){
                return_json(200101,"要修改的字段未传",array());
            }
            $edit=Db::table("project_config")->where([['app_id','=',$param['app_id']]])->update($param);
            if($edit!==false){
                return_json(200200,"项目域名配置设置成功",array());
            }else{
                return_json(200204,"项目域名设置失败",array());
            }


        }

    }
    /**
     * 项目配置-共享配置
     */
    function configSharedEdit(){
        header("Access-Control-Allow-Origin: *");
        $param=Request::instance()->only("app_id,share_permissions");
        $validate = new validate\Projectconfig;
        if (!$validate->scene('configSharedEdit')->check($param)) {
            return_json(200101,$validate->getError(),array());
        }
        $if_exits=Db::table("project_config")->where([['app_id','=',$param['app_id']],['state','=','1'],['status','=','1']])->find();


        if(!isset($if_exits['db_name'])){
            $param['db_name']="";
        }
        if(!isset($if_exits['registry_set'])){
            $param['registry_set']="";
        }
		if(!isset($param['registered_fields'])){
			$param['registered_fields']=array();
		}
        if(!isset($if_exits['user_default_headimg'])){
            $param['user_default_headimg']="";
        }
        if(!isset($if_exits['cloud_account'])){
            $param['cloud_account']="";
        }
        if(!isset($if_exits['cloud_token'])){
            $param['cloud_token']="";
        }
        if(!isset($if_exits['cloud_appid'])){
            $param['cloud_appid']="";
        }
        if(!isset($if_exits['code_minimum_interval'])){
            $param['code_minimum_interval']="";
        }
        if(!isset($if_exits['code_savetime_interval'])){
            $param['code_savetime_interval']="";
        }

        if(!isset($if_exits['project_domain_name'])){
            $param['project_domain_name']="";
        }
        if(!isset($if_exits['website_name'])){
            $param['website_name']="";
        }
        if(!isset($if_exits['project_password'])){
            $param['project_password']="";
        }
        if(!isset($if_exits['search_keywords'])){
            $param['search_keywords']="";
        }

        if(!isset($if_exits['wechat_publicnumber_name'])){
            $param['wechat_publicnumber_name']="";
        }
        if(!isset($if_exits['wechat_appid'])){
            $param['wechat_appid']="";
        }
        if(!isset($if_exits['wechat_secret'])){
            $param['wechat_secret']="";
        }

        if(!isset($param['share_permissions'])){
            return_json(200101,"权限设置未传",array());
        }

        $share_permissions_arr=explode("_",$param['share_permissions']);
        $sign=array();
        foreach($share_permissions_arr as $key=>$value){
            switch($value){
                case "1":
                    array_push($sign,"allow_view_other");
                    break;
                case "2":
                    array_push($sign,"allow_viewed");
                    break;
                case "":

                    break;
            }
        }
        if(empty($if_exits)){

            $param['share_permissions']=$sign;

            $param['remark']="暂无";
            $param['state']="1";
            $param['status']="1";
            $param['sort']="1";
            $param['create_time']=fun_date(1);
            $add=Db::table("project_config")->insertGetId($param);
            if($add){
                return_json(200200,"项目域名配置设置成功",array());
            }else{
                return_json(200204,"项目域名配置设置失败",array());
            }
        }else{
            $param['share_permissions']=$sign;
            $edit=Db::table("project_config")->where([['app_id','=',$param['app_id']]])->update($param);
            if($edit!==false){
                return_json(200200,"项目域名配置设置成功",array());
            }else{
                return_json(200204,"项目域名设置失败",array());
            }

        }
    }
    /**
     * 项目配置-公众号设置
     */
    function configWechatEdit(){
        header("Access-Control-Allow-Origin: *");
        $param=Request::instance()->only("app_id,wechat_publicnumber_name,wechat_appid,wechat_secret");
        $validate = new validate\Projectconfig;
        if (!$validate->scene('configWechatEdit')->check($param)) {
            return_json(200101,$validate->getError(),array());
        }
        $if_exits=Db::table("project_config")->where([['app_id','=',$param['app_id']],['state','=','1'],['status','=','1']])->find();

        if(!isset($if_exits['db_name'])){
            $param['db_name']="";
        }
        if(!isset($if_exits['registry_set'])){
            $param['registry_set']="";
        }
        if(!isset($if_exits['user_default_headimg'])){
            $param['user_default_headimg']="";
        }
		if(!isset($if_exits['registered_fields'])){
			$param['registered_fields']=array();
		}

        if(!isset($if_exits['cloud_account'])){
            $param['cloud_account']="";
        }
        if(!isset($if_exits['cloud_token'])){
            $param['cloud_token']="";
        }
        if(!isset($if_exits['cloud_appid'])){
            $param['cloud_appid']="";
        }
        if(!isset($if_exits['code_minimum_interval'])){
            $param['code_minimum_interval']="";
        }
        if(!isset($if_exits['code_savetime_interval'])){
            $param['code_savetime_interval']="";
        }

        if(!isset($if_exits['project_domain_name'])){
            $param['project_domain_name']="";
        }
        if(!isset($if_exits['website_name'])){
            $param['website_name']="";
        }
        if(!isset($if_exits['project_password'])){
            $param['project_password']="";
        }
        if(!isset($if_exits['search_keywords'])){
            $param['search_keywords']="";
        }
        if(!isset($if_exits['share_permissions'])){
            $param['share_permissions']=array();
        }


        if(empty($if_exits)){

            if(!isset($param['wechat_publicnumber_name'])){
                $param['wechat_publicnumber_name']="";
            }
            if(!isset($param['wechat_appid'])){
                $param['wechat_appid']="";
            }
            if(!isset($param['wechat_secret'])){
                $param['wechat_secret']="";
            }


            $param['remark']="暂无";
            $param['state']="1";
            $param['status']="1";
            $param['sort']="1";
            $param['create_time']=fun_date(1);
            $add=Db::table("project_config")->insertGetId($param);
            if($add){
                return_json(200200,"项目公众号配置设置成功",array());
            }else{
                return_json(200204,"项目公众号配置设置失败",array());
            }
        }else{
            if(!isset($param)||empty($param)){
                return_json(200101,"要修改的字段未传",array());
            }
            $edit=Db::table("project_config")->where([['app_id','=',$param['app_id']]])->update($param);
            if($edit!==false){
                return_json(200200,"项目公众号配置设置成功",array());
            }else{
                return_json(200204,"项目公众号设置失败",array());
            }

        }







    }


    /**
	 * 项目配置-用户加入项目(app_id)之前需要完善的用户信息
	 */
    function configUserApplyEdit(){
		header("Access-Control-Allow-Origin: *");
		$param=Request::instance()->only("app_id,registered_fields");
		$validate = new validate\Projectconfig;
		if (!$validate->scene('configUserApplyEdit')->check($param)) {
			return_json(200101,$validate->getError(),array());
		}
		$fields_arr=explode(",",$param['registered_fields']);
		$param['registered_fields']=$fields_arr;

		$if_exits=Db::table("project_config")->where([['app_id','=',$param['app_id']],['state','=','1'],['status','=','1']])->find();


		if(!isset($if_exits['db_name'])){
			$param['db_name']="";
		}
		if(!isset($if_exits['registry_set'])){
			$param['registry_set']="";
		}
		if(!isset($if_exits['user_default_headimg'])){
			$param['user_default_headimg']="";
		}

		if(!isset($if_exits['cloud_account'])){
			$param['cloud_account']="";
		}
		if(!isset($if_exits['cloud_token'])){
			$param['cloud_token']="";
		}
		if(!isset($if_exits['cloud_appid'])){
			$param['cloud_appid']="";
		}
		if(!isset($if_exits['code_minimum_interval'])){
			$param['code_minimum_interval']="";
		}
		if(!isset($if_exits['code_savetime_interval'])){
			$param['code_savetime_interval']="";
		}

		if(!isset($if_exits['project_domain_name'])){
			$param['project_domain_name']="";
		}
		if(!isset($if_exits['website_name'])){
			$param['website_name']="";
		}
		if(!isset($if_exits['project_password'])){
			$param['project_password']="";
		}
		if(!isset($if_exits['search_keywords'])){
			$param['search_keywords']="";
		}
		if(!isset($if_exits['share_permissions'])){
			$param['share_permissions']=array();
		}
		if(!isset($if_exits['wechat_publicnumber_name'])){
			$param['wechat_publicnumber_name']="";
		}
		if(!isset($if_exits['wechat_appid'])){
			$param['wechat_appid']="";
		}

		if(!isset($if_exits['wechat_secret'])){
			$param['wechat_secret']="";
		}

		if(empty($if_exits)){

			if(!isset($param['registered_fields'])||empty($param['registered_fields'])){
				$param['registered_fields']=array();
			}else{
				$registered_fields=json_decode(html_entity_decode($param['registered_fields']),true);
				if(empty($registered_fields)){
					return_json(200101,"完善字段传值格式错误(#001)",array());
				}else{
					foreach($registered_fields as $key=>$value){
						if(!isset($value['cn_name'])||empty($value['cn_name'])){
							return_json(200101,"完善字段传值格式错误(#002)",array());
						}
						if(!isset($value['field'])||empty($value['field'])){
							return_json(200101,"完善字段传值格式错误(#003)",array());
						}
					}
				}

				$param['registered_fields']=$registered_fields;
			}
			$param['remark']="暂无";
			$param['state']="1";
			$param['status']="1";
			$param['sort']="1";
			$param['create_time']=fun_date(1);
			$add=Db::table("project_config")->insertGetId($param);
			if($add){
				return_json(200200,"项目公众号配置设置成功",array());
			}else{
				return_json(200204,"项目公众号配置设置失败",array());
			}
		}else{
			if(!isset($param)||empty($param)){
				return_json(200101,"要修改的字段未传",array());
			}
			$edit=Db::table("project_config")->where([['app_id','=',$param['app_id']]])->update($param);
			if($edit!==false){
				return_json(200200,"加入相项目之前用户必须完善的用户信息配置设置成功",array());
			}else{
				return_json(200204,"加入相项目之前用户必须完善的用户信息设置失败",array());
			}
		}
	}


}
