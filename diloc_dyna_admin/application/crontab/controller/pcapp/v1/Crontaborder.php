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

	class Crontaborder
	{


		function array_unset_tt($arr,$key){
			//建立一个目标数组
			$res = array();
			foreach ($arr as $value) {
				//查看有没有重复项

				if(isset($res[$value[$key]])){
					//有：销毁

					unset($value[$key]);


				}
				else{

					$res[$value[$key]] = $value;
				}
			}
			return $res;
		}



        /**
		 *用户预约仪器订单超时自动取消
		 */
		function orderAppointPasstimeCancel(){


			$project_idlist=Db::table("project")->where([['state','=','1'],['status','in','1,2']])->field("id")->select();
			if(!empty($project_idlist)) {
				$project_idlist_str = implode(",", array_column($project_idlist, 'id'));
				$db_name_list = Db::table("project_config")->where([['app_id', 'in', $project_idlist_str]])->field("db_name,app_id")->select();
				if (!empty($db_name_list)) {
					$db_name_list = array_values($this->array_unset_tt($db_name_list,'db_name'));
					foreach($db_name_list as $key=>$value){
						$db_name=$value['db_name'];
						//dump($db_name);
						$order_list=Db::table($db_name.".order")->where([['state','=','1'],['status','in','1,2,7']])->select();

						if(!empty($order_list)){
							foreach($order_list as $key1=>$value1){
								//dump($value1);
								if(empty($value1['period_time_list'])){
									if(isset($value1['period_start_time'])){
										$order_list[$key1]['order_start_time']=$value1['period_start_time'];
										$order_list[$key1]['order_end_time']=$value1['period_end_time'];
									}
								}else{
									if(isset($value1['period_time_list'][0]['start_time'])){
										$order_list[$key1]['order_start_time']=$value1['period_time_list'][0]['start_time'];
										$order_list[$key1]['order_end_time']=$value1['period_time_list'][0]['end_time'];
									}else{
										$order_list[$key1]['order_start_time']="";
										$order_list[$key1]['order_end_time']="";
									}

								}
							}
							foreach($order_list as $key1=>$value1){
								if($value1['status']=="1"){//待审核
									if($value1['order_start_time']!=""){
										if(time()-strtotime($value1['order_start_time'])>=1800){
											//过期操作
											Db::table($db_name.".order")->where([['id','=',$value1['id']]])->update(array("status"=>"6","is_pass"=>"1"));
//											删除占用时间
											Db::table($db_name.".resource_takeup_periodtime")->where([['order_id','=',$value1['id']]])->update(array("state"=>"2"));
											//退款....没写....
											$a = $this -> user_order_return_money($db_name,$value1['uid'],$value1['subject_team_id'],$value['app_id'],$value1['plan_price'],$value1['id'],'订单过期退款',$value1['settlement_type']);

										}
									}
								}

								if($value1['status']=="2"){//待使用
									if($value1['order_start_time']!=""){

										if(time()>=strtotime($value1['order_start_time'])&&time()<=strtotime($value1['order_end_time'])){
											Db::table($db_name.".order")->where([['id','=',$value1['id']]])->update(array("status"=>"7"));
										}
										if(time()>strtotime($value1['order_end_time'])){
											Db::table($db_name.".order")->where([['id','=',$value1['id']]])->update(array("status"=>"7"));
										}

									}
								}

								if($value1['status']=="7"){//使用中
									if($value1['order_start_time']!=""&&$value1['shared_code']=="independent"){

										if(time()>=strtotime($value1['order_end_time'])){
											Db::table($db_name.".order")->where([['id','=',$value1['id']]])->update(array("status"=>"3"));
											//删除占用时间
											Db::table($db_name.".resource_takeup_periodtime")->where([['order_id','=',$value1['id']]])->update(array("state"=>"2"));
										}
									}
								}


								//获取订单状态,适当情况删卡命令
								$order_infomation=Db::table($db_name.".order")->where([['id','=',$value1['id']],['state','=','1']])->field("status")->find();
								if(!empty($order_infomation)){

									//3,4,5,6
									if($order_infomation['status']=="3"||$order_infomation['status']=="4"||$order_infomation['status']=="5"||$order_infomation['status']=="6"){

										//获取预约的资源可控参数列表
										$card_control_signallist=Db::table($db_name.".resource_signal_relation")->where([['resource_id','=',$order_infomation['resource_id']],['state','=','1'],['if_user_set','=','1'],['if_control_btn','=','1']])->field("signal_id")->select();

										if(isset($order_info['period_time_list'])&&!empty($order_info['period_time_list'])){
											$start_time=$order_info['period_time_list'][0]['start_time'];
											$end_time=$order_info['period_time_list'][0]['end_time'];
										}else{
											$start_time="";
											$end_time="";
											if(isset($order_info['period_start_time'])&&!empty($order_info['period_start_time'])){
												$start_time=$order_info['period_start_time'];
											}
											if(isset($order_info['period_end_time'])&&!empty($order_info['period_end_time'])){
												$end_time=$order_info['period_end_time'];
											}
										}

										if($start_time!=""&&$end_time!="") {

											if (!empty($card_control_signallist)) {
												$signal_idlist_arr = array_values(array_unique(array_column($card_control_signallist, 'signal_id')));
												//获取当前用户手中的ic卡和手环
												$ic_bracelet_idlist = Db::table($db_name . ".card_infoamtion")->where([['uid', '=', $order_infomation['uid']], ['state', '=', '1'], ['status', 'in', '1']])->field("card_number,card_type,id")->select();

												if (!empty($ic_bracelet_idlist)) {

													foreach($ic_bracelet_idlist as $key2=>$value2){

														foreach($signal_idlist_arr as $key3=>$value3){
															$card_signal_relation_info=Db::table($db_name.".card_signal_relation")->where([['card_info_id','=',$value2['id']],['signal_id','=',$value3],['state','=','1'],['start_time','=',$start_time],['end_time','=',$end_time]])->find();



															if(!empty($card_signal_relation_info)){

																//发送开卡信息-----------------------------------

																$curl = curl_init();
//设置抓取的url
																curl_setopt($curl, CURLOPT_URL, 'http://39.105.153.63:154/dyna/card/v1/client_end/open_close_card');
//设置头文件的信息作为数据流输出
																curl_setopt($curl, CURLOPT_HEADER, 0);
//设置获取的信息以文件流的形式返回，而不是直接输出。
																curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
//设置post方式提交
																curl_setopt($curl, CURLOPT_POST, 1);


																if($value2['card_type']=="ic"){
																	$signa_card_type="C";
																}
																if($value2['card_type']=="bracelet"){
																	$signa_card_type="B";
																}
//设置post数据
																$post_data = array(
																	"access_token" => "b4909d8921f94dd3c5995852264ccbcad35426fa",
																	"login_type" => "pc",
																	"app_id"=>$value['app_id'],
																	"relation_code"=>'',
																	"card_number"=>$value2['card_number'],
																	"signal_id"=>$value3,
																	"start_time"=>$start_time,
																	"end_time"=>$end_time,
																	"card_type"=>$signa_card_type,
																	"movement"=>'1'
																);

//post提交的数据
																curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
//执行命令
																$data = curl_exec($curl);
//关闭URL请求
																curl_close($curl);

															}


														}
													}


												}
											}

										}

















									}



								}






							}

						}

					}
				}
			}



		}

        /**
         * 用户订单退款
         */
        protected function user_order_return_money($db_name,$uid,$subject_id,$app_id,$money,$id,$remark,$settlement_type)
        {
            switch ($settlement_type)
            {

                case 1:
                    // 课题组结算
                    $subject_team_account = Db::table('subject_team_account')
                        ->where('subj_team_id',$subject_id)
                        ->where('app_id',$app_id)
                        ->where('state','1')
                        ->where('status','1')
                        ->find();
                    // 余额加本次使用金额
                    $account_balance = (float)number_format($subject_team_account['account_balance'] + $money,2,'.','');
//                消费总额
                    $all_pay_money = (float)number_format($subject_team_account['all_pay_money'] - $money,2,'.','');
                    // 判断是否使用透支额度
                    if($subject_team_account['used_overdraft'] > 0)
                    {
                        $used_overdraft = (float)number_format($subject_team_account['used_overdraft'] - $money,2,'.','');
                        if($used_overdraft <= 0)
                        {
                            $used_overdraft = 0;
                        }
                    }else{
                        $used_overdraft = $subject_team_account['used_overdraft'];
                    }
                    Db::table('subject_team_account')
                        ->where('subj_team_id',$subject_id)
                        ->where('app_id',$app_id)
                        ->where('state','1')
                        ->where('status','1')
                        ->update(['account_balance' => $account_balance,'used_overdraft' => $used_overdraft,'all_pay_money' => $all_pay_money]);
                    Db::table('user_bill')
                        ->insertGetId([
                            'money_type' => '3',
                            'type' => '2',
                            'uid' => $uid,
                            'subject_team_id' => $subject_id,
                            'order_id' => $id,
                            'remark' => $remark,
                            'amount' => $money,
                            'state' => '1',
                            'status' => '1',
                            'sort' => 1,
                            'create_time' => date('Y-m-d H:i:s'),
                            'app_id' => $app_id,
                        ]);
                    return true;

                    break;
                case 2:

                    $user_account = Db::table('user_account')
                        ->where('uid',$uid)
                        ->where('app_id',$app_id)
                        ->where('state','1')
                        ->where('status','1')
                        ->find();
                    // 余额加本次使用金额
                    $account_balance = (float)number_format($user_account['account_balance'] + $money,2,'.','');
//                消费总额
                    $all_pay_money = (float)number_format($user_account['all_pay_money'] - $money,2,'.','');
                    // 仪器消费
                    $appoint_order_money = (float)number_format($user_account['appoint_order_money'] - $money,2,'.','');
                    // 判断是否使用透支额度
                    if($user_account['used_overdraft'] > 0)
                    {
                        $used_overdraft = (float)number_format($user_account['used_overdraft'] - $money,2,'.','');
                        if($used_overdraft <= 0)
                        {
                            $used_overdraft = 0;
                        }
                    }else{
                        $used_overdraft = $user_account['used_overdraft'];
                    }
                    Db::table('user_account')
                        ->where('uid',$uid)
                        ->where('app_id',$app_id)
                        ->where('state','1')
                        ->where('status','1')
                        ->update(['account_balance' => $account_balance,'appoint_order_money' => $appoint_order_money,'used_overdraft' => $used_overdraft,'all_pay_money' => $all_pay_money]);

                    Db::table('user_bill')
                        ->insertGetId([
                            'money_type' => '3',
                            'type' => '1',
                            'uid' => $uid,
                            'order_id' => $id,
                            'remark' => $remark,
                            'amount' => $money,
                            'state' => '1',
                            'status' => '1',
                            'sort' => 1,
                            'create_time' => date('Y-m-d H:i:s'),
                            'app_id' => $app_id,
                        ]);

                    return true;
                    // 个人结算
                    break;
            }
        }



	}
