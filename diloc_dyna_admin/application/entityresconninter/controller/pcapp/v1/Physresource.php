<?php
	/**
	 * Created by PhpStorm.
	 * User: fu.hy
	 * Date: 2019/4/9
	 * Time: 10:52
	 */
	namespace app\entityresconninter\controller\pcapp\v1;
	use app\auth\controller\admin\v1\Authuser;
	use think\Controller;
	use think\Db;
	use think\facade\Request;
	use app\facade\ConnRedis;
	use think\facade\Validate;

	class Physresource extends Authuser
	{
		function __construct()
		{
			//静态代理类绑定,redis连接类
			\think\Facade::bind('app\facade\ConnRedis', 'app\common\ConnRedis');
			parent::__construct();
		}


		/**
		 * 为指定实体资源添加实体传感器信息添加
		 */
		function physResourceCreate(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();
			if(!isset($param['app_id'])||empty($param['app_id'])){
				return_json(200101,"项目编号未传",array());
			}


			//dump($param);
			//通过appid获取连接的数据库名称
			$db_name=Db::table("project_config")->where([['app_id','=',$param['app_id']],['state','=','1']])->field("db_name")->find();

			if(empty($db_name)||!isset($db_name['db_name'])||empty($db_name['db_name'])){
				return_json(200101,"项目编号指向的数据库名称不存在",array());
			}

			$db_name=$db_name['db_name'];

			//使用的资源模型唯一编码
			if(!isset($param['resource_template_code'])||empty($param['resource_template_code'])){
					$data['resource_template_code']="";
			}else{
				$data['resource_template_code']=$param['resource_template_code'];
			}
			if(!isset($param['resource_type_code'])||empty($param['resource_type_code'])){
				return_json(200101,"资源所属分类唯一编码未传(确认字典)",array());
			}
			$resource_last_typeinfo=Db::table("base_resource_type")->where([['code','=',$param['resource_type_code']],['state','=','1']])->field("id")->find();
			if(empty($resource_last_typeinfo)){
				return_json(200101,"资源分类数据不存在,请确认",array());
			}
			$uplevel_type=uplevel_department($resource_last_typeinfo['id']);
			//dump($uplevel_type);

			//dump($uplevel_type);

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
			//dump($dict_fields_arr);
			if(empty($dict_fields_arr)){
				//return_json(200101,"指定资源分类下未设置字段字典信息",array());
				//dump($param);

				if(isset($param['serial_number'])&&!empty($param['serial_number'])){
					$data['serial_number']=$param['serial_number'];
				}else{
					$data['serial_number']="";
				}

				if(!isset($param['name'])||empty($param['name'])){
					return_json(200101,"资源名称未传",array());
				}else{
					$data['name']=$param['name'];
				}
				if(isset($param['image'])&&!empty($param['image'])){
					$data['image']=$param['image'];
				}else{
					$data['image']="";
				}
				if(isset($param['details_img'])&&!empty($param['details_img'])){
					$data['details_img']=$param['details_img'];
				}else{
					$data['serial_number']="";
				}


				if(isset($param['related_introduction'])&&!empty($param['related_introduction'])){
					$data['related_introduction']=$param['related_introduction'];
				}else{
					$data['related_introduction']="";
				}


			}else{
				//dump($dict_fields_arr);
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

			}


			$data['status']="1";
			$data['state']="1";
			$data['sort']="1";
			$data['create_time']=fun_date(1);



			if(isset($param['if_conn_net'])&&!empty($param['if_conn_net'])){
				$data['if_conn_net']=$param['if_conn_net'];
			}else{
				$data['if_conn_net']="2";
			}
			if(isset($param['if_conn_status'])&&!empty($param['if_conn_status'])){
				$data['if_conn_status']=$param['if_conn_status'];
			}else{
				$data['if_conn_status']="3";
			}




			//传感器所在位置相关设置
			if(!isset($param['position_id'])||empty($param['position_id'])){
				return_json(200101,"传感器所在位置数据编号未传",array());
			}else{



				$assetsdata['position_id']=$param['position_id'];
				//位置信息获取
				$uplevel_position=tower_floor_room_mess_uplevel($param['position_id'],$db_name);

				$place_name_str="暂无";
				if(!empty($uplevel_position)){
					$uplevel_position=array_reverse($uplevel_position);//数组倒序
					//dump($uplevel_position);
					$uplevel_position_str=implode(",",$uplevel_position);
					$place_name_list=Db::table($db_name.".place")->where([['id','in',$uplevel_position_str]])->field("name")->select();
					$place_name_str=implode("-",array_column($place_name_list,'name'));
				}
				$assetsdata['position_name']=$place_name_str;

				//dump($assetsdata);
			}

			//dump($data);
			$add=Db::table($db_name.".resource_infoamtion")->insertGetId($data);
			if($add){

				$assetsdata['resource_id']=$add;
				$assetsdata['asset_serial_number']="";
				$assetsdata['belong_department_name']="";
				$assetsdata['admin_list']=[];
				$assetsdata['manufacture_date']="";
				$assetsdata['purchase_date']="";
				$assetsdata['supply_company']="";
				$assetsdata['supply_addr']="";
				$assetsdata['supply_landline']="";
				$assetsdata['supply_email']="";
				$assetsdata['supply_website']="";
				$assetsdata['supply_person']="";
				$assetsdata['supply_mobile']="";

				$assetsdata['state']="1";
				$assetsdata['status']="1";
				$assetsdata['sort']="1";
				$assetsdata['create_time']=fun_date(1);

				Db::table($db_name.".resource_asset_info")->insert($assetsdata);

				return_json(200200,"实体资源(基本信息)创建成功",array("new_id"=>$add),array());
			}else{
				return_json(200204,"实体资源(基本信息)创建失败",array("new_id"=>""),array());
			}
		}



		/**
		 * 实体传感器和指定实体资源绑定
		 *
		 */
		function resourceSensorRelationAdd(){

			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();
			if(!isset($param['app_id'])||empty($param['app_id'])){
				return_json(200101,"项目编号未传",array());
			}

			//dump($param);
			//通过appid获取连接的数据库名称
			$db_name=Db::table("project_config")->where([['app_id','=',$param['app_id']],['state','=','1']])->field("db_name")->find();

			if(empty($db_name)||!isset($db_name['db_name'])||empty($db_name['db_name'])){
				return_json(200101,"项目编号指向的数据库名称不存在",array());
			}

			$db_name=$db_name['db_name'];
			if(!isset($param['resource_id'])||empty($param['resource_id'])){
				return_json(200101,"要绑定传感器的资源数据编未传",array());
			}else{
				$if_res_info=Db::table($db_name.".resource_infoamtion")->where([['id','=',$param['resource_id']],['state','=','1']])->field("if_conn_net")->find();
				if(empty($if_res_info)){
					return_json(200101,"要绑定传感器的资源不存在请确认",array());
				}else{
					if($if_res_info['if_conn_net']=="2"){
						return_json(200101,"目标资源没有开放联网,不允许关联传感器信息",array());
					}
				}
			}

			if(!isset($param['sensor_idlist'])||empty($param['sensor_idlist'])){
				return_json(200101,"传感器资源数据编号列表未传",array());
			}else{
				$sensr_list_arr=array_unique(explode(",",$param['sensor_idlist']));
				//dump($sensr_list_arr);

				foreach($sensr_list_arr as $key=>$value){
					$data['resource_id']=$param['resource_id'];
					$data['sensor_id']=$value;
					$data['state']="1";
					$data['status']="1";
					$data['sort']="1";
					$data['create_time']=fun_date(1);

					$if_exits_relation=Db::table($db_name.".resource_sensor_relation")->where([['resource_id','=',$param['resource_id']],['sensor_id','=',$value],['state','=','1']])->find();
					if(empty($if_exits_relation)){

						Db::table($db_name.".resource_sensor_relation")->insert($data);
					}
				}

				return_json(200200,"实体传感器绑定实体资源操作成功",array());

			}

		}

		/**
		 * 为实体传感器添加监控参数
		 */
		function sensorSignalAdd(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();
			if(!isset($param['app_id'])||empty($param['app_id'])){
				return_json(200101,"项目编号未传",array());
			}

			//dump($param);
			//通过appid获取连接的数据库名称
			$db_name=Db::table("project_config")->where([['app_id','=',$param['app_id']],['state','=','1']])->field("db_name")->find();

			if(empty($db_name)||!isset($db_name['db_name'])||empty($db_name['db_name'])){
				return_json(200101,"项目编号指向的数据库名称不存在",array());
			}
			$db_name=$db_name['db_name'];


			//参数类型
			if(!isset($param['signal_type'])||empty($param['signal_type'])){
				return_json(200101,"参数类型未传",array());
			}
			$data['signal_type']=$param['signal_type'];
			//所属传感器唯一编码
			if(!isset($param['fk_resource_id'])||empty($param['fk_resource_id'])){
				return_json(200101,"所属传感器数据编号未传",array());
			}
			$data['fk_resource_id']=$param['fk_resource_id'];

			$data['signal_code']=create_uuid(time());

			$signal_code_sign=$data['signal_code'];

			//监控参数图标
			if(!isset($param['signal_icon'])||empty($param['signal_icon'])){
				return_json(200101,"监控参数图标未传",array());
			}
			$data['signal_icon']=$param['signal_icon'];

			//监控参数等级
			if(!isset($param['signal_level'])||empty($param['signal_level'])){
				$data['signal_level']="basic";
			}else{
				$data['signal_level']=$param['signal_level'];
			}

			//监控参数排序
			if(!isset($param['sort'])||empty($param['sort'])){
				$data['sort']='1';
			}else{
				$data['sort']=$param['sort'];
			}

			//监控参数名称
			if(!isset($param['signal_name'])||empty($param['signal_name'])){
				return_json(200101,"监控参数名称未传",array());
			}
			$data['signal_name']=$param['signal_name'];
			$data['state']="1";
			$data['status']="1";
			$data['create_time']=fun_date(1);


			switch($param['signal_type']){
				case '1'://模拟量
					//参数单位
					if(!isset($param['signal_unit'])||empty($param['signal_unit'])){
						return_json(200101,"监控参数单位未传",array());
					}
					$data['signal_unit']=$param['signal_unit'];

					//设备端传值修正公式
					if(!isset($param['correction_formula'])||empty($param['correction_formula'])){
						$data['correction_formula']="";
					}else{
						$data['correction_formula']=$param['correction_formula'];
					}

					if(isset($param['if_enable_alarm_level'])&&$param['if_enable_alarm_level']=="1"){
						$data['if_enable_alarm_level']="1";
						//参数报警规则
						if(!isset($param['signal_warn_config'])||empty($param['signal_warn_config'])){
							return_json(200101,"参数报警规则未传",array());
						}
						$signal_warn_config_jsonarr=json_decode(html_entity_decode($param['signal_warn_config']),true);
						//dump($signal_warn_config_jsonarr);
						//echo  "==============================";
						if(empty($signal_warn_config_jsonarr)){
							return_json(200101,"参数报警规则传值格式错误",array());
						}
						//判断所传json的key是否正确
						foreach($signal_warn_config_jsonarr as $key=>$value){
							//如果用户设置的模拟量
							if($data['signal_type']=="1"){

								if(!isset($value['warn_level_name'])||empty($value['warn_level_name'])){
									return_json(200101,"报警等级名称未传",array());
								}
								if(!isset($value['color'])){
									return_json(200101,"报警颜色key未传",array());
								}
								if(!isset($value['warn_message_temaplte_code'])){
									return_json(200101,"参数报警消息模板唯一编码key未设置",array());
								}
								if(!isset($value['normal_message_template_code'])){
									return_json(200101,"参数正常消息模板唯一编码key未设置",array());
								}
								if(!isset($value['logical_operator'])||empty($value['logical_operator'])){
									return_json(200101,"参数报警逻辑运算符未传",array());
								}
								if(!isset($value['logic_type'])||empty($value['logic_type'])){
									return_json(200101,"报警范围数据未传",array());
								}
								if(!isset($value['if_auxiliary'])||empty($value['if_auxiliary'])){
									return_json(200101,"是否使用参数报警辅助条件未传",array());
								}
								if($value['if_auxiliary']=="1"&&!isset($value['auxiliary_condition'])||empty($value['auxiliary_condition'])){
									return_json(200101,$value['warn_level_name']."报警辅助条件未传",array());
								}
								if(isset($value['auxiliary_condition'])&&!empty($value['auxiliary_condition'])){
									foreach($value['auxiliary_condition'] as $key1=>$value1){
										if(!isset($value1['sensor_code'])||empty($value1['sensor_code'])){
											return_json(200101,$value['warn_level_name']."报警辅助参数所属传感器唯一编码未传",array());
										}
										if(!isset($value1['signal_code'])||empty($value1['signal_code'])){
											return_json(200101,$value['warn_level_name']."报警辅助参数唯一编码未传",array());
										}
										if(!isset($value1['signal_type'])||empty($value1['signal_type'])){
											return_json(200101,$value['warn_level_name']."报警辅助参数类别未传",array());
										}

										if($value1['signal_type']=="1"){//辅助-模拟量
											if(!isset($value1['analog'])||empty($value1['analog'])){
												return_json(200101,"报警辅助参数(模拟量)报警范围数据未设置",array());
											}
											if(!isset($value1['analog']['logical_operator'])||empty($value1['analog']['logical_operator'])){
												return_json(200101,"报警辅助参数报警范围(逻辑运算符)未设置",array());
											}
											if(!isset($value1['analog']['logic_type'])||empty($value1['analog']['logic_type'])){
												return_json(200101,"报警辅助参数(模拟量)报警范围(表达式)未设置",array());
											}
										}

										if($value1['signal_type']=="2"){//辅助-开关量
											if(!isset($value1['quantity'])||empty($value1['quantity'])){
												return_json(200101,"报警辅助参数(开关量)报警范围数据未设置",array());
											}
											if(!isset($value1['quantity']['logic_type'])||empty($value1['quantity']['logic_type'])){
												return_json(200101,"报警辅助参数(开关量)报警范围(表达式)未设置",array());
											}
										}
									}
								}

							}
							if($data['signal_type']=="2"){//开关量json过滤
								if(!isset($value['warn_level_name'])||empty($value['warn_level_name'])){
									return_json(200101,"报警等级名称未传",array());
								}
								if(!isset($value['color'])){
									return_json(200101,"报警颜色key未传",array());
								}
								if(!isset($value['warn_message_temaplte_code'])){
									return_json(200101,"参数报警消息模板唯一编码key未设置",array());
								}
								if(!isset($value['normal_message_template_code'])){
									return_json(200101,"参数正常消息模板唯一编码key未设置",array());
								}
								if(!isset($value['real_val'])||empty($value['real_val'])){
									return_json(200101,"参数报警的设备端传值未传",array());
								}
								if(!isset($value['if_auxiliary'])||empty($value['if_auxiliary'])){
									return_json(200101,"是否使用参数报警辅助条件未传",array());
								}
								if($value['if_auxiliary']=="1"&&!isset($value['auxiliary_condition'])||empty($value['auxiliary_condition'])){
									return_json(200101,$value['warn_level_name']."报警辅助条件未传",array());
								}
								if(isset($value['auxiliary_condition'])&&!empty($value['auxiliary_condition'])){
									foreach($value['auxiliary_condition'] as $key1=>$value1){
										if(!isset($value1['sensor_code'])||empty($value1['sensor_code'])){
											return_json(200101,$value['warn_level_name']."报警辅助参数所属传感器唯一编码未传",array());
										}
										if(!isset($value1['signal_code'])||empty($value1['signal_code'])){
											return_json(200101,$value['warn_level_name']."报警辅助参数唯一编码未传",array());
										}
										if(!isset($value1['signal_type'])||empty($value1['signal_type'])){
											return_json(200101,$value['warn_level_name']."报警辅助参数类别未传",array());
										}

										if($value1['signal_type']=="1"){//辅助-模拟量
											if(!isset($value1['analog'])||empty($value1['analog'])){
												return_json(200101,"报警辅助参数(模拟量)报警范围数据未设置",array());
											}
											if(!isset($value1['analog']['logical_operator'])||empty($value1['analog']['logical_operator'])){
												return_json(200101,"报警辅助参数报警范围(逻辑运算符)未设置",array());
											}
											if(!isset($value1['analog']['logic_type'])||empty($value1['analog']['logic_type'])){
												return_json(200101,"报警辅助参数(模拟量)报警范围(表达式)未设置",array());
											}
										}

										if($value1['signal_type']=="2"){//辅助-开关量
											if(!isset($value1['quantity'])||empty($value1['quantity'])){
												return_json(200101,"报警辅助参数(开关量)报警范围数据未设置",array());
											}
											if(!isset($value1['quantity']['logic_type'])||empty($value1['quantity']['logic_type'])){
												return_json(200101,"报警辅助参数(开关量)报警范围(表达式)未设置",array());
											}
										}
									}
								}
							}
						}
						//dump($signal_warn_config_jsonarr);
						//---以上json格式过滤完毕--
						$signal_warn_config_arr=array();
						foreach($signal_warn_config_jsonarr as $key=>$value){

							$signal_warn_config_arr[$key]['signal_warn_code']=create_uuid(time()+1);
							$signal_warn_config_arr[$key]['warn_level_name']=$value['warn_level_name'];
							$signal_warn_config_arr[$key]['color']=$value['color'];
							$signal_warn_config_arr[$key]['warn_message_temaplte_code']=$value['warn_message_temaplte_code'];
							$signal_warn_config_arr[$key]['normal_message_template_code']=$value['normal_message_template_code'];
							$signal_warn_config_arr[$key]['logical_operator']=$value['logical_operator'];
							$signal_warn_config_arr[$key]['logic_type']=$value['logic_type'];
							$signal_warn_config_arr[$key]['if_auxiliary']=$value['if_auxiliary'];

							//监控参数报警辅助条件数据
							if(isset($value['auxiliary_condition'])&&!empty($value['auxiliary_condition'])){

								foreach($value['auxiliary_condition'] as $key1=>$value1){
									$signalwarn_auxiliary_condition['belong_signal_code']=$signal_code_sign;
									$signalwarn_auxiliary_condition['signal_warn_code']=$signal_warn_config_arr[$key]['signal_warn_code'];

									$signalwarn_auxiliary_condition['sensor_code']=$value1['sensor_code'];
									$sensor_name_str=Db::table($db_name.".resource_infoamtion")->where([['id','=',$value1['sensor_code']]])->field('name')->find()['name'];
									if(empty($sensor_name_str)){
										$sensor_name_str="";
									}
									$signalwarn_auxiliary_condition['sensor_name']=$sensor_name_str;

									$signal_name_str=Db::table($db_name.".signal")->where([['id','=',$value1['signal_code']]])->field('name')->find()['name'];
									if(empty($signal_name_str)){
										$signal_name_str="";
									}
									$signalwarn_auxiliary_condition['signal_name']=$signal_name_str;
									$signalwarn_auxiliary_condition['signal_code']=$value1['signal_code'];

									$signalwarn_auxiliary_condition['signal_type']=$value1['signal_type'];
									//dump($value1['signal_type']);
									if($value1['signal_type']=="1"){
										$signalwarn_auxiliary_condition['analog_quantity']=$value1['analog'];
										unset($signalwarn_auxiliary_condition['quantity']);
									}
									if($value1['signal_type']=="2"){
										$signalwarn_auxiliary_condition['analog_quantity']=$value1['quantity'];
										unset($signalwarn_auxiliary_condition['analog']);
									}
									$signalwarn_auxiliary_condition['sort']="1";
									$signalwarn_auxiliary_condition['state']="1";
									$signalwarn_auxiliary_condition['status']="1";
									$signalwarn_auxiliary_condition['create_time']=fun_date(1);

									//dump($signalwarn_auxiliary_condition);
									Db::table($db_name.".signalwarn_auxiliary_condition")->insertGetId($signalwarn_auxiliary_condition);
								}
							}
						}
						//dump($signal_warn_config_arr);
						$data['signal_warn_config']=$signal_warn_config_arr;

					}else{
						$data['if_enable_alarm_level']="2";
						$data['signal_warn_config']=array();
					}
					break;
				case '2'://开关量
					//参数单位
					if(!isset($param['signal_escape'])||empty($param['signal_escape'])){
						return_json(200101,"设备端传值意义未传",array());
					}

					$signal_escape_arr=(Object)json_decode(html_entity_decode($param['signal_escape']),true);

					$data['signal_escape']=$signal_escape_arr;

					if(isset($param['if_enable_alarm_level'])&&$param['if_enable_alarm_level']=="1") {
						$data['if_enable_alarm_level'] = "1";
						//参数报警规则
						if(!isset($param['signal_warn_config'])||empty($param['signal_warn_config'])){
							return_json(200101,"参数报警规则未传",array());
						}
						$signal_warn_config_jsonarr=json_decode(html_entity_decode($param['signal_warn_config']),true);
						//dump($signal_warn_config_jsonarr);
						//echo  "==============================";
						if(empty($signal_warn_config_jsonarr)){
							return_json(200101,"参数报警规则传值格式错误",array());
						}
						//判断所传json的key是否正确
						foreach($signal_warn_config_jsonarr as $key=>$value){
							//如果用户设置的模拟量
							if($data['signal_type']=="1"){

								if(!isset($value['warn_level_name'])||empty($value['warn_level_name'])){
									return_json(200101,"报警等级名称未传",array());
								}
								if(!isset($value['color'])){
									return_json(200101,"报警颜色key未传",array());
								}
								if(!isset($value['warn_message_temaplte_code'])){
									return_json(200101,"参数报警消息模板唯一编码key未设置",array());
								}
								if(!isset($value['normal_message_template_code'])){
									return_json(200101,"参数正常消息模板唯一编码key未设置",array());
								}
								if(!isset($value['logical_operator'])||empty($value['logical_operator'])){
									return_json(200101,"参数报警逻辑运算符未传",array());
								}
								if(!isset($value['logic_type'])||empty($value['logic_type'])){
									return_json(200101,"报警范围数据未传",array());
								}
								if(!isset($value['if_auxiliary'])||empty($value['if_auxiliary'])){
									return_json(200101,"是否使用参数报警辅助条件未传",array());
								}
								if($value['if_auxiliary']=="1"&&!isset($value['auxiliary_condition'])||empty($value['auxiliary_condition'])){
									return_json(200101,$value['warn_level_name']."报警辅助条件未传",array());
								}
								if(isset($value['auxiliary_condition'])&&!empty($value['auxiliary_condition'])){
									foreach($value['auxiliary_condition'] as $key1=>$value1){
										if(!isset($value1['sensor_code'])||empty($value1['sensor_code'])){
											return_json(200101,$value['warn_level_name']."报警辅助参数所属传感器数据编号未传",array());
										}
										if(!isset($value1['signal_code'])||empty($value1['signal_code'])){
											return_json(200101,$value['warn_level_name']."报警辅助参数唯一编码未传",array());
										}
										if(!isset($value1['signal_type'])||empty($value1['signal_type'])){
											return_json(200101,$value['warn_level_name']."报警辅助参数类别未传",array());
										}

										if($value1['signal_type']=="1"){//辅助-模拟量
											if(!isset($value1['analog'])||empty($value1['analog'])){
												return_json(200101,"报警辅助参数(模拟量)报警范围数据未设置",array());
											}
											if(!isset($value1['analog']['logical_operator'])||empty($value1['analog']['logical_operator'])){
												return_json(200101,"报警辅助参数报警范围(逻辑运算符)未设置",array());
											}
											if(!isset($value1['analog']['logic_type'])||empty($value1['analog']['logic_type'])){
												return_json(200101,"报警辅助参数(模拟量)报警范围(表达式)未设置",array());
											}
										}

										if($value1['signal_type']=="2"){//辅助-开关量
											if(!isset($value1['quantity'])||empty($value1['quantity'])){
												return_json(200101,"报警辅助参数(开关量)报警范围数据未设置",array());
											}
											if(!isset($value1['quantity']['logic_type'])||empty($value1['quantity']['logic_type'])){
												return_json(200101,"报警辅助参数(开关量)报警范围(表达式)未设置",array());
											}
										}
									}
								}

							}
							if($data['signal_type']=="2"){//开关量json过滤
								if(!isset($value['warn_level_name'])||empty($value['warn_level_name'])){
									return_json(200101,"报警等级名称未传",array());
								}
								if(!isset($value['color'])){
									return_json(200101,"报警颜色key未传",array());
								}
								if(!isset($value['warn_message_temaplte_code'])){
									return_json(200101,"参数报警消息模板唯一编码key未设置",array());
								}
								if(!isset($value['normal_message_template_code'])){
									return_json(200101,"参数正常消息模板唯一编码key未设置",array());
								}
								if(!isset($value['real_val'])||empty($value['real_val'])){
									return_json(200101,"参数报警的设备端传值未传",array());
								}
								if($value['if_auxiliary']=="1"&&!isset($value['auxiliary_condition'])||empty($value['auxiliary_condition'])){
									return_json(200101,$value['warn_level_name']."报警辅助条件未传",array());
								}
								if(isset($value['auxiliary_condition'])&&!empty($value['auxiliary_condition'])){
									foreach($value['auxiliary_condition'] as $key1=>$value1){
										if(!isset($value1['sensor_code'])||empty($value1['sensor_code'])){
											return_json(200101,$value['warn_level_name']."报警辅助参数所属传感器数据编号未传",array());
										}
										if(!isset($value1['signal_code'])||empty($value1['signal_code'])){
											return_json(200101,$value['warn_level_name']."报警辅助参数数据编号未传",array());
										}
										if(!isset($value1['signal_type'])||empty($value1['signal_type'])){
											return_json(200101,$value['warn_level_name']."报警辅助参数类别未传",array());
										}

										if($value1['signal_type']=="1"){//辅助-模拟量
											if(!isset($value1['analog'])||empty($value1['analog'])){
												return_json(200101,"报警辅助参数(模拟量)报警范围数据未设置",array());
											}
											if(!isset($value1['analog']['logical_operator'])||empty($value1['analog']['logical_operator'])){
												return_json(200101,"报警辅助参数报警范围(逻辑运算符)未设置",array());
											}
											if(!isset($value1['analog']['logic_type'])||empty($value1['analog']['logic_type'])){
												return_json(200101,"报警辅助参数(模拟量)报警范围(表达式)未设置",array());
											}
										}

										if($value1['signal_type']=="2"){//辅助-开关量
											if(!isset($value1['quantity'])||empty($value1['quantity'])){
												return_json(200101,"报警辅助参数(开关量)报警范围数据未设置",array());
											}
											if(!isset($value1['quantity']['logic_type'])||empty($value1['quantity']['logic_type'])){
												return_json(200101,"报警辅助参数(开关量)报警范围(表达式)未设置",array());
											}
										}
									}
								}
							}
						}
						//dump($signal_warn_config_jsonarr);
						//---以上json格式过滤完毕--
						$signal_warn_config_arr=array();
						foreach($signal_warn_config_jsonarr as $key=>$value){

							$signal_warn_config_arr[$key]['signal_warn_code']=create_uuid(time()+1);
							$signal_warn_config_arr[$key]['warn_level_name']=$value['warn_level_name'];
							$signal_warn_config_arr[$key]['color']=$value['color'];
							$signal_warn_config_arr[$key]['warn_message_temaplte_code']=$value['warn_message_temaplte_code'];
							$signal_warn_config_arr[$key]['normal_message_template_code']=$value['normal_message_template_code'];
							$signal_warn_config_arr[$key]['real_val']=$value['real_val'];
							$signal_warn_config_arr[$key]['if_auxiliary']=$value['if_auxiliary'];

							//监控参数报警辅助条件数据
							if(isset($value['auxiliary_condition'])&&!empty($value['auxiliary_condition'])){

								foreach($value['auxiliary_condition'] as $key1=>$value1){
									$signalwarn_auxiliary_condition['belong_signal_code']=$signal_code_sign;
									$signalwarn_auxiliary_condition['signal_warn_code']=$signal_warn_config_arr[$key]['signal_warn_code'];

									$signalwarn_auxiliary_condition['sensor_code']=$value1['sensor_code'];
									$sensor_name_str=Db::table($db_name.".resource_infoamtion")->where([['id','=',$value1['sensor_code']]])->field('name')->find()['name'];
									if(empty($sensor_name_str)){
										$sensor_name_str="";
									}
									$signalwarn_auxiliary_condition['sensor_name']=$sensor_name_str;

									$signal_name_str=Db::table($db_name.".signal")->where([['id','=',$value1['signal_code']]])->field('name')->find()['name'];
									if(empty($signal_name_str)){
										$signal_name_str="";
									}
									$signalwarn_auxiliary_condition['signal_name']=$signal_name_str;
									$signalwarn_auxiliary_condition['signal_code']=$value1['signal_code'];

									$signalwarn_auxiliary_condition['signal_type']=$value1['signal_type'];
									//dump($value1['signal_type']);
									if($value1['signal_type']=="1"){
										$signalwarn_auxiliary_condition['analog_quantity']=$value1['analog'];
										unset($signalwarn_auxiliary_condition['quantity']);
									}
									if($value1['signal_type']=="2"){
										$signalwarn_auxiliary_condition['analog_quantity']=$value1['quantity'];
										unset($signalwarn_auxiliary_condition['analog']);
									}
									$signalwarn_auxiliary_condition['sort']="1";
									$signalwarn_auxiliary_condition['state']="1";
									$signalwarn_auxiliary_condition['status']="1";
									$signalwarn_auxiliary_condition['create_time']=fun_date(1);
									//dump($signalwarn_auxiliary_condition);
									Db::table($db_name.".signalwarn_auxiliary_condition")->insertGetId($signalwarn_auxiliary_condition);
								}
							}
						}
						//dump($signal_warn_config_arr);
						$data['signal_warn_config']=$signal_warn_config_arr;

					}else{
						$data['if_enable_alarm_level']="2";
						$data['signal_warn_config']=array();
					}

					break;
				default:
					return_json(200101,"请确认参数类型",array());
			}

			$add=Db::table($db_name.".signal")->insertGetId($data);

			if($add){


				return_json(200200,"监控参数添加成功",array("new_id"=>$add));
			}else{
				return_json(200204,"监控参数添加失败",array("new_id"=>""));
			}
		}

		/**
		 * 监控参数基本信息修改
		 * /resource/admin/v1/sensor_template_signal_edit
		 */
		function sensorSignalInfoEdit(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();
			if(!isset($param['app_id'])||empty($param['app_id'])){
				return_json(200101,"项目编号未传",array());
			}
			//dump($param);
			//通过appid获取连接的数据库名称
			$db_name=Db::table("project_config")->where([['app_id','=',$param['app_id']],['state','=','1']])->field("db_name")->find();

			if(empty($db_name)||!isset($db_name['db_name'])||empty($db_name['db_name'])){
				return_json(200101,"项目编号指向的数据库名称不存在",array());
			}
			$db_name=$db_name['db_name'];

			if(!isset($param['signal_id'])||empty($param['signal_id'])){
				return_json(200101,"监控参数数据编号未传",array());
			}
			$info=Db::table($db_name.".signal")->where([['id','=',$param['signal_id']],['state','=','1']])->find();
			if(empty($info)){
				return_json(200101,"监控参数不存在,请确认",array());
			}
			//dump($info);
			if(isset($param['signal_icon'])){
				$data['signal_icon']=$param['signal_icon'];
			}
			if(isset($param['signal_level'])){
				$data['signal_level']=$param['signal_level'];
			}
			if(isset($param['signal_name'])){
				$data['signal_name']=$param['signal_name'];
			}
			if(isset($param['sort'])){
				$data['sort']=$param['sort'];
			}
			if(isset($param['if_enable_alarm_level'])){
				$data['if_enable_alarm_level']=$param['if_enable_alarm_level'];
			}
			//模拟量
			if($info['signal_type']=="1"){
				if(isset($param['signal_unit'])){
					$data['signal_unit']=$param['signal_unit'];
				}
				if(isset($param['correction_formula'])){
					$data['correction_formula']=$param['correction_formula'];
				}
			}

			//开关量
			if($info['signal_type']=="2"){
				if(isset($param['signal_escape'])){
					$param['signal_escape']=(Object)json_decode(html_entity_decode($param['signal_escape']),true);
					$data['signal_escape']=$param['signal_escape'];
				}
			}


			if(isset($param['signal_warn_config'])){

				$signal_warn_config_jsonarr=json_decode(html_entity_decode($param['signal_warn_config']),true);
				//echo  "==============================";
				if(empty($signal_warn_config_jsonarr)){
					return_json(200101,"参数报警规则传值格式错误",array());
				}

				//dump($signal_warn_config_jsonarr);
				//判断所传json的key是否正确
				foreach($signal_warn_config_jsonarr as $key=>$value){
					//如果用户设置的模拟量
					if($info['signal_type']=="1"){
						if(!isset($value['signal_warn_code'])||empty($value['signal_warn_code'])){
							return_json(200101,"参数报警规则唯一编码未传",array());
						}

						if(!isset($value['warn_level_name'])||empty($value['warn_level_name'])){
							return_json(200101,"报警等级名称未传",array());
						}
						if(!isset($value['color'])){
							return_json(200101,"报警颜色key未传",array());
						}
						if(!isset($value['warn_message_temaplte_code'])){
							return_json(200101,"参数报警消息模板唯一编码key未设置",array());
						}
						if(!isset($value['normal_message_template_code'])){
							return_json(200101,"参数正常消息模板唯一编码key未设置",array());
						}
						if(!isset($value['logical_operator'])||empty($value['logical_operator'])){
							return_json(200101,"参数报警逻辑运算符未传",array());
						}
						if(!isset($value['logic_type'])||empty($value['logic_type'])){
							return_json(200101,"报警范围数据未传",array());
						}
						if(!isset($value['if_auxiliary'])||empty($value['if_auxiliary'])){
							return_json(200101,"是否使用参数报警辅助条件未传",array());
						}


					}

					if($info['signal_type']=="2"){//开关量json过滤
						if(!isset($value['warn_level_name'])||empty($value['warn_level_name'])){
							return_json(200101,"报警等级名称未传",array());
						}
						if(!isset($value['color'])){
							return_json(200101,"报警颜色key未传",array());
						}
						if(!isset($value['warn_message_temaplte_code'])){
							return_json(200101,"参数报警消息模板唯一编码key未设置",array());
						}
						if(!isset($value['normal_message_template_code'])){
							return_json(200101,"参数正常消息模板唯一编码key未设置",array());
						}
						if(!isset($value['real_val'])||empty($value['real_val'])){
							return_json(200101,"参数报警的设备端传值未传",array());
						}
						if(!isset($value['if_auxiliary'])||empty($value['if_auxiliary'])){
							return_json(200101,"是否使用参数报警辅助条件未传",array());
						}
					}

					if($info['signal_type']=="1"){
						$signal_warn_config_arr=array();
						foreach($signal_warn_config_jsonarr as $key=>$value){
							$signal_warn_config_arr[$key]['signal_warn_code']=$value['signal_warn_code'];
							$signal_warn_config_arr[$key]['warn_level_name']=$value['warn_level_name'];
							$signal_warn_config_arr[$key]['color']=$value['color'];
							$signal_warn_config_arr[$key]['warn_message_temaplte_code']=$value['warn_message_temaplte_code'];
							$signal_warn_config_arr[$key]['normal_message_template_code']=$value['normal_message_template_code'];
							$signal_warn_config_arr[$key]['logical_operator']=$value['logical_operator'];
							$signal_warn_config_arr[$key]['logic_type']=$value['logic_type'];
							$signal_warn_config_arr[$key]['if_auxiliary']=$value['if_auxiliary'];
						}
						//dump($signal_warn_config_arr);
					}

					if($info['signal_type']=="2"){
						$signal_warn_config_arr=array();
						foreach($signal_warn_config_jsonarr as $key=>$value) {

							$signal_warn_config_arr[$key]['signal_warn_code'] = $value['signal_warn_code'];
							$signal_warn_config_arr[$key]['warn_level_name'] = $value['warn_level_name'];
							$signal_warn_config_arr[$key]['color'] = $value['color'];
							$signal_warn_config_arr[$key]['warn_message_temaplte_code'] = $value['warn_message_temaplte_code'];
							$signal_warn_config_arr[$key]['normal_message_template_code'] = $value['normal_message_template_code'];
							$signal_warn_config_arr[$key]['real_val'] = $value['real_val'];
							$signal_warn_config_arr[$key]['if_auxiliary'] = $value['if_auxiliary'];
						}
					}

					$data['signal_warn_config']=$signal_warn_config_arr;

				}
			}

			if(!isset($data)){
				return_json(200101,"要修改的字段未传",array());
			}

			//dump($data);
			$edit=Db::table($db_name.".signal")->where([['id','=',$param['signal_id']]])->update($data);
			if($edit!==false){
				return_json(200200,"监控参数基本信息修改成功",array());
			}else{
				return_json(200204,"监控参数基本信息修改成功修改失败",array());
			}
		}


		/**
		 * 监控参数报警辅助条件删除
		 * /resource/admin/v1/template_signalwarn_condition_del
		 */
		function signalwarnConditionDel(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();
			if(!isset($param['app_id'])||empty($param['app_id'])){
				return_json(200101,"项目编号未传",array());
			}
			//dump($param);
			//通过appid获取连接的数据库名称
			$db_name=Db::table("project_config")->where([['app_id','=',$param['app_id']],['state','=','1']])->field("db_name")->find();

			if(empty($db_name)||!isset($db_name['db_name'])||empty($db_name['db_name'])){
				return_json(200101,"项目编号指向的数据库名称不存在",array());
			}
			$db_name=$db_name['db_name'];

			if(!isset($param['aim_idlist'])||empty($param['aim_idlist'])){
				return_json(200101,"要修改的辅助条件数据编号未传",array());
			}

			$where[]=['id','in',$param['aim_idlist']];

			$del=Db::table($db_name.".signalwarn_auxiliary_condition")->where($where)->delete();
			if($del){
				return_json(200200,"监控参数报警辅助条件规则删除成功",array());
			}else{
				return_json(200204,"监控参数报警辅助条件规则删除失败",array());
			}

		}


		/**
		 * 监控参数详情 查表:signal
		 * /resource/admin/v1/sensor_template_signal_info
		 */
		function signalInfoShow(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();
			if(!isset($param['app_id'])||empty($param['app_id'])){
				return_json(200101,"项目编号未传",array());
			}
			//dump($param);
			//通过appid获取连接的数据库名称
			$db_name=Db::table("project_config")->where([['app_id','=',$param['app_id']],['state','=','1']])->field("db_name")->find();

			if(empty($db_name)||!isset($db_name['db_name'])||empty($db_name['db_name'])){
				return_json(200101,"项目编号指向的数据库名称不存在",array());
			}
			$db_name=$db_name['db_name'];
			if(!isset($param['signal_id'])||empty($param['signal_id'])){
				return_json(200101,"目标监控参数数据编号未传",array());
			}
			$where[]=['id','=',$param['signal_id']];
			$where[]=['state','=','1'];

			$info=Db::table($db_name.".signal")->where($where)->field("fk_resource_id,signal_code,signal_icon,signal_level,signal_name,sort,signal_escape,if_enable_alarm_level,signal_warn_config,signal_unit,correction_formula,signal_type")->find();

			if(empty($info)){
				return_json(200204,"监控参数模板信息获取失败,可能被删除",array());
			}else{

				if($info['signal_type']=="2"){
					$info['signal_escape']=(Object)$info['signal_escape'];
					//dump($info);
					$signal_escape_arr=array();
					if(!empty($info['signal_escape'])){
						foreach($info['signal_escape'] as $key=>$value){
							array_push($signal_escape_arr,array("real_val"=>$key,"show_val"=>$value));
						}
					}
					$info['signal_escape']=$signal_escape_arr;
				}

				return_json(200200,"监控参数数据详情获取成功",$info);
			}

		}


		/**
		 * 参数报警等级关联的辅助条件列表
		 * /resource/admin/v1/template_signalwarn_condition_list
		 */
		function signalwarnConditionList(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();
			if(!isset($param['app_id'])||empty($param['app_id'])){
				return_json(200101,"项目编号未传",array());
			}
			//dump($param);
			//通过appid获取连接的数据库名称
			$db_name=Db::table("project_config")->where([['app_id','=',$param['app_id']],['state','=','1']])->field("db_name")->find();

			if(empty($db_name)||!isset($db_name['db_name'])||empty($db_name['db_name'])){
				return_json(200101,"项目编号指向的数据库名称不存在",array());
			}
			$db_name=$db_name['db_name'];

			if(!isset($param['signal_id'])||empty($param['signal_id'])){
				return_json(200101,"目标监控参数数据编号未传",array());
			}

			//查询监控参数唯一编码
			$signal_code=Db::table($db_name.".signal")->where([['id','=',$param['signal_id']],['state','=','1']])->field("signal_code")->find();
			if(empty($signal_code)){
				return_json(200101,"监控参数信息不存在,请确认",array());
			}else{
				if(empty($signal_code['signal_code'])){
					return_json(200101,"监控参数唯一编码为空,请确认",array());
				}
			}

			$signal_code_val=$signal_code['signal_code'];


			if(!isset($param['signal_warn_code'])||empty($param['signal_warn_code'])){
				return_json(200101,"监控参数报警规则唯一编码未传",array());
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
			if(isset($param['keywords'])&&!empty($param['keywords'])){
				$where[]=['sensor_name|signal_name','like',$param['keywords']];
			}

			$where[]=['state','=','1'];
			$where[]=['belong_signal_code','=',$signal_code_val];
			$where[]=['signal_warn_code','=',$param['signal_warn_code']];


			$list=Db::table($db_name.".signalwarn_auxiliary_condition")->where($where)->field("sensor_name,signal_name,sensor_code,signal_code,signal_type,analog_quantity")->order("sort",'asc')->limit(($page-1)*$size,$size)->select();
			if(empty($list)){
				return_json(200204,"参数报警辅助条件列表为空",array());
			}else{
				return_json(200200,"参数报警辅助条件列表获取成功",$list);
			}

		}



		/**
		 * 参数报警n级报警数据删除按钮
		 * /resource/admin/v1/templatesignal_warnlevel_del
		 */
		function signalWarnlevelDelBtn(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();
			if(!isset($param['app_id'])||empty($param['app_id'])){
				return_json(200101,"项目编号未传",array());
			}
			//dump($param);
			//通过appid获取连接的数据库名称
			$db_name=Db::table("project_config")->where([['app_id','=',$param['app_id']],['state','=','1']])->field("db_name")->find();

			if(empty($db_name)||!isset($db_name['db_name'])||empty($db_name['db_name'])){
				return_json(200101,"项目编号指向的数据库名称不存在",array());
			}
			$db_name=$db_name['db_name'];

			if(!isset($param['signal_id'])||empty($param['signal_id'])){
				return_json(200101,"监控参数数据编号未传",array());
			}
			if(!isset($param['signal_warn_code'])||empty($param['signal_warn_code'])){
				return_json(200101,"监控参数报警等级唯一编码未传",array());
			}

			$signal_signal_warn_config=Db::table($db_name.".signal")->where([['state','=','1'],['id','=',$param['signal_id']]])->field("signal_warn_config,signal_code")->find();
			if(empty($signal_signal_warn_config)){
				return_json(200101,"监控参数信息不存在,请确认",array());
			}


			//dump($signal_signal_warn_config);
			$new_warn_config_arr=array();
			if(!empty($signal_signal_warn_config['signal_warn_config'])){
				foreach($signal_signal_warn_config['signal_warn_config'] as $key=>$value){
					if($value['signal_warn_code']!=$param['signal_warn_code']){
						array_push($new_warn_config_arr,$value);
					}
				}
			}

			//dump($new_warn_config_arr);
			$data['signal_warn_config']=$new_warn_config_arr;
			$del=Db::table($db_name.".signal")->where([['id','=',$signal_signal_warn_config['id']]])->update($data);
			if($del!==false){
				$where[]=['belong_signal_code','=',$signal_signal_warn_config['signal_code']];
				$where[]=['signal_warn_code','=',$param['signal_warn_code']];
				Db::table($db_name.".signalwarn_auxiliary_condition")->where($where)->delete();
				return_json(200200,"参数报警等级数据删除成功(关联的辅助条件也会被删除)",array());
			}else{
				return_json(200204,"参数模板报警等级数据删除失败",array());
			}
		}



		/**
		 *实体资源关联的传感器数据列表
		 */
		function resourceSensorList(){

			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();
			if(!isset($param['app_id'])||empty($param['app_id'])){
				return_json(200101,"项目编号未传",array());
			}
			//dump($param);
			//通过appid获取连接的数据库名称
			$db_name=Db::table("project_config")->where([['app_id','=',$param['app_id']],['state','=','1']])->field("db_name")->find();

			if(empty($db_name)||!isset($db_name['db_name'])||empty($db_name['db_name'])){
				return_json(200101,"项目编号指向的数据库名称不存在",array());
			}
			$db_name=$db_name['db_name'];

			if(!isset($param['resource_id'])||empty($param['resource_id'])){
				return_json(200101,"实体资源编号未传",array());
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
			$where[]=['resource_id','=',$param['resource_id']];
			$where[]=['state','=','1'];

			$list=Db::table($db_name.".resource_sensor_relation")->where($where)->field("sensor_id")->order("sort",'asc')->limit(($page-1)*$size,$size)->select();
			if(empty($list)){
				return_json(200200,"资源绑定的传感器列表为空",array());
			}else{

				$sensor_idlist=implode(",",array_column($list,'sensor_id'));

				$where_sensor[]=['id','in',$sensor_idlist];
				$where_sensor[]=['state','=','1'];
				$sensorlist=Db::table($db_name.".resource_infoamtion")->where($where_sensor)->field("name,id")->select();
				if(!empty($sensorlist)){
					foreach($list as $key=>$value){
						$list[$key]['sensor_name']="--";
						foreach($sensorlist as $key1=>$value1){
							if($value1['id']==$value['sensor_id']){
								$list[$key]['sensor_name']=$value1['name'];
							}
						}
					}
					return_json(200200,"传感器列表获取成功",$list);
				}else{
					return_json(200200,"传感器列表为空,请确认",array());
				}

			}

		}


		/**
		 *实体传感器下监控参数列表    查表:signal和resource_signal_relation      判断是否制定实体资源编号
		 *
		 */
		function resourceSensorSignalList(){

			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();
			if(!isset($param['app_id'])||empty($param['app_id'])){
				return_json(200101,"项目编号未传",array());
			}
			//dump($param);
			//通过appid获取连接的数据库名称
			$db_name=Db::table("project_config")->where([['app_id','=',$param['app_id']],['state','=','1']])->field("db_name")->find();

			if(empty($db_name)||!isset($db_name['db_name'])||empty($db_name['db_name'])){
				return_json(200101,"项目编号指向的数据库名称不存在",array());
			}
			$db_name=$db_name['db_name'];

			if(!isset($param['sensor_id'])||empty($param['sensor_id'])){
				return_json(200101,"传感器数据编号未传",array());
			}




			$signal_list=Db::table($db_name.".signal")->where([['fk_resource_id','=',$param['sensor_id']],['state','=','1']])->field("id,signal_name,signal_code,fk_resource_id,signal_type")->order("sort",'asc')->select();
			if(empty($signal_list)){
				return_json(200200,"传感器没有监控参数",array());
			}else{

				//dump($signal_list);


				if(isset($param['resource_id'])&&!empty($param['resource_id'])){
					//查询资源和监控参数的关系表
					$signal_id_arr=Db::table($db_name.".resource_signal_relation")->where([['resource_id','=',$param['resource_id']],['state','=','1'],['sensor_id','=',$param['sensor_id']]])->field("signal_id,sensor_id")->select();
					if(empty($signal_id_arr)){
						return_json(200200,"指定传感器,与指定资源绑定的监控参数列表为空",array());
					}else{
						//dump($signal_id_arr);
						$res_signal_list=array();
						foreach($signal_list as $key=>$value) {
							foreach ($signal_id_arr as $key1 => $value1) {
								if ($value1['signal_id'] == $value['id']) {
									array_push($res_signal_list, $value);
								}
							}
						}

						//dump($res_signal_list);
						if(empty($res_signal_list)){
							return_json(200200,"指定传感器,与指定资源绑定的监控参数列表为空(#002)",array());
						}else{

							return_json(200200,"监控参数列表获取成功",$res_signal_list);

						}
					}

				}
				return_json(200200,"监控参数列表获取成功",$signal_list);

			}

		}


		/**
		 * 实体资源和传感器解除绑定   同时删除资源和监控参数的关系
		 */
		function resourceSensorUnbundling(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();
			if(!isset($param['app_id'])||empty($param['app_id'])){
				return_json(200101,"项目编号未传",array());
			}
			//dump($param);
			//通过appid获取连接的数据库名称
			$db_name=Db::table("project_config")->where([['app_id','=',$param['app_id']],['state','=','1']])->field("db_name")->find();

			if(empty($db_name)||!isset($db_name['db_name'])||empty($db_name['db_name'])){
				return_json(200101,"项目编号指向的数据库名称不存在",array());
			}
			$db_name=$db_name['db_name'];

			if(!isset($param['sensor_id'])||empty($param['sensor_id'])){
				return_json(200101,"传感器数据编号未传",array());
			}

			if(!isset($param['resource_id'])||empty($param['resource_id'])){
				return_json(200101,"资源编号未传",array());
			}

			//查询传感器下所有参数
			$signal_list=Db::table($db_name.".signal")->where([['fk_resource_id','=',$param['sensor_id']]])->where("id")->select();

			if(!empty($signal_list)){
				$signal_listid=implode(",",array_column($signal_list,'id'));
				Db::table($db_name.".resource_signal_relation")->where([['resource_id','=',$param['resource_id']],['sensor_id','in',$signal_listid]])->update(array("state"=>"2"));
			}

			$del=Db::table($db_name.".resource_sensor_relation")->where([['resource_id','=',$param['resource_id']],['sensor_id','=',$param['sensor_id']]])->update(array("state"=>"2"));

			if($del!==false){
				return_json(200200,"资源和传感器解绑成功",array());
			}else{
				return_json(200204,"资源和传感器解绑失败",array());
			}

		}


		/**
		 * 监控参数和实体资源关系创建
		 */
		function resourceSignalRelationAdd(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();
			if(!isset($param['app_id'])||empty($param['app_id'])){
				return_json(200101,"项目编号未传",array());
			}
			//dump($param);
			//通过appid获取连接的数据库名称
			$db_name=Db::table("project_config")->where([['app_id','=',$param['app_id']],['state','=','1']])->field("db_name")->find();

			if(empty($db_name)||!isset($db_name['db_name'])||empty($db_name['db_name'])){
				return_json(200101,"项目编号指向的数据库名称不存在",array());
			}
			$db_name=$db_name['db_name'];

			if(!isset($param['signal_id'])||empty($param['signal_id'])){
				return_json(200101,"监控参数数据编号未传",array());
			}

			if(!isset($param['resource_id'])||empty($param['resource_id'])){
				return_json(200101,"资源编号未传",array());
			}

			if(!isset($param['level'])||empty($param['level'])){
				$data['level']="basic";
			}else{
				$data['level']=$param['level'];
			}

			if(!isset($param['if_user_set'])||empty($param['if_user_set'])){
				$data['if_user_set']="2";
			}else{
				$data['if_user_set']=$param['if_user_set'];
			}

			if(!isset($param['if_control_btn'])||empty($param['if_control_btn'])){
				$data['if_control_btn']="1";
			}else{
				$data['if_control_btn']="1";
			}

			if(!isset($param['sort'])||empty($param['sort'])){
				$data['sort']="1";
			}else{
				$data['sort']=$param['sort'];
			}

			$signal_info=Db::table($db_name.".signal")->where([['id','=',$param['signal_id']],['state','=','1']])->field("id,fk_resource_id")->find();
			if(empty($signal_info)){
				return_json(200101,"监控参数信息不存在",array());
			}

			$resource_info=Db::table($db_name.".resource_infoamtion")->where([['id','=',$param['resource_id']],['state','=','1']])->field("id")->find();
			if(empty($resource_info)){
				return_json(200101,"实体资源信息不存在",array());
			}



			$if_exits_relation=Db::table($db_name.".resource_signal_relation")->where([['state','=','1'],['resource_id','=',$param['resource_id']],['signal_id','=',$param['signal_id']]])->field("id")->find();
			if(!empty($if_exits_relation)){
				return_json(200201,"关系数据已经存在,不能重复添加",array());
			}else{


				$data['resource_id']=$param['resource_id'];
				$data['signal_id']=$param['signal_id'];
				$data['sensor_id']=$signal_info['fk_resource_id'];
				$data['state']="1";
				$data['status']="1";
				$data['create_time']=fun_date(1);


				$add=Db::table($db_name.".resource_signal_relation")->insert($data);
				if($add){
					return_json(200200,"关系建立成功",array());
				}else{
					return_json(200204,"关系建立失败",array());
				}

			}

		}

		/**
		 * 监控参数和实体资源关系解除
		 */
		function resourceSignalRelationDel(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();
			if(!isset($param['app_id'])||empty($param['app_id'])){
				return_json(200101,"项目编号未传",array());
			}
			//dump($param);
			//通过appid获取连接的数据库名称
			$db_name=Db::table("project_config")->where([['app_id','=',$param['app_id']],['state','=','1']])->field("db_name")->find();

			if(empty($db_name)||!isset($db_name['db_name'])||empty($db_name['db_name'])){
				return_json(200101,"项目编号指向的数据库名称不存在",array());
			}
			$db_name=$db_name['db_name'];

			if(!isset($param['signal_id'])||empty($param['signal_id'])){
				return_json(200101,"监控参数数据编号未传",array());
			}

			if(!isset($param['resource_id'])||empty($param['resource_id'])){
				return_json(200101,"资源编号未传",array());
			}



			$del=Db::table($db_name.".resource_signal_relation")->where([['resource_id','=',$param['resource_id']],['signal_id','=',$param['signal_id']],['state','=','1']])->update(array("state"=>"2"));

			if($del!==false){
				return_json(200200,"资源和监控参数解绑成功",array());
			}else{
				return_json(200204,"资源和监控参数解绑失败",array());
			}

		}


		/**
		 * 修改资源和监控参数关系数据
		 */
		function resourceSignalRelationEdit(){

			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();
			if(!isset($param['app_id'])||empty($param['app_id'])){
				return_json(200101,"项目编号未传",array());
			}
			//dump($param);
			//通过appid获取连接的数据库名称
			$db_name=Db::table("project_config")->where([['app_id','=',$param['app_id']],['state','=','1']])->field("db_name")->find();

			if(empty($db_name)||!isset($db_name['db_name'])||empty($db_name['db_name'])){
				return_json(200101,"项目编号指向的数据库名称不存在",array());
			}
			$db_name=$db_name['db_name'];

			if(!isset($param['signal_id'])||empty($param['signal_id'])){
				return_json(200101,"监控参数数据编号未传",array()); 
			}

			if(!isset($param['resource_id'])||empty($param['resource_id'])){
				return_json(200101,"资源编号未传",array());
			}

			if(isset($param['level'])&&!empty($param['level'])){
				$data['level']=$param['level'];
			}
			if(isset($param['if_user_set'])&&!empty($param['if_user_set'])){
				$data['if_user_set']=$param['if_user_set'];
			}

			if(isset($param['if_control_btn'])&&!empty($param['if_control_btn'])){
				$data['if_control_btn']=$param['if_control_btn'];
			}
			if(isset($param['sort'])&&!empty($param['sort'])){
				$data['sort']=$param['sort'];
			}

			if(!isset($data)){
				return_json(2002101,"要修改的字段未传",array());
			}


			$edit=Db::table($db_name.".resource_signal_relation")->where([['resource_id','=',$param['resource_id']],['signal_id','=',$param['signal_id']],['state','=','1']])->update($data);
			if($edit!==false){
				return_json(200200,"资源和监控参数关系数据修改成功",array());
			}else{
				return_json(200204,"资源和监控参数关系数据修改失败",array());
			}


		}


	}