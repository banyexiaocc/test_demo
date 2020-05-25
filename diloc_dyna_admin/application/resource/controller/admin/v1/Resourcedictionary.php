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


	class Resourcedictionary extends Authuser
	{

		//方法已经可用
		public function __construct()
		{
			parent::__construct();
		}


		/**
		 * 字段分组创建
		 */
		function fieldGroupCreate(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("resource_type_id,name,sort");
			$validate = new validate\Resourcedictionary;
			if (!$validate->scene('fieldGroupCreate')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			//判断是否最下级
			$down_level_type=department($param['resource_type_id']);
			if(!empty($down_level_type)){
				return_json(200101,"资源分类不是最后一级分类数据,不能创建字典有关的数据",array());
			}

			$resource_type_info=Db::table("base_resource_type")->where([['id','=',$param['resource_type_id']],['state','=','1']])->find();
			if(empty($resource_type_info)){
				return_json(200101,"资源分类信息不存在",array());
			}
			$param['resource_type_code']=$resource_type_info['code'];
			if(!isset($param['name'])||empty($param['name'])){
				$param['name']="";
			}
			$param['code']=create_uuid(time());
			if(!isset($param['sort'])||empty($param['sort'])){
				$param['sort']="1";
			}
			$param['state']="1";
			$param['status']="1";
			$param['create_time']=fun_date(1);

			//dump($param);

			$add=Db::table("base_resource_field_group")->insertGetId($param);
			if($add){
				return_json(200200,"字段分组创建成功",array("new_id"=>$add,"code"=>$param['code']));
			}else{
				return_json(200204,"字段分组创建失败,请重试",array("new_id"=>"","code"=>""));
			}
		}

		/**
		 * (指定资源分类数据编号)字段分组列表
		 */
		function fieldGroupList(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("resource_type_id");
			$validate = new validate\Resourcedictionary;
			if (!$validate->scene('fieldGroupList')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}

			$where[]=['resource_type_id','=',$param['resource_type_id']];
			$where[]=['state','=','1'];

			$list=Db::table("base_resource_field_group")->where($where)->field("resource_type_id,name,code,sort,status,create_time")->order("sort",'asc')->select();

			if(!empty($list)){

				$resource_type_list_str=implode(",",array_column($list,'resource_type_id'));
				$where_type[]=['id','in',$resource_type_list_str];

				$resource_type_list=Db::table("base_resource_type")->where($where_type)->field("name")->select();

				//数组合并
				foreach($list as $key=>$value){
					foreach($resource_type_list as $key1=>$value1){
						if($value1['id']==$value['resource_type_id']){
							$list[$key]['resource_type_name']=$value1['name'];
						}
					}
				}
				return_json(200200,"字段分组列表获取成功!",$list);
			}else{
				return_json(200204,"字段分组列表为空!",array());
			}
		}

		/**
		 * 字段分组信息修改
		 */
		function fieldGroupEdit(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("aim_id,name,sort");
			$validate = new validate\Resourcedictionary;
			if (!$validate->scene('fieldGroupEdit')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			if(isset($param['name'])){
				$data['name']=$param['name'];
			}
			if(isset($param['sort'])){
				$data['sort']=$param['sort'];
			}
			$where[]=['id','=',$param['aim_id']];
			$where[]=['state','=','1'];
			$where[]=['status','=','1'];
			if(!isset($data)){
				return_json(200101,"要修改的字段未传",array());
			}
			$edit=Db::table("base_resource_field_group")->where($where)->update($data);
			if($edit!==false){
				return_json(200200,"分组信息修改成功",array());
			}else{
				return_json(200204,"分组信息修改失败",array());
			}
		}

		/**
		 * 字段分组删除
		 * 字段分组中有描述字段的,不可删除
		 */
		function fieldGroupDel(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("aim_idlist");
			if(!isset($param['aim_idlist'])||empty($param['aim_idlist'])){
				return_json(200101,"目标分组数据编号未传",array());
			}
			$list_arr=explode(",",$param['aim_idlist']);
			foreach($list_arr as $key=>$value){
				$if_exit=Db::table("base_resource_field_dictionary")->where([['field_group_id','=',$value],['state','=','1'],['status','=','1']])->find();
				if(!empty($if_exit)){
					return_json(200101,"要删除的分组内含有描述字段,不可删除",array());
				}
			}

			$where[]=['id','in',$param['aim_idlist']];
			$data['state']="2";
			$del=Db::table("base_resource_field_group")->where($where)->update($data);
			if($del!==false){
				return_json(200200,"字段分组删除成功",array());
			}else{
				return_json(200204,"字段分组删除失败",array());
			}

		}


		/**
		 * 字典数据创建
		 */
		function fieldDictCreate(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("resource_type_code,field,cn_name,sort,data_source,level,data_deal_type,getdata_api,field_group_id,data_processing_fun,filtering_rules");
			$validate = new validate\Resourcedictionary;
			if (!$validate->scene('fieldDictCreate')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			if(!isset($param['sort'])||empty($param['sort'])){
				$param['sort']="1";
			}

			if(isset($param['field'])&&($param['field']=="name"||$param['field']=="serial_number"||$param['field']=="image"||$param['field']=="details_img"||$param['field']=="related_introduction")){
				return_json(200101,"创建属字段被系统占用,请更换!",array());
			}



			if($param['data_deal_type']=="radio"||$param['data_deal_type']=="checkbox"||$param['data_deal_type']=="select"){
				if(!isset($param['getdata_api'])||empty($param['getdata_api'])){
					return_json(200101,"辅助接口url未传",array());
				}
			}else{
				$param['getdata_api']="";
			}
			if($param['data_source']=="3"){
				if(!isset($param['data_processing_fun'])||empty($param['data_processing_fun'])){
					return_json(200101,"数据处理函数必传",array());
				}
			}else{
				$param['data_processing_fun']="";
			}

			if(!isset($param['filtering_rules'])||empty($param['filtering_rules'])){
				$param['filtering_rules']="";
			}


			//不能有重复的field
			$if_exits_field=Db::table("base_resource_field_dictionary")->where([['resource_type_code',
				'=',$param['resource_type_code']],['field','=',$param['field']],['state','=','1'],['status','=','1']])->find();


			if(!empty($if_exits_field)){
				return_json(200101,"字段KEY已经存在,不能重复添加",array());
			}

			$field_group_code=Db::table("base_resource_field_group")->where([['id','=',$param['field_group_id']],['state','=','1']])->field("code")->find();
			if(empty($field_group_code)){
				return_json(20101,"指定的字段分组不存在",array());
			}else{
				$param['field_group_code']=$field_group_code['code'];
			}

			$param['state']="1";
			$param['status']="1";
			$param['create_time']=fun_date(1);
			//dump($param);
			$add=Db::table("base_resource_field_dictionary")->insertGetId($param);
			if($add){
				return_json(200200,"字段创建成功",array("field"=>$param['field'],'name'=>$param['cn_name'],'new_id'=>$add));
			}else{
				return_json(200200,"字段创建失败,请重试",array("field"=>"",'name'=>"",'new_id'=>""));
			}

		}

		/**
		 * 字典数据获取(详情)
		 */
		function fieldDictInfo(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("aim_id");
			$validate = new validate\Resourcedictionary;
			if (!$validate->scene('fieldDictInfo')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}

			$where[]=['id','=',$param['aim_id']];
			$where[]=['state','=','1'];

			$info=Db::table("base_resource_field_dictionary")->where($where)->field("resource_type_code,field,cn_name,sort,data_source,level,data_deal_type,getdata_api,field_group_id,data_processing_fun,filtering_rules")->find();

			if(empty($info)){
				return_json(200104,"字段字典信息不存在",array());
			}else{

				$field_group_name=Db::table("base_resource_field_group")->where([['id','=',$info['field_group_id']]])->field("name")->find();

				$info['field_group_name']=$field_group_name['name'];

				return_json(200200,"字段字典信息获取成功",$info);
			}
		}

		/**
		 * 字典数据修改
		 */
		function fieldDictEdit(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("aim_id,cn_name,data_source,level,data_deal_type,getdata_api,field_group_id,data_processing_fun,filtering_rules,sort");
			$validate = new validate\Resourcedictionary;
			if (!$validate->scene('fieldDictInfo')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			$info=Db::table("base_resource_field_dictionary")->where([['id','=',$param['aim_id']],['state','=','1']])->find();
			if(empty($info)){
				return_json(200101,"字段字典数据不存在,请确认",array());
			}
			if(isset($param['cn_name'])&&!empty($param['cn_name'])){
				$data['cn_name']=$param['cn_name'];
			}
			if(isset($param['data_source'])&&!empty($param['data_source'])){
				$data['data_source']=$param['data_source'];
			}
			if(isset($param['level'])&&!empty($param['level'])){
				$data['level']=$param['level'];
			}
			if(isset($param['data_deal_type'])&&!empty($param['data_deal_type'])){
				$data['data_deal_type']=$param['data_deal_type'];
			}

			if(isset($param['field_group_id'])&&!empty($param['field_group_id'])){
				$data['field_group_id']=$param['field_group_id'];


				$field_group_code=Db::table("base_resource_field_group")->where([['id','=',$param['field_group_id']],['state','=','1']])->field("code")->find();

				if(empty($field_group_code)){
					return_json(200101,"字段所属分组数据不存在",array());
				}else{
					$data['field_group_code']=$field_group_code['code'];
				}
			}

			if(isset($param['filtering_rules'])){
				$data['filtering_rules']=$param['filtering_rules'];
			}

			if(isset($param['sort'])&&!empty($param['sort'])){
				$data['sort']=$param['sort'];
			}
			if(isset($param['sort'])&&empty($param['sort'])){
				$data['sort']="1";
			}
			//-----------data_source和data_processing_fun的关系和data_processing_fun的关系------------
			if(isset($param['data_source'])&&$param['data_source']=="3"){
				if($info['data_processing_fun']==""){
					if(!isset($param['data_processing_fun'])||empty($param['data_processing_fun'])){
						return_json(200101,"数据处理函数不得为空",array());
					}
				}else{
					if(isset($param['data_processing_fun'])&&empty($param['data_processing_fun'])){
						return_json(200101,"数据处理函数不得为空",array());
					}
				}
			}
			if(isset($param['data_source'])){
				$data['data_source']=$param['data_source'];
			}
			if(isset($param['data_processing_fun'])){
				$data['data_processing_fun']=$param['data_processing_fun'];
			}

			//-------------data_deal_type和getdata_api的关系-------------------
			if(isset($param['data_deal_type'])&&($param['data_deal_type']=="radio"||$param['data_deal_type']=="checkbox"||$param['data_deal_type']=="select")){
				if($info['getdata_api']==""){
					if(!isset($param['getdata_api'])||empty($param['getdata_api'])){
						return_json(200101,"辅助接口url必传",array());
					}
				}else{
					if(isset($param['getdata_api'])&&empty($param['getdata_api'])){
						return_json(200101,"辅助接口url必传",array());
					}
				}
			}
			if(isset($param['data_deal_type'])){
				$data['data_deal_type']=$param['data_deal_type'];
			}
			if(isset($param['getdata_api'])){
				$data['getdata_api']=$param['getdata_api'];
			}

			$edit=Db::table("base_resource_field_dictionary")->where([['id','=',$param['aim_id']]])->update($data);

			if($edit!==false){
				return_json(200200,"字段字典信息创建成功",array());
			}else{
				return_json(200204,"字段字典信息创建失败",array());
			}

		}


		/**
		 * 指定所属分类唯一编码,获取字段列表信息
		 */
		function fieldDictList(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("resource_type_code,page,size,field_group_code");
			$validate = new validate\Resourcedictionary;
			if (!$validate->scene('fieldDictList')->check($param)) {
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

			if(!isset($param['resource_type_code'])||empty($param['resource_type_code'])){
				return_json(200101,"所属资源分类唯一编码未传",array());
			}
			if(!isset($param['field_group_code'])||empty($param['field_group_code'])){
				return_json(200101,"所属字段分组唯一编码未传",array());
			}

			$where[]=['resource_type_code','=',$param['resource_type_code']];
			$where[]=['field_group_code','=',$param['field_group_code']];
			$where[]=['state','=','1'];
			$where[]=['status','=','1'];

			$list=Db::table("base_resource_field_dictionary")->where($where)->order("sort",'asc')->order("create_time","desc")->field("id,field,cn_name,level")->limit(($page-1)*$size,$size)->select();

			if(empty($list)){
				return_json(200204,"获取字段字典列表为空",array());
			}else{
				return_json(200200,"字段字典列表获取成功",$list);
			}

		}

		/**
		 * 删除字段字典数据(删除字段)
		 */
		function fieldDictDel(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("aim_idlist");
			if(!isset($param['aim_idlist'])||empty($param['aim_idlist'])){
				return_json(200101,"要删除的字段字典数据编号未传",array());
			}
			$where[]=['id','in',$param['aim_idlist']];
			$data['state']="2";
			$del=Db::table("base_resource_field_dictionary")->where($where)->update($data);
			if($del!==false){
				return_json(200200,"字段字典删除成功",array());
			}else{
				return_json(200200,"字段字典删除失败",array());
			}
		}


		/**
		 * 字典字段辅助code数据添加(为字段多选,下拉,单选服务)
		 */
		function fieldAuxiliarycodeCreate(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("name,pid,sort");
			$validate = new validate\Resourcedictionary;
			if (!$validate->scene('fieldAuxiliarycodeCreate')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			if(!isset($param['name'])||empty($param['name'])){
				return_json(200101,"名称未传",array());
			}
			if(!isset($param['pid'])||empty($param['pid'])){
				$param['pid']="0";
				$param['code']=create_uuid(time());
			}else{
				$uplevel_info=Db::table("field_dictionary_auxiliary_url")->where([['id','=',$param['pid']],['state','=','1']])->find();
				if(empty($uplevel_info)){
					return_json(200101,"上级信息不存在",array());
				}
				$param['code']="";
			}
			if(!isset($param['sort'])||empty($param['sort'])){
				$param['sort']="1";
			}


			$param['path']="";
			$param['state']="1";
			$param['status']="1";
			$param['create_time']=fun_date(1);

			$add=Db::table("field_dictionary_auxiliary_url")->insertGetId($param);
			if($add){
				if($param['pid']=="0"){
					Db::table("field_dictionary_auxiliary_url")->where([['id','=',$add]])->update(array("path"=>",".$add.","));
				}else{
					Db::table("field_dictionary_auxiliary_url")->where([['id','=',$add]])->update(array("path"=>$uplevel_info['path'].$add.","));
				}
				return_json(200200,'信息添加成功',array("new_id"=>$add,'code'=>$param['code']));
			}else{
				return_json(200204,'信息添加失败',array("new_id"=>"",'code'=>""));
			}

		}


		/**
		 * 字典字段辅助code数据修改(为字段多选,下拉,单选服务)
		 */
		function fieldAuxiliarycodeEdit(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("aim_id,name,sort");
			$validate = new validate\Resourcedictionary;
			if (!$validate->scene('fieldAuxiliarycodeEdit')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			if(isset($param['name'])&&!empty($param['name'])){
				$data['name']=$param['name'];
			}
			if(isset($param['sort'])&&!empty($param['sort'])){
				$data['sort']=$param['sort'];
			}
			if(!isset($data)){
				return_json(200101,"要修改的字段未传",array());
			}

			$edit=Db::table("field_dictionary_auxiliary_url")->where([['id','=',$param['aim_id']]])->update($data);
			if($edit!==false){
				return_json(200200,"修改成功",array());
			}else{
				return_json(200204,"修改失败",array());
			}

		}


		/**
		 * 字典字段辅助code数据详情(为字段多选,下拉,单选服务)
		 */
		function fieldAuxiliarycodeInfo(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("aim_id,code");
			if(isset($param['aim_id'])&&!empty($param['aim_id'])){
				$where[]=['id','=',$param['aim_id']];
			}
			if(isset($param['code'])&&!empty($param['code'])){
				$where[]=['code','=',$param['code']];
			}

			$where[]=['state','=','1'];

			if(!isset($where)){
				return_json(200101,"查询条件未传",array());
			}
			$info=Db::table("field_dictionary_auxiliary_url")->where($where)->field("name,pid,sort,code")->find();
			if(!empty($info)){
				return_json(200200,"详情获取成功",$info);
			}else{
				return_json(200204,"详情获取失败,不存在",array());
			}
		}


		/**
		 * 字典字段辅助code数据列表(为字段多选,下拉,单选服务) 指定pid
		 */
		function fieldAuxiliarycodeList(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("code,pid");
			if(isset($param['pid'])&&!empty($param['pid'])){
				$where[]=['pid','=',$param['pid']];
			}
			if(isset($param['code'])&&!empty($param['code'])){
				$where[]=['code','=',$param['code']];
			}


			if(!isset($where)){
				$where[]=['pid','=','0'];
			}

			$where[]=['state','=','1'];

			$list=Db::table("field_dictionary_auxiliary_url")->where($where)->field("name,pid,sort,code")->order("sort",'asc')->select();
			if(!empty($list)){
				return_json(200200,"列表获取成功",$list);
			}else{
				return_json(200204,"列表获取失败,不存在",array());
			}
		}



		/**
		 * 字典字段辅助code数据删除(为字段多选,下拉,单选服务)
		 */
		function fieldAuxiliarycodeDel(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("aim_idlist");
			if(!isset($param['aim_idlist'])||empty($param['aim_idlist'])){
				return_json(200101,"目标数据编号未传",array());
			}
			$where[]=['id','in',$param['aim_idlist']];

			if(!isset($where)){
				return_json(200101,"查询条件未传",array());
			}
			$edit=Db::table("field_dictionary_auxiliary_url")->where($where)->update(array("state"=>"2"));
			if($edit!==false){
				return_json(200200,"删除成功",array());
			}else{
				return_json(200204,"删除失败",array());
			}
		}

	}
