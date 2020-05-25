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

class Project extends Authuser
{

    //方法已经可用
    public function __construct(){
        parent::__construct();
    }

    /**
     * 项目列表
     */
    function projectInfoList(){
        header("Access-Control-Allow-Origin: *");
        $param=Request::instance()->only("status,keywords,create_time,page,size");
        $validate = new validate\Project;
        if (!$validate->scene('projectInfoList')->check($param)) {
            return_json(200101,$validate->getError(),array());
        }
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

        if(isset($param['create_time'])&&!empty($param['create_time'])){
            $where[]=['create_time','between',[$param['create_time']." 00:00:00",$param['create_time']." 23:59:59"]];
        }

        if(isset($param['keywords'])&&!empty($param['keywords'])){
            $where[]=['name,sys_number,company_name,head_person,connect_person','like',$param['keywords']];
        }
        if(!isset($param['status'])||empty($param['status'])){
            $where[]=['status','in','1,2,3,4'];
        }else{
            $where[]=['status','in',$param['status']];
        }
        $where[]=['state','=','1'];


        $list=Db::table("project")->where($where)->order("sort",'asc')->order("create_time","desc")->field("if_ayna,name,sys_number,head_person,head_person_mobile,due_time,contract_amount,status")->limit(($page-1)*$size,$size)->select();
        if(!empty($list)){
            return_json(200200,"项目列表获取成功",$list);
        }else{
            return_json(200204,"项目列表为空",$list);
        }

    }

    /**
     * 所属项目行业信息列表(下拉选择)
     */
    function industryChooseList(){
        header("Access-Control-Allow-Origin: *");
        $where=[['state','=','1'],['status','=','1']];
        $list=Db::table("industry_info")->where($where)->field("code,name,icon")->order("sort",'asc')->select();
        if(empty($list)){
            return_json(200204,"列表为空,请添加",array());
        }else{

            return_json(200200,"行业列表获取成功",$list);
        }
    }

    /**
     *创建新项目
     */
    function projectInfoCreate(){
        header("Access-Control-Allow-Origin: *");
        $param=Request::instance()->only("name,logo,pc_logo,industry_type_id,head_person,head_person_mobile,due_time,contract_amount,registration_code,company_name,company_addr,company_phone,company_fax,company_email,connect_person,connect_person_mobile,sort");
        $validate = new validate\Project;
        if (!$validate->scene('projectInfoCreate')->check($param)) {
            return_json(200101,$validate->getError(),array());
        }
        $industry_type_info=Db::table("industry_info")->where([['id','=',$param['industry_type_id']]])->field("name")->find();
        if(empty($industry_type_info)){
            return_json(200101,"选择的行业不存在,请重新选择",array());
        }
        $param['industry_type_name']=$industry_type_info['name'];
        $param['if_ayna']="2";
        $param['contract_amount']=(double)$param['contract_amount'];
        $param['create_time']=fun_date(1);
        $param['state']="1";
        $param['status']="1";
        $param['sys_number']=create_theonly_num("P");


		$compnay_name_only=Db::table("project")->where([['company_name','=',$param['company_name']],['state','=','1'],['status','in','1,2,3,4,5']])->find();
		if(!empty($compnay_name_only)){
			return_json(20020,"项目注册使用的企业名称已经存在,不能重复添加",array());
		}

        $if_exits=Db::table("project")->where([['state','=','1'],['name','=',$param['name']]])->find();
        if(!empty($if_exits)){
            return_json(200101,"项目名称被占用",array());
        }else{
            $add=Db::table("project")->insertGetId($param);
            if($add){
                return_json(200200,"项目创建信息成功",array("new_appid"=>$add,"sys_number"=>$param['sys_number']));
            }else{
                return_json(200204,"项目创建信息失败",array());
            }
        }
    }

    /**
     * 项目信息详情获取
     */
    function projectInfoMess(){
        header("Access-Control-Allow-Origin: *");
        $param=Request::instance()->only("pro_id");
        $validate = new validate\Project;
        if (!$validate->scene('projectInfoMess')->check($param)) {
            return_json(200101,$validate->getError(),array());
        }
        $where=[['id','=',$param['pro_id']],['state','=','1']];
        $info=Db::table("project")->where($where)->field("name,logo,pc_logo,industry_type_id,industry_type_name,head_person,head_person_mobile,due_time,contract_amount,registration_code,company_name,company_addr,company_phone,company_fax,company_email,connect_person,connect_person_mobile,sort,status")->find();
        if(empty($info)){
            return_json(200204,"项目信息不存在,请确认",array());
        }else{
            return_json(200200,"项目信息获取成功",$info);
        }
    }

    /**
     * 项目信息修改
     */
    function projectInfoEdit(){
        header("Access-Control-Allow-Origin: *");
        $param=Request::instance()->only("pro_id,name,logo,pc_logo,industry_type_id,head_person,head_person_mobile,due_time,contract_amount,registration_code,company_name,company_addr,company_phone,company_fax,company_email,connect_person,connect_person_mobile,sort,status,state");
        $validate = new validate\Project;
        if (!$validate->scene('projectInfoCreate')->check($param)) {
            return_json(200101,$validate->getError(),array());
        }
        if(!isset($param['pro_id'])||empty($param['pro_id'])){
            return_json(200101,"要修改信息的项目编号未传",array());
        }
        $project_id=$param['pro_id'];
        unset($param['pro_id']);
        if(isset($param['status'])&&!empty($param['status'])){
            $param['status']=$param['status'];
        }
        if(isset($param['state'])&&!empty($param['state'])){
            $param['state']=$param['state'];
        }
        if(isset($param['name'])&&!empty($param['name'])){
            $if_exits=Db::table("project")->where([['state','=','1'],['name','=',$param['name']]])->find();
            if(!empty($if_exits)&&$if_exits['id']!=$project_id){
                return_json(200101,"项目名称被占用",array());
            }
        }
        $edit=Db::table("project")->where([['id','=',$project_id]])->update($param);
        if($edit!==false){
            return_json(200200,"修改成功",array());
        }else{
            return_json(200204,"修改失败",array());
        }
    }

}