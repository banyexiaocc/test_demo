<?php
	/**
	 * Created by PhpStorm.
	 * User: fu.hy
	 * Date: 2020/2/24
	 * Time: 15:03
	 * 资源(传感器)模型和参数模型的关系
	 */
	namespace app\resource\controller\admin\v1;
	use tests\RotateTest;
	use think\Controller;
	use think\facade\Request;//数据接收-静态代理
	use think\Db;
	use app\resource\validate;
	use app\auth\controller\admin\v1\Authuser;



	class Resourcesignalmodel extends Authuser
	{
		//方法已经可用
		public function __construct()
		{
			parent::__construct();
		}

		/**
		 * 为传感器模型添加监控参数模型
		 */
		function sensorTemplateSignalAdd(){

			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();
			//参数类型
			if(!isset($param['signal_type'])||empty($param['signal_type'])){
				return_json(200101,"参数类型未传",array());
			}
			$data['signal_type']=$param['signal_type'];
			//所属传感器唯一编码
			if(!isset($param['fk_resource_code'])||empty($param['fk_resource_code'])){
				return_json(200101,"所属传感器模板唯一编码未传",array());
			}
			$data['fk_resource_code']=$param['fk_resource_code'];

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
				return_json(200101,"监控参数模型名称未传",array());
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
									$sensor_name_str=Db::table("template_resource_infoamtion")->where([['resource_template_code','=',$value1['sensor_code']]])->field('name')->find()['name'];
									if(empty($sensor_name_str)){
										$sensor_name_str="";
									}
									$signalwarn_auxiliary_condition['sensor_name']=$sensor_name_str;

									$signal_name_str=Db::table("template_signal")->where([['signal_code','=',$value1['signal_code']]])->field('name')->find()['name'];
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
									Db::table("template_signalwarn_auxiliary_condition")->insertGetId($signalwarn_auxiliary_condition);
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
							$signal_warn_config_arr[$key]['real_val']=$value['real_val'];
							$signal_warn_config_arr[$key]['if_auxiliary']=$value['if_auxiliary'];

							//监控参数报警辅助条件数据
							if(isset($value['auxiliary_condition'])&&!empty($value['auxiliary_condition'])){

								foreach($value['auxiliary_condition'] as $key1=>$value1){
									$signalwarn_auxiliary_condition['belong_signal_code']=$signal_code_sign;
									$signalwarn_auxiliary_condition['signal_warn_code']=$signal_warn_config_arr[$key]['signal_warn_code'];

									$signalwarn_auxiliary_condition['sensor_code']=$value1['sensor_code'];
									$sensor_name_str=Db::table("template_resource_infoamtion")->where([['resource_template_code','=',$value1['sensor_code']]])->field('name')->find()['name'];
									if(empty($sensor_name_str)){
										$sensor_name_str="";
									}
									$signalwarn_auxiliary_condition['sensor_name']=$sensor_name_str;

									$signal_name_str=Db::table("template_signal")->where([['signal_code','=',$value1['signal_code']]])->field('name')->find()['name'];
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
									Db::table("template_signalwarn_auxiliary_condition")->insertGetId($signalwarn_auxiliary_condition);
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

			$add=Db::table("template_signal")->insertGetId($data);
			if($add){
				return_json(200200,"监控参数模型添加成功",array("new_id"=>$add,"code"=>$data['signal_code']));
			}else{
				return_json(200204,"监控参数模型添加失败",array("new_id"=>"","code"=>""));
			}
		}

		/**
		 * 指定传感器模型唯一编码,获取监控参数模板列表
		 */
		function sensorTemplateSignalList(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("signal_type,sensor_code,signal_level,page,size,keywords");
			$validate = new validate\Resourcesignalmodel;
			if (!$validate->scene('sensorTemplateSignalList')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			if(isset($param['signal_level'])&&!empty($param['signal_level'])){
				switch($param['signal_level']){
					case 'basic':
						$where[]=['signal_level','=','basic'];
						break;
					case 'more':
						$where[]=['signal_level','=','more'];
						break;
					default:
						return_json(200101,"请确认参数等级传值",array());
				}
			}
			if(isset($param['signal_type'])&&!empty($param['signal_type'])){
				$where[]=['signal_type','=',$param['signal_type']];
			}
			if(isset($param['keywords'])&&!empty($param['keywords'])){
				$where[]=['signal_name|signal_code','like',$param['keywords']];
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

			$where[]=['fk_resource_code','=',$param['sensor_code']];

			$where[]=['state','=','1'];
			$list=Db::table("template_signal")->where($where)->field("signal_name,signal_level,signal_icon,signal_code,fk_resource_code,signal_type")->order("sort",'asc')->limit(($page-1)*$size,$size)->select();
			if(empty($list)){
				return_json(200204,"监控参数模板列表为空",array());
			}else{
				return_json(200200,"监控参数模板列表获取成功",$list);
			}
		}

		/**
		 * 指定监控参数模板唯一编码,获取监控参数模板基本信息
		 */
		function sensorTemplateSignalInfo(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("signal_code");
			$validate = new validate\Resourcesignalmodel;
			if (!$validate->scene('sensorTemplateSignalInfo')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}

			$where[]=['signal_code','=',$param['signal_code']];
			$where[]=['state','=','1'];

			$info=Db::table("template_signal")->where($where)->field("fk_resource_code,signal_code,signal_icon,signal_level,signal_name,sort,signal_escape,if_enable_alarm_level,signal_warn_config,signal_unit,correction_formula,signal_type")->find();

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

				return_json(200200,"监控参数模板数据获取成功",$info);
			}

		}

		/**
		 * 修改监控参数模板系详情信息
		 */
		function sensorTemplateSignalEdit(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->param();
			if(!isset($param['signal_code'])||empty($param['signal_code'])){
				return_json(200101,"监控参数模板唯一编码未传",array());
			}
			$info=Db::table("template_signal")->where([['signal_code','=',$param['signal_code']],['state','=','1']])->find();
			if(empty($info)){
				return_json(200101,"监控参数模型不存在,请确认",array());
			}
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
				//$data['signal_warn_config']=$param['signal_warn_config'];

				$signal_warn_config_jsonarr=json_decode(html_entity_decode($param['signal_warn_config']),true);

				//dump($signal_warn_config_jsonarr);


				//echo  "==============================";
				if(empty($signal_warn_config_jsonarr)){
					return_json(200101,"参数报警规则传值格式错误",array());
				}
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
			$edit=Db::table("template_signal")->where([['signal_code','=',$param['signal_code']]])->update($data);
			if($edit!==false){
				return_json(200200,"监控参数模板基本信息修改成功",array());
			}else{
				return_json(200204,"监控参数模板基本信息修改成功修改失败",array());
			}
		}

		/**
		 * 监控参数模板n级报警删除按钮
		 */
		function templateSignalWarnLevelDel(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("signal_code,signal_warn_code");
			$validate = new validate\Resourcesignalmodel;
			if (!$validate->scene('templateSignalWarnLevelDel')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			$signal_signal_warn_config=Db::table("template_signal")->where([['state','=','1'],['signal_code','=',$param['signal_code']]])->field("signal_warn_config")->find();
			if(empty($signal_signal_warn_config)){
				return_json(200101,"监控参数模板信息不存在,请确认",array());
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
			$del=Db::table("template_signal")->where([['id','=',$signal_signal_warn_config['id']]])->update($data);
			if($del!==false){
				$where[]=['belong_signal_code','=',$param['signal_code']];
				$where[]=['signal_warn_code','=',$param['signal_warn_code']];
				Db::table("template_signalwarn_auxiliary_condition")->where($where)->delete();
				return_json(200200,"参数报警等级数据删除成功(关联的辅助条件也会被删除)",array());
			}else{
				return_json(200204,"参数模板报警等级数据删除失败",array());
			}
		}



		//-------------------------------


		/**
		 * 参数报警等级关联的报警辅助条件列表
		 */
		function templateSignalWarnConditionList(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("signal_code,signal_warn_code,page,size,keywords");
			$validate = new validate\Resourcesignalmodel;
			if (!$validate->scene('templateSignalWarnConditionList')->check($param)) {
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

			if(isset($param['keywords'])&&!empty($param['keywords'])){
				$where[]=['signal_name','like',$param['keywords']];
			}


			$where[]=['state','=','1'];
			$where[]=['belong_signal_code','=',$param['signal_code']];
			$where[]=['signal_warn_code','=',$param['signal_warn_code']];

			$list=Db::table("template_signalwarn_auxiliary_condition")->where($where)->field("sensor_name,signal_name,sensor_code,signal_code,signal_type,analog_quantity")->order("sort",'asc')->limit(($page-1)*$size,$size)->select();
			if(empty($list)){
				return_json(200204,"参数模板报警辅助条件列表为空",array());
			}else{
				return_json(200200,"参数模板报警辅助条件列表获取成功",$list);
			}

		}


		/**
		 * 参数报警等级关联的报警辅助条件列表刪除
		 */
		function templateSignalWarnConditionDel(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("aim_idlist");
			$validate = new validate\Resourcesignalmodel;
			if (!$validate->scene('templateSignalWarnConditionDel')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			$where[]=['id','in',$param['aim_idlist']];

			$del=Db::table("template_signalwarn_auxiliary_condition")->where($where)->delete();
			if($del){
				return_json(200200,"监控参数报警辅助条件规则删除成功",array());
			}else{
				return_json(200204,"监控参数报警辅助条件规则删除失败",array());
			}
		}


		/**
		 *  参数报警等级关联的报警辅助条件添加
		 *
		 */
		function templateSignalWarnConditionAdd(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("belong_signal_code,signal_warn_code,sensor_code,signal_code,sort,analog_quantity");
			$validate = new validate\Resourcesignalmodel;
			if (!$validate->scene('templateSignalWarnConditionAdd')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			if(isset($param['sort'])&&!empty($param['sort'])){
				$data['sort']=$param['sort'];
			}else{
				$data['sort']="1";
			}
			if($param['belong_signal_code']==$param['signal_code']){
				return_json(200101,"参数自身不得设置成本身的抱紧辅助条件",array());
			}
			if(!isset($param['analog_quantity'])||empty($param['analog_quantity'])){
				return_json(200101,"辅助条件配置信息未传",array());
			}
		    $data['belong_signal_code']=$param['belong_signal_code'];
			$data['signal_warn_code']=$param['signal_warn_code'];

			//辅助条件传感器基本信息
			$con_sensor_info=Db::table("template_resource_infoamtion")->where([['resource_template_code','=',$param['sensor_code']],['state','=','1']])->field("name")->find();
			if(empty($con_sensor_info)){
				return_json(200101,"作为辅助条件的传感器模板数据不存在,请确认",array());
			}
			$data['sensor_code']=$param['sensor_code'];
			$data['sensor_name']=$con_sensor_info['name'];

			//辅助条件参数基本信息
			$con_signal_info=Db::table("template_signal")->where([['signal_code','=',$param['signal_code']],['state','=','1']])->field("signal_type,signal_name")->find();
			if(empty($con_signal_info)){
				return_json(200101,"作为辅助条件的监控参数不存在,请确认",array());
			}


			$data['signal_code']=$param['signal_code'];
			$data['signal_name']=$con_signal_info['signal_name'];
			$data['signal_type']=$con_signal_info['signal_type'];

			$analog_quantity_arr=json_decode(html_entity_decode($param['analog_quantity']),true);
			if(empty($analog_quantity_arr)){
				return_json(200101,"辅助条件配置信息格式错误(#001)",array());
			}
			switch($con_signal_info['signal_type']){
				case '1'://模拟领
					foreach($analog_quantity_arr as $key=>$value){
						if(!isset($analog_quantity_arr['logic_type'])||empty($analog_quantity_arr['logic_type'])||!is_array($analog_quantity_arr['logic_type'])){
							return_json(200101,"辅助条件配置信息格式错误(#003)",array());
						}
						if(!isset($analog_quantity_arr['logical_operator'])||empty($analog_quantity_arr['logical_operator'])){
							return_json(200101,"辅助条件配置信息格式错误(#004)",array());
						}
					}
					$analog_quantity_arr_info['logical_operator']=$analog_quantity_arr['logical_operator'];
					$analog_quantity_arr_info['logic_type']=$analog_quantity_arr['logic_type'];

					break;
				case '2'://开关量
					foreach($analog_quantity_arr as $key=>$value){
						if(!isset($analog_quantity_arr['logic_type'])||empty($analog_quantity_arr['logic_type'])||!is_array($analog_quantity_arr['logic_type'])){
							return_json(200101,"辅助条件配置信息格式错误(#002)",array());
						}
					}
					$analog_quantity_arr_info['logic_type']=$analog_quantity_arr['logic_type'];

					break;
				default:
					return_json(200101,"监控参数类型无法处理,请确认",array());
			}


			$data['analog_quantity']=$analog_quantity_arr_info;
			$data['state']="1";
			$data['status']="1";
			$data['create_time']=fun_date(1);

			$if_exits=Db::table("template_signalwarn_auxiliary_condition")->where([['belong_signal_code','=',$data['belong_signal_code']],['signal_warn_code','=',$data['signal_warn_code']],['sensor_code','=',$data['sensor_code']],['signal_code','=',$param['signal_code']],['state','=','1']])->find();
			if(!empty($if_exits)){
				return_json(200101,"数据已经存在,不能重复设置",array());
			}else{
				$add=Db::table("template_signalwarn_auxiliary_condition")->insertGetId($data);
				if($add){
					return_json(200200,"报警辅助条件添加成功",array("new_id"=>$add));
				}else{
					return_json(200204,"报警辅助条件添加失败",array("new_id"=>""));
				}
			}
		}

		/**
		 * 参数模板报警复制条件信息编辑
		 */
		function templateSignalWarnConditionEdit(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("aim_id,signal_code,sort,analog_quantity");
			$validate = new validate\Resourcesignalmodel;
			if (!$validate->scene('templateSignalWarnConditionEdit')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}

			if(isset($param['sort'])&&!empty($param['sort'])){
				$data['sort']=$param['sort'];
			}

			//辅助条件参数基本信息
			$con_signal_info=Db::table("template_signal")->where([['signal_code','=',$param['signal_code']],['state','=','1']])->field("signal_type,signal_name")->find();


			if(isset($param['analog_quantity'])&&!empty($param['analog_quantity'])){

				$analog_quantity_arr=json_decode(html_entity_decode($param['analog_quantity']),true);

				switch($con_signal_info['signal_type']){
					case '1'://模拟领
						foreach($analog_quantity_arr as $key=>$value){
							if(!isset($analog_quantity_arr['logic_type'])||empty($analog_quantity_arr['logic_type'])||!is_array($analog_quantity_arr['logic_type'])){
								return_json(200101,"辅助条件配置信息格式错误(#003)",array());
							}
							if(!isset($analog_quantity_arr['logical_operator'])||empty($analog_quantity_arr['logical_operator'])){
								return_json(200101,"辅助条件配置信息格式错误(#004)",array());
							}
						}
						$analog_quantity_arr_info['logical_operator']=$analog_quantity_arr['logical_operator'];
						$analog_quantity_arr_info['logic_type']=$analog_quantity_arr['logic_type'];

						break;
					case '2'://开关量
						foreach($analog_quantity_arr as $key=>$value){
							if(!isset($analog_quantity_arr['logic_type'])||empty($analog_quantity_arr['logic_type'])||!is_array($analog_quantity_arr['logic_type'])){
								return_json(200101,"辅助条件配置信息格式错误(#002)",array());
							}
						}
						$analog_quantity_arr_info['logic_type']=$analog_quantity_arr['logic_type'];

						break;
					default:
						return_json(200101,"监控参数类型无法处理,请确认",array());
				}
				$data['analog_quantity']=$analog_quantity_arr_info;
			}

			if(!isset($data)){
				return_json(200101,"要修改的字段信息未传",array());
			}
			$edit=Db::table("template_signalwarn_auxiliary_condition")->where([['id','=',$param['aim_id']]])->update($data);
			if($edit!==false){
				return_json(200200,"参数报警辅助条件信息修改成功",array());
			}else{
				return_json(200204,"参数报警辅助条件信息修改失败",array());
			}

		}

	}