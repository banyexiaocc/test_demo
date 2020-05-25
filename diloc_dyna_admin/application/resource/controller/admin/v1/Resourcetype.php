<?php
/**
 * Created by PhpStorm.
 * User: fu.hy
 * Date: 2020/2/24
 * Time: 15:03
 */
namespace app\resource\controller\admin\v1;
use think\Controller;
use think\facade\Request;//数据接收-静态代理
use think\Db;
use app\resource\validate;
use app\auth\controller\admin\v1\Authuser;

class Resourcetype extends Authuser
{

    //方法已经可用
    public function __construct(){
        parent::__construct();
    }

    /**
     * 资源分类创建
     */
    function resourceTypeCreate(){
        header("Access-Control-Allow-Origin: *");
        $param=Request::instance()->only("name,icon,set_content_sensor,sort,remark,pid,if_dictionary");
        $validate = new validate\Resourcetype;
        if (!$validate->scene('resourceTypeCreate')->check($param)) {
            return_json(200101,$validate->getError(),array());
        }
        if(!isset($param['if_dictionary'])||empty($param['if_dictionary'])){
            $param['if_dictionary']="2";//无字典
        }
        if(!isset($param['sort'])||empty($param['sort'])){
            $param['sort']="1";
        }
        if(!isset($param['pid'])||empty($param['pid'])){
            $param['pid']="0";
            $param['level']=1;
        }else{
            $up_level_info=Db::table("base_resource_type")->where([['id','=',$param['pid']],['state','=','1']])->field("path,if_dictionary")->find();

            if(empty($up_level_info)){
                return_json(200101,"上级资源分类不存在,请确认",array());
            }else{

                if($up_level_info['if_dictionary']=="1"){
                    return_json(200204,"指定上级资源分类已经是最后一级资源分类,不能继续添加下级分类",array());
                }

                $param['pid']=$up_level_info['id'];
                $level_arr=explode(",",$up_level_info['path']);
                array_pop($level_arr);
                array_shift($level_arr);
                $param['level']=count($level_arr)+1;
            }
        }

        if(!isset($param['remark'])||empty($param['remark'])){
            $param['remark']="暂无";
        }
        if(!isset($param['icon'])||empty($param['icon'])){
            $param['icon']="";
        }

        if(isset($param['set_content_sensor'])&&!empty($param['set_content_sensor'])){
            $set_content_sensor_arr=explode("_",$param['set_content_sensor']);
            $param['set_content_sensor']=$set_content_sensor_arr;
        }else{
            $param['set_content_sensor']=array();
        }
        $param['code']=create_uuid(time());
        $param['path']="";
        $param['state']="1";
        $param['status']="1";
        $param['create_time']=fun_date(1);

        $add=Db::table("base_resource_type")->insertGetId($param);
        if($add){
            if($param['pid']=="0"){
                Db::table("base_resource_type")->where([['id','=',$add]])->update(array("path"=>",".$add.","));
            }else{
                Db::table("base_resource_type")->where([['id','=',$add]])->update(array("path"=>$up_level_info['path'].$add.","));
            }
            return_json(200200,"资源分类信息创建成功",array("id"=>$add,"code"=>$param['code']));
        }else{
            return_json(200204,"资源分类信息创建失败",array());
        }
    }

    /**
     * 资源分类列表查询
     * 指定pid获取下级资源分类列表
     */
    function resourceTypeList(){
        header("Access-Control-Allow-Origin: *");
        $param=Request::instance()->only("if_dictionary,keywords,page,size,status,pid");
        $validate = new validate\Resourcetype;
        if (!$validate->scene('resourceTypeList')->check($param)) {
            return_json(200101,$validate->getError(),array());
        }
        if(!isset($param['pid'])||empty($param['pid'])){
            $where[]=['pid','=','0'];
        }else{
            $where[]=['pid','=',$param['pid']];
        }

        if(!isset($param['if_dictionary'])||empty($param['if_dictionary'])){
            $where[]=['if_dictionary','in','1,2'];
        }else{
            $where[]=['if_dictionary','in',$param['if_dictionary']];
        }

        if(!isset($param['status'])||empty($param['status'])){
            $where[]=['status','in','1,2,3'];
        }else{
            $where[]=['status','in',$param['status']];
        }

        if(isset($param['keywords'])&&!empty($param['keywords'])){
            $where[]=['name|remark','like',$param['keywords']];
        }

        $where[]=['state','=','1'];

        if(isset($param['size'])&&!empty($param['size'])){

            $size=$param['size'];
        }else{
            $size=10;
        }
        if(isset($param['page'])&&!empty($param['page'])){
            $page=$param['page'];
        }else{

            $page=1;
        }

        $list=Db::table("base_resource_type")->where($where)->field("name,icon,set_content_sensor,if_dictionary,level,remark,code,status")->limit(($page-1)*$size,$size)->order("sort",'asc')->order("create_time",'desc')->select();
        if(empty($list)){
            return_json(200204,"资源分类列表为空",array());
        }else{
            return_json(200200,"资源分类列表获取成功",$list);
        }
    }


    /**
     * 资源分类修改
     * 指定pid获取下级资源分类列表
     */
    function resourceTypeEdit(){
		header("Access-Control-Allow-Origin: *");
		$param=Request::instance()->only("aim_id,name,icon,set_content_sensor,sort,remark,if_dictionary,status");
		$validate = new validate\Resourcetype;
		if (!$validate->scene('resourceTypeEdit')->check($param)) {
			return_json(200101,$validate->getError(),array());
		}
		$data['name']=$param['name'];
		if(isset($param['icon'])){
			$data['icon']=$param['icon'];
		}
		if(isset($param['set_content_sensor'])){
			$set_content_sensor=explode("_",$param['set_content_sensor']);
			$data['set_content_sensor']=$set_content_sensor;
		}
		if(isset($param['set_content_sensor'])){
			$set_content_sensor=explode("_",$param['set_content_sensor']);
			$data['set_content_sensor']=$set_content_sensor;
		}
		if(isset($param['sort'])){
			$data['sort']=$param['sort'];
		}
		if(isset($param['remark'])){
			$data['remark']=$param['remark'];
		}
		if(isset($param['if_dictionary'])){
			if($param['if_dictionary']=="1"){
				//判断是否含有下级字段分类,如果有,就不能将此参数设置为1
				$down_level_resource_type=department($param['aim_id']);
				//dump($down_level_resource_type);
				if(!empty($down_level_resource_type)){
					return_json(200101,"当前资源分类不是最底层分类,不能设置字典信息",array());
				}
			}
			$data['if_dictionary']=$param['if_dictionary'];
		}
		if(isset($param['status'])){
			$data['status']=$param['status'];
		}
		if(!isset($data)){
			return_json(200101,"要修改的字段未传",array());
		}

		$edit=Db::table("base_resource_type")->where([['id','=',$param['aim_id']]])->update($data);
		if($edit!==false){
			return_json(200200,"分类信息修改成功",array());
		}else{
			return_json(200204,"分类信息修改失败",array());
		}
    }


    /**
     * 资源分类基本信息详情
     */
    function resourceTypeInfo(){
		header("Access-Control-Allow-Origin: *");
		$param=Request::instance()->only("aim_id");
		$validate = new validate\Resourcetype;
		if (!$validate->scene('resourceTypeInfo')->check($param)) {
			return_json(200101,$validate->getError(),array());
		}
		$where[]=['id','=',$param['aim_id']];
		$where[]=['state','=','1'];
		$info=Db::table("base_resource_type")->where($where)->field("name,icon,set_content_sensor,if_dictionary,remark,status,sort")->find();
		if(empty($info)){
			return_json(200204,"资源分类信息不存在",array());
		}else{

			return_json(200200,"资源分类信息获取成功",$info);
		}
    }

    /**
     * 资源分类删除(只管删除数据)
     * app|pc官网战术资源数据时,需要进系统表查看分类状态,看是否删除才能判断在前端是否展示
     */
    function resourceTypeDel(){
		header("Access-Control-Allow-Origin: *");
		$param=Request::instance()->only("aim_id");

		$validate = new validate\Resourcetype;
		if (!$validate->scene('resourceTypeInfo')->check($param)) {
			return_json(200101,$validate->getError(),array());
		}
		$where[]=['id','=',$param['aim_id']];
		//判断是否是最后一级数据

		$down_level_resource_type=department($param['aim_id']);
		if(!empty($down_level_resource_type)){
			return_json(200101,"存在下级分类,不能删除",array());
		}

		$del=Db::table("base_resource_type")->where($where)->update(array("state"=>'2'));
		if($del!==false){
			return_json(200200,"资源分类删除成功",array());
		}else{
			return_json(200204,"资源分类删除失败",array());
		}
    }

}