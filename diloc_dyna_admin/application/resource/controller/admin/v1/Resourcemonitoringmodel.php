<?php
	/**
	 * Created by PhpStorm.
	 * User: fu.hy
	 * Date: 2020/2/24
	 * Time: 15:03
	 * 资源模型和传感器模型的关系
	 */
	namespace app\resource\controller\admin\v1;
	use think\Controller;
	use think\facade\Request;//数据接收-静态代理
	use think\Db;
	use app\resource\validate;
	use app\auth\controller\admin\v1\Authuser;



	class Resourcemonitoringmodel extends Authuser
	{
		//方法已经可用
		public function __construct()
		{
			parent::__construct();
		}

		/**
		 *位置资源模型添加监控传感器模型信息
		 */
		function resourceTemplateSensorAdd(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("resource_template_code,sensor_code,sort");
			$validate = new validate\Resourcemonitoringmodel;
			if (!$validate->scene('resourceTemplateSensorAdd')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			if(!isset($param['sort'])||empty($param['sort'])){
				$param['sort']="1";
			}else{
				$param['sort']=$param['sort'];
			}
			$if_exits=Db::table("template_resource_sensor_relation")->where([['state','=','1'],['sttaus','=','1'],['resource_template_code','=',$param['resource_template_code']],['sensor_code','=',$param['sensor_code']]])->find();
			//dump($if_exits);
			if(!empty($if_exits)){
				return_json(200101,"当前资源模型和传感器模型关联数据已经存在,请确认",array());
			}
			$param['state']="1";
			$param['status']="1";
			$param['sort']="1";
			$param['create_time']=fun_date(1);

			$if_exits=Db::table("template_resource_sensor_relation")->insertGetId($param);
			if($if_exits){
				return_json(200200,"资源模型关联传感器模型操作成功",array());
			}else{
				return_json(200204,"资源模型关联传感器模型操作失败",array());
			}
		}

		/**
		 *指定资源模型,获取关联的传感器模型列表
		 */
		function resourceTemplateSensorList(){

			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("resource_template_code");
			$validate = new validate\Resourcemonitoringmodel;
			if (!$validate->scene('resourceTemplateSensorList')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}

			$where[]=['resource_template_code','=',$param['resource_template_code']];
			$where[]=['state','=','1'];
			$where[]=['status','=','1'];

			$list=Db::table("template_resource_sensor_relation")->where($where)->field("sensor_code,sort")->order("sort",'asc')->select();
			if(empty($list)){
				return_json(200204,"资源模型未关联传感器模型",array());
			}else{
				$sensor_code_list_str=implode(",",array_column($list,'sensor_code'));
				//dump($sensor_code_list_str);
				$where_sensor[]=['resource_template_code','in',$sensor_code_list_str];
				$where_sensor[]=['state','=','1'];
				$sensor_list=Db::table("template_resource_infoamtion")->where($where_sensor)->field("resource_template_code,name,serial_number")->select();
				//dump($sensor_list);
				//dump($list);
				if(empty($sensor_list)){
					return_json(200204,"资源模型关联的传感器模板信息为空",array());
				}else{
					foreach($sensor_list as $key=>$value){
						foreach($list as $key1=>$value1){
							if($value1['sensor_code']==$value['resource_template_code']){
								$sensor_list[$key]['sort']=$value1['sort'];
							}
						}
					}
					$sort = array_column($sensor_list, 'sort');
					//进行排序改变数组本身             数组
					array_multisort($sort, SORT_ASC, $sensor_list);
					return_json(200200,"资源模型关联的传感器模板列表",$sensor_list);
				}

			}


		}

		/**
		 * 资源模型和关联的传感器关系解除
		 */
		function resourceTemplateSensorDel(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("resource_template_code,sensor_code");
			$validate = new validate\Resourcemonitoringmodel;
			if (!$validate->scene('resourceTemplateSensorDel')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}

			$if_exits=Db::table("template_resource_sensor_relation")->where([['resource_template_code','=',$param['resource_template_code']],['state','=','1'],['sensor_code','=',$param['sensor_code']]])->find();
			if(empty($if_exits)){
				return_json(200101,"关系数据不存在,可能已经移除",array());
			}

			$del=Db::table("template_resource_sensor_relation")->where([['id','=',$if_exits['id']]])->update(array("state"=>"2"));
			if($del!==false){
				return_json(200200,"资源模型关联的传感器模型数据删除成功",array());
			}else{
				return_json(200204,"资源模型关联的传感器模型数据删除失败",array());
			}
		}

		/**
		 * 资源模型关联的传感器模型排序修改
		 */
		function resourceTemplateSensorEdit(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("aim_id,sort");
			$validate = new validate\Resourcemonitoringmodel;
			if (!$validate->scene('resourceTemplateSensorEdit')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}

			if(!isset($param['sort'])||empty($param['sort'])){
				return_json(200101,"排序值未传",array());
			}
			$if_exits=Db::table("template_resource_sensor_relation")->where([['id','=',$param['aim_id']],['state','=','1']])->find();
			if(empty($if_exits)){
				return_json(200101,"关联的传感器数据关系数据不存在,不可修改!",array());
			}
			$edit=Db::table("template_resource_sensor_relation")->where([['id','=',$param['aim_id']],['state','=','1']])->update(array("sort"=>$param['sort']));
			if($edit!==false){
				return_json(200200,"排序修改成功",array());
			}else{
				return_json(200204,"排序修改失败",array());
			}
		}

	}