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
	use think\facade\Validate;
	use app\auth\controller\admin\v1\Authuser;



	class Resourcemodel extends Authuser
	{
		//方法已经可用
		public function __construct()
		{
			parent::__construct();
		}


		/**
		 * 资源模型创建描述性字段表单获取展示
		 * 资源模板数据添加/修改/详情;实体资源信息添加/修改/详情  都可用本接口显示表单
		 */
		function showForm(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("resource_type_code");

			if(!isset($param['resource_type_code'])||empty($param['resource_type_code'])){
				return_json(200101,"未制定资源分类唯一编码",array());
			}
			$where[]=['resource_type_code','=',$param['resource_type_code']];
			$where[]=['state','=','1'];
			$where[]=['status','=','1'];

			$list=Db::table("base_resource_field_dictionary")->where($where)->field("field,cn_name,data_source,level,data_deal_type,getdata_api,field_group_code,field_group_id,data_processing_fun,sort")->order("sort",'asc')->select();
			if(empty($list)){
				return_json(200200,"指定资源分类描述性字段未设置,请确认",array());
			}
			//dump($list);

			foreach($list as $key=>$value){
				//表单选项获取
				$list[$key]['option_list']=array();
				if($value['data_deal_type']=="radio"||$value['data_deal_type']=="checkbox"||$value['data_deal_type']=="select"){
					if($value['getdata_api']==""){
						return_json(200204,"表单获取失败(字典中,辅助接口code未设置)");
					}else{
						$auxiliary_url_id=Db::table("field_dictionary_auxiliary_url")->where([['code','=',$value['getdata_api']],['state','=','1'],['status','=','1']])->field("id")->find();
						if(empty($auxiliary_url_id)){
							return_json(200204,"字段字典中,辅助接口code指向的集合不存在,请重新设置字段字典信息",array());
						}else{
							$auxiliary_opton_list=Db::table("field_dictionary_auxiliary_url")->where([['pid','=',$auxiliary_url_id['id']],['state','=','1'],['status','=','1']])->field("name,id")->order("sort",'asc')->select();
						}
						$list[$key]['option_list']=$auxiliary_opton_list;
					}
				}
				//表单字段默认系统生成数据
				$list[$key]['default_val']="";
				if($value['data_source']=="3"){
					if($value['data_processing_fun']==""){
						return_json(200204,"字段数据为系统分配,字段字典没有设置数据处理函数,请修改字段字典信息",array());
					}else{
						$list[$key]['default_val']=$value['data_processing_fun'](1);
					}
				}
			}
			//dump($list);
			$types = array_unique(array_column($list,'field_group_code'));
			$list2 = [];
			foreach($types as $k => $v)
			{
				$list2[$v] = array();
			}
			foreach($list as $k => $v)
			{
				$list2[$v['field_group_code']]['field_group_code']=$v['field_group_code'];
				$list2[$v['field_group_code']]['field_group_id']=$v['field_group_id'];
				$list2[$v['field_group_code']]['field_list'][]=$v;
			}
			//dump($list2);
			//分组名称获取
			foreach($list2 as $key=>$value){
				$field_group_name=Db::table("base_resource_field_group")->where([['id','=',$value['field_group_id']]])->field("name,sort")->find();
				if(empty($field_group_name)){
					$list2[$key]['field_group_name']="";
					$list2[$key]['field_group_sort']="";
				}else{
					$list2[$key]['field_group_name']=$field_group_name['name'];
					$list2[$key]['field_group_sort']=$field_group_name['sort'];
				}
			}
			$list2=array_values($list2);
			$sort = array_column($list2, 'field_group_sort');
			//进行排序改变数组本身             数组
			array_multisort($sort, SORT_ASC, $list2);
			return_json(200200,"分组字典表单展示数据获取成功",$list2);

		}


		/**
		 * 指定资源分类,创建实体资源模型数据
		 * 过滤规则的等级高比data_source执行等级高
		 */
		function resourceTemplateCreate(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();
		//所属资源分类唯一编码(含字典的那一级)
			if(!isset($param['resource_type_code'])||empty($param['resource_type_code'])){
				return_json(200101,"所属资源分类唯一编码未传",array());
			}

			$resource_last_typeinfo=Db::table("base_resource_type")->where([['code','=',$param['resource_type_code']],['state','=','1']])->field("id")->find();
				if(empty($resource_last_typeinfo)){
				return_json(200101,"资源分类数据不存在,请确认",array());
			}
			$uplevel_type=uplevel_department($resource_last_typeinfo['id']);
			//dump($uplevel_type);
			$data['resource_template_code']=create_uuid(time());
			$data['resource_first_type_id']=$uplevel_type[count($uplevel_type)-1];
			$data['resource_first_type_code']=Db::table("base_resource_type")->where([['id','=',$data['resource_first_type_id']]])->field("code")->find()['code'];
			$data['resource_last_type_id']=$resource_last_typeinfo['id'];
			$data['resource_last_type_code']=$param['resource_type_code'];

			if(!isset($param['serial_number'])||empty($param['serial_number'])){
				$data['serial_number']="暂无";
			}else{
				$data['serial_number']=$param['serial_number'];
			}
			if(!isset($param['name'])||empty($param['name'])){
				return_json(200101,"名称未传",array());
			}
			$data['name']=$param['name'];

			if(!isset($param['image'])||empty($param['image'])){
				$data['image']="";
			}else{
				$data['image']=$param['image'];
			}

			if(!isset($param['details_img'])||empty($param['details_img'])){
				$data['details_img']="";
			}else{
				$data['details_img']=$param['details_img'];
			}

			//实验服务专用字段
			if(!isset($param['related_introduction'])||empty($param['related_introduction'])){
				$data['related_introduction']="暂无";
			}else{
				$data['related_introduction']=$param['related_introduction'];
			}

			if(!isset($param['sort'])||empty($param['sort'])){
				$data['sort']="1";
			}else{
				$data['sort']=$param['sort'];
			}
			//---------------------------------以上程序必传字段------以下字典字段(包含过滤)-------
			$dict_fields_arr=Db::table("base_resource_field_dictionary")->where([['state','=','1'],['status','=','1'],['resource_type_code','=',$param['resource_type_code']]])->field("field,data_source,cn_name,filtering_rules")->select();
			if(empty($dict_fields_arr)){
				return_json(200101,"指定资源分类下未设置字段字典信息",array());
			}

			//dump($dict_fields_arr);
			foreach($dict_fields_arr as $key=>$value){
				switch($value['data_source']){
					case '1'://必传字段
						if(!isset($param[$value['field']])||empty($param[$value['field']])){
						return_json(200101,"[数据来源]必传字段,".$value['cn_name'].":不得为空!",array());
					}
						break;
					case '2'://选填字段
						if(!isset($param[$value['field']])){
							$param[$value['field']]="";
						}
						break;
					case '3':
						if(!isset($param[$value['field']])||empty($param[$value['field']])){
							return_json(200101,"[数据来源]系统分配值字段,".$value['cn_name'].":传值未接收到!",array());
						}
						break;
				}
				$data[$value['field']]=$param[$value['field']];

				//过滤-----------
				//规则
				if(!empty($value['filtering_rules'])){
					$rule = array($value['field']=>$value['filtering_rules']);
					//验证
					$validate   = Validate::make($rule);
					$result = $validate->batch()->check($param);
					if(!$result) {
						//dump($validate->getError());
						$error_msg_arr=$validate->getError();
						$error_msg_arr_str=str_replace($value['field'],"",$error_msg_arr[$value['field']]);
						return_json(200101,"[内置规则]".$value['cn_name'].":".$error_msg_arr_str,array());
					}
				}
			}
			$data['status']="1";
			$data['state']="1";
			$data['sort']="1";
			$data['create_time']=fun_date(1);
			//dump($data);
			$add=Db::table("template_resource_infoamtion")->insertGetId($data);
			if($add){
				return_json(200200,"资源模型(基本信息)创建成功",array("new_id"=>$add,"code"=>$data['resource_template_code']),array());
			}else{
				return_json(200204,"资源模型(基本信息)创建失败",array("new_id"=>"","code"=>""),array());
			}

		}

		/**
		 * 指定资源分类唯一编码,获取资源模型列表
		 */
		function resourceTemplateList(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();
			//所属资源分类唯一编码(含字典的那一级)
			if(!isset($param['resource_type_code'])||empty($param['resource_type_code'])){
				return_json(200101,"所属资源分类唯一编码未传",array());
			}

			$where[]=['resource_last_type_code','=',$param['resource_type_code']];

			$if_exits=Db::table("base_resource_type")->where([['code','=',$param['resource_type_code']],['state','=','1']])->find();
			if(empty($if_exits)){
				return_json(200101,"指定的资源分类信息不存在,请确认",array());
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

			if(!isset($param['status'])||empty($param['status'])){
				$where[]=['status','in','1,2,3'];
			}else{
				$where[]=['status','in',$param['status']];
			}

			if(isset($param['keywords'])&&!empty($param['keywords'])){
				$where[]=['name','like',$param['keywords']];
			}

			$where[]=['state','=','1'];

			$list=Db::table("template_resource_infoamtion")->where($where)->field("name,resource_template_code,status,create_time,serial_number")->order("sort",'asc')->limit(($page-1)*$size,$size)->select();
			if(empty($list)){
				return_json(200204,"资源信息模板列表为空",array());
			}else{
				return_json(200200,"资源信息模板列表获取成功",$list);
			}
		}

		/**
		 *资源模型(描述字段)详情
		 */
		function resourceTemplateInfo(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();

			if(isset($param['aim_id'])&&!empty($param['aim_id'])){
				$where[]=['id','=',$param['aim_id']];
			}
			if(isset($param['resource_template_code'])&&!empty($param['resource_template_code'])){
				$where[]=['resource_template_code','=',$param['resource_template_code']];
			}


			if(!isset($where)){
				return_json(200101,"查询条件未传",array());
			}
			if(isset($param['resource_type_code'])&&!empty($param['resource_type_code'])){
				$where[]=['resource_last_type_code','=',$param['resource_type_code']];
			}else{
				return_json(200101,"所属资源分类未传",array());
			}
			$where[]=['state','=','1'];

			$resource_last_typeinfo=Db::table("base_resource_type")->where([['code','=',$param['resource_type_code']],['state','=','1']])->field("id")->find();
			if(empty($resource_last_typeinfo)){
				return_json(200101,"资源分类数据不存在,请确认",array());
			}

			$dict_fields_arr=Db::table("base_resource_field_dictionary")->where([['state','=','1'],['status','=','1'],['resource_type_code','=',$param['resource_type_code']]])->field("field,data_source,cn_name,filtering_rules")->select();
			if(!empty($dict_fields_arr)){
				$fields=array_column($dict_fields_arr,'field');
				$fields_str=",".implode(",",$fields);
			}else{
				$fields_str="";
			}
			//dump($where);
			$info=Db::table("template_resource_infoamtion")->where($where)->field("name,resource_template_code,serial_number,image,details_img,related_introduction,status".$fields_str)->find();
			//dump($info);
			if(empty($info)){
				return_json(200204,"资源模型信息获取失败",array());
			}else{
				return_json(200200,"资源模型数据获取成功",$info);
			}
		}

		/**
		 * 修改资源模板数据
		 */
		function resourceTemplateEdit(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();


			if(isset($param['aim_id'])&&!empty($param['aim_id'])){
				$where[]=['id','=',$param['aim_id']];
			}
			if(isset($param['resource_template_code'])&&!empty($param['resource_template_code'])){
				$where[]=['resource_template_code','=',$param['resource_template_code']];
			}
			if(!isset($where)){
				return_json(200101,"查询条件未传",array());
			}
			if(isset($param['resource_template_code'])){
				unset($param['resource_template_code']);
			}
			if(isset($param['aim_id'])){
				unset($param['aim_id']);
			}
			unset($param['module'],$param['version'],$param['access_token']);
			$where[]=['state','=','1'];
			$where[]=['status','in','1,2,3'];
			$if_exits=Db::table("template_resource_infoamtion")->where($where)->field("serial_number,name,image,details_img,related_introduction,resource_last_type_code,sort")->find();
			if(empty($if_exits)){
				return_json(200101,"查询数据不存在,请确认查询条件",array());
			}

			$aim_resource_last_type_code=$if_exits['resource_last_type_code'];
			unset($if_exits['id'],$if_exits['resource_last_type_code']);
			//dump($if_exits);
			$fields_fixed_arr=array_keys($if_exits);

			//查询字典信息
			$dict_fields_arr=Db::table("base_resource_field_dictionary")->where([['state','=','1'],['status','=','1'],['resource_type_code','=',$aim_resource_last_type_code],['data_source','in','1,2']])->field("field,data_source,cn_name,filtering_rules")->select();

			if(!empty($dict_fields_arr)){
				$fields=array_column($dict_fields_arr,'field');
			}else{
				return_json(200101,"模型使用的字典信息获取失败,可能不存在,请确认",array());
			}

			foreach($dict_fields_arr as $key=>$value){
				$dict_fields_arr[$value['field']]=$value;
				unset($dict_fields_arr[$key]);
			}
			//dump($dict_fields_arr);

			$fields_listarr=array_merge($fields,$fields_fixed_arr);//数组合并
			//dump($fields_listarr);
			$update_fields_arr=array();
			foreach($fields_listarr as $key=>$value){
				if(isset($param[$value])){
					$update_fields_arr[$value]=$param[$value];
				}
			}
			if(empty($update_fields_arr)){
				return_json(200101,"要修改的字段未传,请确认",array());
			}
			//dump($update_fields_arr);

			//字典规则过滤
			foreach($update_fields_arr as $key=>$value){

				//数据来源过滤
				if(isset($dict_fields_arr[$key])){
					switch($dict_fields_arr[$key]['data_source']){
						case '1'://必传
							if($update_fields_arr[$key]==""){
								return_json(200101,"[数据来源]必传字段,".$dict_fields_arr[$key]['cn_name'].":不得为空!",array());
							}
							break;

					}
				}

				//内置规则过滤
				if(!empty($dict_fields_arr[$key]['filtering_rules'])){
					$rule = array($key=>$dict_fields_arr[$key]['filtering_rules']);
					//验证
					$validate   = Validate::make($rule);
					$result = $validate->batch()->check($update_fields_arr);
					if(!$result) {
						//dump($validate->getError());
						$error_msg_arr=$validate->getError();
						$error_msg_arr_str=str_replace($key,"",$error_msg_arr[$key]);
						return_json(200101,"[内置规则]".$dict_fields_arr[$key]['cn_name'].":".$error_msg_arr_str,array());
					}
				}
			}

			//dump($update_fields_arr);

			//数据修改
			$edit=Db::table("template_resource_infoamtion")->where($where)->update($update_fields_arr);
			if($edit!==false){
				return_json(200200,"模型数据修改成功",array());
			}else{
				return_json(200204,"模型数据修改失败",array());
			}
		}

		/**
		 * 资源模板启用禁用操作
		 */
		function resourceTemplateIfused(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();

			if(isset($param['aim_id'])&&!empty($param['aim_id'])){
				$where[]=['id','=',$param['aim_id']];
			}
			if(isset($param['resource_template_code'])&&!empty($param['resource_template_code'])){
				$where[]=['resource_template_code','=',$param['resource_template_code']];
			}
			if(!isset($where)){
				return_json(200101,"查询条件未传",array());
			}

			if(!isset($param['status'])||empty($param['status'])){
				return_json(200101,"状态值未传",array());
			}

			switch($param['status']){
				case '1'://施工中
					$data['status']="1";
					break;
				case '2'://正常
					$data['status']="2";
					break;
				case '3'://禁用
					$data['status']="3";
					break;

				default:
					return_json(200101,"请确认状态值",array());
			}

			//数据修改
			$edit=Db::table("template_resource_infoamtion")->where($where)->update($data);
			if($edit!==false){
				return_json(200200,"模型状态修改成功",array());
			}else{
				return_json(200204,"模型状态修改失败",array());
			}

		}

		/**
		 * 资源模型数据删除
		 * 模型删除不影响已经创建的实体资源,不会删除关联的手册模板和参数模板等信息
		 */
		function resourceTemplateDel(){


			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();

			if(isset($param['aim_id'])&&!empty($param['aim_id'])){
				$where[]=['id','=',$param['aim_id']];
			}
			if(isset($param['resource_template_code'])&&!empty($param['resource_template_code'])){
				$where[]=['resource_template_code','=',$param['resource_template_code']];
			}
			if(!isset($where)){
				return_json(200101,"查询条件未传",array());
			}
			$data['state']="2";

			$del=Db::table("template_resource_infoamtion")->where($where)->update($data);
			if($del!==false){
				return_json(200200,"资源模型删除成功",array());
			}else{
				return_json(200204,"资源模型删除失败",array());
			}
		}


		/**
		 *资源模板关联的操作手册模板信息添加
		 */
		function resourceTemplateManualCreate(){

			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();

			if(!isset($param['resource_template_code'])||empty($param['resource_template_code'])){
				return_json(200101,"所属资源模型唯一编码未传",array());
			}
			$if_exits=Db::table("template_resource_infoamtion")->where([['resource_template_code','=',$param['resource_template_code']],['state','=','1']])->field("id")->find();
			if(empty($if_exits)){
				return_json(200101,"资源模型唯一编码指向的资源模型不存在,请确认",array());
			}
			$data['resource_template_code']=$param['resource_template_code'];

			if(!isset($param['name'])||empty($param['name'])){
				return_json(200101,"手册名称未传",array());
			}else{
				$data['name']=$param['name'];
			}
			if(!isset($param['url'])||empty($param['url'])){
				return_json(200101,"手册保存路径未传",array());
			}else{
				$data['url']=$param['url'];
			}
			if(!isset($param['sort'])||empty($param['sort'])){
				$data['sort']="1";
			}else{
				$data['sort']=$param['sort'];
			}

			$if_exits_manual=Db::table("template_resource_operation_manual")->where([['resource_template_code','=',$param['resource_template_code']],['state','=','1'],['name','=',$data['name']]])->find();
			if(!empty($if_exits_manual)){
				return_json(200101,"手册名称已存在,请确认",array());
			}

			$data['code']=create_uuid(time());
			$data['state']="1";
			$data['status']="1";
			$data['create_time']=fun_date(1);

			$add=Db::table("template_resource_operation_manual")->insertGetId($data);
			if($add){
				return_json(200200,"模型资源手册信息添加成功",array('new_id'=>$add));
			}else{
				return_json(200204,"模型资源手册信息添加失败",array('new_id'=>""));
			}
		}

		/**
		 * 资源模型手册信息修改
		 */
		function resourceTemplateManualEdit(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();

			if(isset($param['aim_id'])&&!empty($param['aim_id'])){
				$where[]=['id','=',$param['aim_id']];
			}
			if(isset($param['code'])&&!empty($param['code'])){
				$where[]=['code','=',$param['code']];
			}
			if(!isset($where)){
				return_json(200101,"查询条件未传",array());
			}

			$where[]=['state','=','1'];
			$where[]=['status','=','1'];


			if(isset($param['name'])&&!empty($param['name'])){
				$data['name']=$param['name'];
			}
			if(isset($param['url'])&&!empty($param['url'])){
				$data['url']=$param['url'];
			}
			if(isset($param['sort'])&&!empty($param['sort'])){
				$data['sort']=$param['sort'];
			}
			if(!isset($data)){
				return_json(200101,"要修改的字段未传",array());
			}

			$edit=Db::table("template_resource_operation_manual")->where([['id','=',$param['aim_id']],['state','=','1']])->update($data);
			if($edit!==false){
				return_json(200200,"资源模型手册数据修改成功",array());
			}else{
				return_json(200200,"资源模型手册数据修改失败",array());
			}
		}


		/**
		 * 资源模型手册信息列表
		 */
		function resourceTemplateManualList(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();

			//所属资源分类唯一编码(含字典的那一级)
			if(!isset($param['resource_template_code'])||empty($param['resource_template_code'])){
				return_json(200101,"所属资源模型数据唯一编码",array());
			}
			$where[]=['resource_template_code','=',$param['resource_template_code']];

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

			if(isset($param['keywords'])&&!empty($param['keywords'])){
				$where[]=['name','like',$param['keywords']];
			}

			$where[]=['state','=','1'];
			$where[]=['status','=','1'];
			$list=Db::table("template_resource_operation_manual")->where($where)->field("name,url,sort,code,create_time")->order("sort",'asc')->limit(($page-1)*$size,$size)->select();
			if(empty($list)){
				return_json(200204,"资源模型手册列表为空",array());
			}else{
				return_json(200200,"资源模型手册列表获取成功",$list);
			}

		}

		/**
		 * 资源模型手册模型信息详情
		 */
		function resourceTemplateManualInfo(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();

			if(isset($param['aim_id'])&&!empty($param['aim_id'])){
				$where[]=['id','=',$param['aim_id']];
			}
			if(isset($param['code'])&&!empty($param['code'])){
				$where[]=['code','=',$param['code']];
			}
			if(!isset($where)){
				return_json(200101,"查询条件未传",array());
			}

			$where[]=['state','=','1'];
			$where[]=['status','=','1'];

			$info=Db::table("template_resource_operation_manual")->where($where)->find();
			if(!empty($info)){
				return_json(200200,"手册模型详情获取成功",$info);
			}else{
				return_json(200204,"手册模型详情获取失败",array());
			}
		}

		/**
		 * 资源模型手册模型信息删除
		 */
		function resourceTemplateManualDel(){

			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();

			if(isset($param['aim_id'])&&!empty($param['aim_id'])){
				$where[]=['id','=',$param['aim_id']];
			}
			if(isset($param['code'])&&!empty($param['code'])){
				$where[]=['code','=',$param['code']];
			}
			if(!isset($where)){
				return_json(200101,"查询条件未传",array());
			}


			$data['state']="2";

			$del=Db::table("template_resource_operation_manual")->where($where)->update($data);
			if($del!==false){
				return_json(200200,"资源模型手册模型数据删除成功",array());
			}else{
				return_json(200204,"资源模型手册模型数据删除失败",array());
			}


		}

	}