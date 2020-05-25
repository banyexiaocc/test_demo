<?php
	/**
	 * Created by PhpStorm.
	 * User: fu.hy
	 * Date: 2019/3/29
	 * Time: 11:15
	 */

	namespace app\login\controller\pcapp\v1;
	use app\announcement\model\AuditMess;
    use think\Controller;
	use think\facade\Request;//数据接收-静态代理
	use app\facade\ConnRedis;//引入静态代理类,连接Redis
	use app\facade\ValidationFunction;//引入静态代理类,连接Redis
	use think\Db;
	use app\login\validate;


	class AverageUserLogin
	{

		function __construct()
		{
			//静态代理类绑定,redis连接类
			\think\Facade::bind('app\facade\ConnRedis', 'app\common\ConnRedis');
		}

		/**
		 * 区分指定端的access_token是哦否正确
		 */
		function distinguish_end($login_type,$access_token){

			if(empty($login_type)){
				return_json(200101,"登录端类型未传值",array());
			}
			if(empty($access_token)){
				return_json(200101,"ACCESSTOKEN 未传",array());
			}

			switch($login_type){
				case 'app':
					$access_token_sign="diloc_project_app_access_token";
					break;
				case 'pc':
					$access_token_sign="diloc_project_pc_access_token";
					break;
				case 'dyna':
					$access_token_sign="diloc_dyna_access_token";
					break;
				default:
					return_json(200101,"登录端类型传值有误",array());
			}
			//用户信息
			$uid_info=ConnRedis::createConn()->hGetAll($access_token_sign.$access_token);

			if(empty($uid_info)){
				return_json(200101,"ACCESSTOKEN 无效",array());
			}else{
				return $uid_info;
			}
		}

		/**
		 * 普通用户注册|登录
		 */
		function userRegisteredLogin(){
			header("Access-Control-Allow-Origin: *");
			header('Access-Control-Allow-Headers: *');
			$param=Request::instance()->only('mobile,password,mobile_code,login_type');
			$validate = new validate\Login;
			if (!$validate->scene('userRegistered')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			//验证验证码
			//手机验证码验证
			$phone_code=ConnRedis::createConn()->get("phone_yzm".$param['mobile_code'].$param['mobile']);
			if(!$phone_code){
				return_json(200101,"验证码失效,请重新获取",array());
			}else{
				if($phone_code!=$param['mobile_code']){
					return_json(200101,"验证码错误,请确认!",array());
				}
			}

			$if_exits=Db::table("project_user")->where([['state','=','1'],['mobile','=',$param['mobile']]])->find();

			$handel="登录";
			if(empty($if_exits)){//手机号已经存在,用户实验通过验证码登录,获取token
				$handel="注册";

				$data['mobile']=$param['mobile'];
				$data['account']=$data['mobile'];

				$data['password']="";
				$data['password_md5']="";

				$data['name']="未设置";
				$data['head_img']="";
				$data['sex']="";
				$data['age']="";
				$data['record_schooling']="";
				$data['professional']="";
				$data['if_reading']="";
				$data['graduation_date']="";

				$data['company_id']="";
				$data['company_name']="";

				$data['department_id']="";
				$data['department_name']="";

				$data['job_number']="";
				$data['user_title']="";

				$data['state']="1";

				//查询系统是否开启了用户审核开关
				$user_registered_switch=Db::table("audit_action_switch")->where([['code','=','507869C5-CA58-DC71-4C68-D9C9CED808281-AC30F76B8F1'],['state','=','1']])->find();
				if(empty($user_registered_switch)){
					$data['status']="1";//正常
				}else{
					if($user_registered_switch['switch_val']=="1"){
						$data['status']="3";//待审核
					}
					if($user_registered_switch['switch_val']=="2"){
						$data['status']="1";//正常
					}
				}

				$data['sort']="1";
				$data['create_time']=fun_date(1);
				$add=Db::table("project_user")->insertGetId($data);
				$if_exits=Db::table("project_user")->where([['id','=',$add]])->find();



			}

				//判断用户账号当前状态
				if($if_exits['status']=="3"){
					return_json(200201,"用户账号待审核,请联系超级管理员,加快审核进度!",array());
				}

				if($if_exits['status']=="2"){
					return_json(200201,"用户账号被禁用,请联系超级管理员解封",array());
				}

				if($if_exits['status']=="4"){
					return_json(200201,"用户账号审核被拒绝,请联系超级管理员撤销",array());
				}


			//删除验证码
			ConnRedis::createConn()->del("phone_yzm".$param['mobile_code'].$param['mobile']);

			if($if_exits['status']=="2"){
				return_json(200101,"用户账号被禁用,请联系管理员",array());
			}
			//dump($if_exits);
			//判断用户登录端
			switch($param['login_type']){

				case 'pc'://pc官网   pc管理员
					$user_sign="diloc_project_pc_login_uid";
					$access_token_sign="diloc_project_pc_access_token";
					$refresh_token_sign="diloc_project_pc_refresh_token";
					$instructions="PC端";
					break;

				case 'app'://app
					$user_sign="diloc_project_app_login_uid";
					$access_token_sign="diloc_project_app_access_token";
					$refresh_token_sign="diloc_project_app_refresh_token";
					$instructions="APP端";
					break;

				default:
					return_json(200101,"登录端类型传值有误",array());
			}

			//dump($if_exits);
			$login_uid=$if_exits['id'];
			//生成dyna登录token
			//生成token信息
			//登录信息生成
			$if_login_user=ConnRedis::createConn()->hMGet($user_sign.$login_uid,array($access_token_sign,$refresh_token_sign));//注意此信息保存时间等于refresh_token保存时间
			if($if_login_user[$access_token_sign]==false){
				$access_token=creat_access_token($login_uid);
				$refresh_token=creat_refresh_token($login_uid);
				//将token信息保存
				ConnRedis::createConn()->hMset($user_sign.$login_uid,array($access_token_sign=>$access_token,$refresh_token_sign=>$refresh_token,"uid"=>$login_uid));
				ConnRedis::createConn()->expire($user_sign.$login_uid,3600*24*30);
				ConnRedis::createConn()->hMset($access_token_sign.$access_token,array($refresh_token_sign=>$refresh_token,"uid"=>$login_uid,"sign"=>$access_token_sign));
				ConnRedis::createConn()->expire($access_token_sign.$access_token,3600*24*15);
				ConnRedis::createConn()->hMset($refresh_token_sign.$refresh_token,array($access_token_sign=>$access_token,"uid"=>$login_uid,"sign"=>$refresh_token_sign));
				ConnRedis::createConn()->expire($refresh_token_sign.$refresh_token,3600*24*30);
				ConnRedis::createConn()->close();
			}else{
				//说明refresh_token依然存在,重新生成access_token,跟新refresh_token的保存时间,refresh_token不需要刷新
				$access_token=creat_access_token($login_uid);
				$refresh_token=$if_login_user[$refresh_token_sign];
				ConnRedis::createConn()->del($access_token_sign.$if_login_user[$access_token_sign]);
				ConnRedis::createConn()->expire($refresh_token_sign.$if_login_user[$refresh_token_sign],3600*24*30);
				ConnRedis::createConn()->expire($user_sign.$login_uid,3600*24*30);
				ConnRedis::createConn()->hMset($access_token_sign.$access_token,array($refresh_token_sign=>$if_login_user[$refresh_token_sign],"uid"=>$login_uid,"sign"=>$access_token_sign));
				ConnRedis::createConn()->expire($access_token_sign.$access_token,3600*24*15);
				//修改保存信息
				ConnRedis::createConn()->hSet($user_sign.$login_uid,$access_token_sign,$access_token);
				ConnRedis::createConn()->hSet($refresh_token_sign.$if_login_user[$refresh_token_sign],$access_token_sign,$access_token);
				ConnRedis::createConn()->close();
			}

			$member_info_data['access_token']=$access_token;
			$member_info_data['expires_in_access_token']="15 days";
			$member_info_data['refresh_token']=$refresh_token;
			$member_info_data['expires_in_refresh_token']="30 days";
			$member_info_data['account']=$if_exits['account'];
			$member_info_data['mobile']=$if_exits['mobile'];
			$member_info_data['head_img']=$if_exits['head_img'];
			$member_info_data['nick_name']=$if_exits['name'];
			$member_info_data['uid']=$if_exits['id'];


			$member_info_data['company_id']=$if_exits['company_id'];





			return_json(200200,$instructions."(验证码)".$handel."用户信息获取成功",$member_info_data);
		}


		/**
		 *普通用户账号密码登录
		 */
		function userAccountLogin(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only('account,password,login_type');
			$validate = new validate\Login;
			if (!$validate->scene('userAccountLogin')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}

			$where[]=['account','=',$param['account']];
			$where[]=['password_md5','=',md5($param['password'])];

			$where[]=['state','=','1'];
			$if_exits=Db::table("project_user")->where($where)->find();
			if(!empty($if_exits)){
				if($if_exits['status']=="2"){
					return_json(200101,"用户账号被禁用,请联系管理员",array());
				}
				if($if_exits['status']=="3"){
					return_json(200101,"用户账号等待审核,请联系管理员加快审核进度",array());
				}
				if($if_exits['status']=="4"){
					return_json(200101,"用户账号审核未通过,请联系管理员撤销",array());
				}
				$handel="登录";
				//判断用户登录端
				switch($param['login_type']){

					case 'pc'://pc官网   pc管理员
						$user_sign="diloc_project_pc_login_uid";
						$access_token_sign="diloc_project_pc_access_token";
						$refresh_token_sign="diloc_project_pc_refresh_token";
						$instructions="PC端";
						break;

					case 'app'://app
						$user_sign="diloc_project_app_login_uid";
						$access_token_sign="diloc_project_app_access_token";
						$refresh_token_sign="diloc_project_app_refresh_token";
						$instructions="APP端";
						break;

					default:
						return_json(200101,"登录端类型传值有误",array());
				}

				//dump($if_exits);
				$login_uid=$if_exits['id'];
				//生成dyna登录token
				//生成token信息
				//登录信息生成
				$if_login_user=ConnRedis::createConn()->hMGet($user_sign.$login_uid,array($access_token_sign,$refresh_token_sign));//注意此信息保存时间等于refresh_token保存时间
				if($if_login_user[$access_token_sign]==false){
					$access_token=creat_access_token($login_uid);
					$refresh_token=creat_refresh_token($login_uid);
					//将token信息保存
					ConnRedis::createConn()->hMset($user_sign.$login_uid,array($access_token_sign=>$access_token,$refresh_token_sign=>$refresh_token,"uid"=>$login_uid));
					ConnRedis::createConn()->expire($user_sign.$login_uid,3600*24*30);
					ConnRedis::createConn()->hMset($access_token_sign.$access_token,array($refresh_token_sign=>$refresh_token,"uid"=>$login_uid,"sign"=>$access_token_sign));
					ConnRedis::createConn()->expire($access_token_sign.$access_token,3600*24*15);
					ConnRedis::createConn()->hMset($refresh_token_sign.$refresh_token,array($access_token_sign=>$access_token,"uid"=>$login_uid,"sign"=>$refresh_token_sign));
					ConnRedis::createConn()->expire($refresh_token_sign.$refresh_token,3600*24*30);
					ConnRedis::createConn()->close();
				}else{
					//说明refresh_token依然存在,重新生成access_token,跟新refresh_token的保存时间,refresh_token不需要刷新
					$access_token=creat_access_token($login_uid);
					$refresh_token=$if_login_user[$refresh_token_sign];
					ConnRedis::createConn()->del($access_token_sign.$if_login_user[$access_token_sign]);
					ConnRedis::createConn()->expire($refresh_token_sign.$if_login_user[$refresh_token_sign],3600*24*30);
					ConnRedis::createConn()->expire($user_sign.$login_uid,3600*24*30);
					ConnRedis::createConn()->hMset($access_token_sign.$access_token,array($refresh_token_sign=>$if_login_user[$refresh_token_sign],"uid"=>$login_uid,"sign"=>$access_token_sign));
					ConnRedis::createConn()->expire($access_token_sign.$access_token,3600*24*15);
					//修改保存信息
					ConnRedis::createConn()->hSet($user_sign.$login_uid,$access_token_sign,$access_token);
					ConnRedis::createConn()->hSet($refresh_token_sign.$if_login_user[$refresh_token_sign],$access_token_sign,$access_token);
					ConnRedis::createConn()->close();
				}

				$member_info_data['access_token']=$access_token;
				$member_info_data['expires_in_access_token']="15 days";
				$member_info_data['refresh_token']=$refresh_token;
				$member_info_data['expires_in_refresh_token']="30 days";
				$member_info_data['account']=$if_exits['account'];
				$member_info_data['mobile']=$if_exits['mobile'];
				$member_info_data['head_img']=$if_exits['head_img'];
				$member_info_data['nick_name']=$if_exits['name'];
				$member_info_data['uid']=$if_exits['id'];



				$member_info_data['company_id']=$if_exits['company_id'];

				return_json(200200,$instructions."(账号)".$handel."用户信息获取成功",$member_info_data);

			}else{
				return_json(200204,"账号密码错误",array());
			}

		}


		/**
		 * 退出登录
		 * app|pc后台|pc官网|超级管理员 通用
		 */
		function personLogout(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only('access_token,login_type');
			$validate = new validate\Login;
			if (!$validate->scene('personLogout')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			$uid_info=$this->distinguish_end($param['login_type'],$param['access_token']);

			switch($param['login_type']){
				case 'app':
					$access_token_sign="diloc_project_app_access_token";
					$access_token_login_uid="diloc_project_app_login_uid";
					$refresh_token_sign="diloc_project_app_refresh_token";
					break;
				case 'pc':
					$access_token_sign="diloc_project_pc_access_token";
					$access_token_login_uid="diloc_project_pc_login_uid";
					$refresh_token_sign="diloc_project_pc_refresh_token";
					break;
				case 'dyna':
					$access_token_sign="diloc_dyna_access_token";
					$access_token_login_uid="diloc_dyna_login_uid";
					$refresh_token_sign="diloc_dyna_refresh_token";
					break;
				default:
					return_json(200101,"登录端类型传值有误",array());
			}

			ConnRedis::createConn()->del($access_token_sign.$param['access_token']);
			ConnRedis::createConn()->del($access_token_login_uid.$uid_info['uid']);
			ConnRedis::createConn()->del($refresh_token_sign.$uid_info[$refresh_token_sign]);

			return_json(200200,"退出成功",array());

		}


		/**
		 *用户申请加入企业(app_id,项目)
		 *会判断用户基本信息的企业信息是否正常
		 */
		function userApplyProject(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only('access_token,app_id,login_type');
			$validate = new validate\Login;
			if (!$validate->scene('userApplyProject')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			//验证token
			$uid_info=$this->distinguish_end($param['login_type'],$param['access_token']);
			//dump($uid_info);
			//查询指定的项目配置信息
			$project_info=Db::table("project")->where([['id','=',$param['app_id']]])->field("status,state,name")->find();
			if(empty($project_info)){
				return_json(200101,"项目不存在,请确认APPID真确性",array());
			}
			if($project_info['state']=="2"){
				return_json(200101,"项目信息不存在,已经被删除",array());
			}
			switch($project_info['status']){
				case '3':
					return_json(200101,"项目已经过期,请联系管理员",array());
					break;
				case '4':
					return_json(200101,"项目被禁用,请联系管理员",array());
					break;
				case '5':
					return_json(200101,"项目正在审核,如需加快进度,请联系超级管理员",array());
					break;

			}
			$project_config_info=Db::table("project_config")->where([['app_id','=',$param['app_id']],['state','=','1'],['status','=','1']])->field("registry_set,registered_fields,db_name")->find();
			if(empty($project_config_info)){
				return_json(200101,"项目配置信息未设置",array());
			}

			//dump($project_config_info);

			//判断项目配置是否可以直接加入
			switch($project_config_info['registry_set']){
				//	"registry_set":"1允许注册(加入)  2允许注册审核(加入需要审核)   3不允许注册(不允许加入)",
				case '1':
					$data['status']="2";
					$sign1="加入成功";
					break;
				case '2':
					$data['status']="1";
					$sign1="关系建立成功,待审核";
					break;
				case '3':
					return_json(200101,"指定项目不允许用户加入,请确认",array());
					break;
				default:
					return_json(200101,"项目未设置注册申请加入配置",array());
			}

			$user_info=Db::table("project_user")->where([['id','=',$uid_info['uid']],['state','=','1'],['status','=','1']])->find();


			//dump($user_info);
			//dump($project_config_info['registered_fields']);
			//判断项目配置用户必须完善的字段
			if(!empty($project_config_info['registered_fields'])){
				//dump($project_config_info['registered_fields']);
				if(empty($user_info)){
					return_json(200101,"账号信息变动,请重新登录",array());
				}
				//dump($user_info);



					//dump($project_config_info['registered_fields']);



					if(!empty($project_config_info['registered_fields'])){
						$project_userfields_group_all_fields=array();
						foreach($project_config_info['registered_fields'] as $key=>$value){

							$project_userfields_group_fields=Db::table("project_userfields_group")->where([['group_code','=',$value],['state','=','1']])->field("fields")->find();
							if(!empty($project_userfields_group_fields)&&!empty($project_userfields_group_fields['fields'])){
								foreach($project_userfields_group_fields['fields'] as $key1=>$value1){

									array_push($project_userfields_group_all_fields,$value1);
								}
							}

						}

						if(!empty($project_userfields_group_all_fields)){
							foreach($project_userfields_group_all_fields as $key=>$value) {
								if (!isset($user_info[$value]) || empty($user_info[$value])) {
									return_json(200101, "请先完善项目制定的用户必须设置的基本信息".$value,array());
								}
							}
						}


					}


			}

			$data['status'] = '1';//待审核

		/*	//判断项目用户加入羡慕是否需要审核

			$app_registry_set=Db::table("project_config")->where([['app_id','=',$param['app_id']]])->field("registry_set")->find();
			if(empty($app_registry_set)){
				return_json(200101,"目标项目部未设置加入规则,请联系管理员",array());
			}


			if(!empty($app_registry_set)&&isset($app_registry_set['registry_set'])){


				if($app_registry_set['registry_set']=="2"){
					$data['status'] = '1';//待审核
				}

				if($app_registry_set['registry_set']=="3"){
					return_json(200101,"目标项目不允许用户加入",array());
				}

				if($app_registry_set['registry_set']=="1"){
					$data['status'] = '2';//直接审核通过

					//创建用户在项目中的账户信息
					$app_project_account_exits=Db::table("user_account")->where([['app_id','=',$param['app_id']],['uid','=',$uid_info['uid']],['state','=','1']])->find();
					if(empty($app_project_account_exits)){
						$user_account_data['app_id']=$param['app_id'];
						$user_account_data['uid']=$uid_info['uid'];
						$user_account_data['account_balance']=0;
						$user_account_data['all_overdraft']=1000;//用户在本项目下默认可透支额度
						$user_account_data['used_overdraft']=0;
						$user_account_data['all_pay_money']=0;
						$user_account_data['appoint_order_money']=0;
						$user_account_data['training_money']=0;
						$user_account_data['state']="1";
						$user_account_data['status']="1";
						$user_account_data['sort']="1";
						$user_account_data['create_time']=fun_date(1);

						Db::table("user_account")->insert($user_account_data);
					}
				}
			}*/


			$data['account']=$user_info['account'];
			$data['name']=$user_info['name'];
			$data['job_number']=$user_info['job_number'];
			$data['department_name']=$user_info['department_name'];
			$data['department_id']=$user_info['department_id'];
			$data['member_id']=$uid_info['uid'];
			$data['app_id']=$param['app_id'];
			$data['state']="1";
            $data['sort']="1";
            $data['if_admin']="3";
			$data['create_time']=fun_date(1);

			//是否重复加入
			$if_exits_apply=Db::table("project_user_relation")->where([['state','=','1'],['member_id','=',$uid_info['uid']],['app_id','=',$param['app_id']],['status','in','1,2']])->find();
			if(!empty($if_exits_apply)){
				if($if_exits_apply['status']=="1"){
					return_json(200101,"已经申请加入此项目,正在审核",array());
				}
				if($if_exits_apply['status']=="2"){
					return_json(200101,"已经加入此项目,不能重复加入",array());
				}
			}



			//判断主项目,次项目
			$apply_pro_user_sign=Db::table("project_user_relation")
                ->where([['member_id','=',$uid_info['uid']],['state','=','1'],['status','in','1,2']])
                ->find();
			if(!empty($apply_pro_user_sign)){
				$data['sign']="2";
				$sign2="非主项目";
			}else{
				$data['sign']="1";
				$sign2="主项目";
			}
			//dump($data);

			$add=Db::table("project_user_relation")->insertGetId($data);
            AuditMess::audit_list_sort($project_config_info['db_name'],$project_info['id'],'D9823B82-EDB1-B0C5-A221-F2D5E1800445-0C693EDA32',$add,$param);

			if($add){
			/*	//创建用户在此项目中的账户信息
				$user_pro_account['app_id']=$param['app_id'];
				$user_pro_account['uid']=$uid_info['uid'];
				$user_pro_account['account_balance']=0;
				$user_pro_account['all_overdraft']=0;
				$user_pro_account['used_overdraft']=0;
				$user_pro_account['all_pay_money']=0;
				$user_pro_account['appoint_order_money']=0;
				$user_pro_account['training_money']=0;
				$user_pro_account['state']="1";
				$user_pro_account['status']="1";
				$user_pro_account['sort']="1";
				$user_pro_account['create_time']=fun_date(1);

				Db::table("user_account")->insertGetId($user_pro_account);*/

				return_json(200200,"用户加入".$project_info['name']."(".$sign2."),".$sign1,array());
			}else{
				return_json(200204,"用户加入".$project_info['name']."(".$sign2."),失败",array());
			}
		}

		/**
		 * 用户可加入的企业(项目,app)列表--过滤掉已经加入的
		 */
		function couldApplyProjectList(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only('access_token,login_type');
			$validate = new validate\Login;
			if (!$validate->scene('couldApplyProjectList')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			$user_info=$this->distinguish_end($param['login_type'],$param['access_token']);
			//dump($user_info);

			//查询我已经加入的项目ppid列表
			$intoed_project_list=Db::table("project_user_relation")->where([['member_id','=',$user_info['uid']],['status','in','1,2'],['state','=','1']])->field("app_id")->order('sort','asc')->select();

			if(!empty($intoed_project_list)){
				$intoed_project_list_arr=array_column($intoed_project_list,'app_id');
			}else{
				$intoed_project_list_arr=array();
			}
			//dump($intoed_project_list_arr);

			//查询所有项目列表
			$project_list=Db::table("project")->where([['state','=','1'],['status','in','1,2']])->field("name,sys_number")->order("sort",'asc')->select();

			if(!empty($project_list)&&!empty($intoed_project_list_arr)){
				$project_list2=array_column($project_list,NULL,'id');
				foreach($intoed_project_list_arr as $key=>$value){
					unset($project_list2[$value]);
				}
				$project_list=array_values($project_list2);
			}
			return_json(200200,"用户可选要加入的项目列表获取成功",$project_list);
		}


		/**
		 * 判断当前用户是否是指定项目的管理员
		 * 页面跳转按钮是否显示
		 */
		function isProjectAdmin(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only('access_token,app_id');
			$validate = new validate\Login;
			if (!$validate->scene('isProjectAdmin')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			$user_info=$this->distinguish_end('pc',$param['access_token']);
			//dump($user_info);
			$where[]=['user_id','=',$user_info['uid']];
			$where[]=['app_id','=',$param['app_id']];
			$where[]=['state','=',"1"];
			$where[]=['status','=','1'];

			$if_exits=Db::table("project_admin_relation")->where($where)->find();
            if($if_exits)
            {
                return_json(200200,"是项目管理员",array());

            }

            $user_role = Db::table($user_info['db_name'].'.user_role_group')
                ->where('member_id',$user_info['uid'])
                ->where('state','1')
                ->where('status','1')
                ->find();
            if(empty($user_role))
            {
                return_json(200204,"不是项目管理员",array());
            }

            return_json(200200,"是项目管理员",array());


		}

		/**
		 * 用户可加入的企业信息列表
		 */
		function userCouldApplyCompanyList(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only('access_token,keywords,page,size,login_type');
			$validate = new validate\Login;
			if (!$validate->scene('userCouldApplyCompanyList')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			//验证token
			$this->distinguish_end($param['login_type'],$param['access_token']);

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
			$where[]=['status','=','2'];
			$company_list=Db::table("company")->where($where)->field("id,name,code")->order("sort",'asc')->limit(($page-1)*$size,$size)->select();
			if(empty($company_list)){
				return_json(200200,"可加入的企业列表为空",array());
			}else{
				return_json(200200,"可加入的企业列表获取成功",$company_list);
			}
		}

		/**
		 * 用户可加入的部门信息了列表
		 */
		function userCouldApplyDepartmentList(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only('access_token,company_id,keywords,page,size,login_type');
			$validate = new validate\Login;
			if (!$validate->scene('userCouldApplyDepartmentList')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			//验证token
			$this->distinguish_end($param['login_type'],$param['access_token']);

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

			$where[]=['company_id','=',$param['company_id']];
			$where[]=['state','=','1'];
			$where[]=['status','=','1'];

			$department_list=Db::table("department")->where($where)->field("id,name,code")->order("sort",'asc')->limit(($page-1)*$size,$size)->select();
			if(empty($department_list)){
				return_json(200200,"可加入的部门列表为空",array());
			}else{
				return_json(200200,"可加入的部门列表获取成功",$department_list);
			}
		}

		/**
		 * 用于设置自己的所在企业基本信息
		 */
		function userSetselfCompanyId(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only('access_token,company_name,login_type');
			$validate = new validate\Login;
			if (!$validate->scene('userSetselfCompanyId')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			//验证token
			$user_info=$this->distinguish_end($param['login_type'],$param['access_token']);
			$if_exits=Db::table("company")->where([['name','=',$param['company_name']],['state','=','1'],['status','in','1,2']])->find();
			//dump($if_exits);
			if(!empty($if_exits)){
				return_json(200101,"企业信息名称被占用",array());
			}

			$if_exits=Db::table("company")->where([['admin_id','=',$user_info['uid']],['state','=','1'],['status','in','1,2']])->find();
			//dump($if_exits);
			if(!empty($if_exits)){
				return_json(200101,"用户不能创建1个以上的企业",array());
			}

			$data['new_name']="";
			$data['name']=$param['company_name'];
			$data['admin_id']=array($user_info['uid']);
			$data['state']="1";
			$data['status']="1";
			$data['code']=create_uuid(time());
			$data['sort']="1";
			$data['create_time']=fun_date(1);

			$add=Db::table("company")->insertGetId($data);
			if($add){
				return_json(200200,"企业信息创建成功,等待平台审核",array('new_id'=>$add));
			}else{
				return_json(200204,"企业信息创建失败",array());
			}

		}

		/**
		 * 用户设置自己的所在部门基本信息
		 */
		function userSetselfDepartmentId(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only('access_token,company_id,department_name,login_type');
			$validate = new validate\Login;
			if (!$validate->scene('userSetselfDepartmentId')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			$user_info=$this->distinguish_end($param['login_type'],$param['access_token']);

			$if_exits=Db::table("department")->where([['state','=','1'],['status','=','1'],['name','=',$param['department_name']]])->find();

			if(!empty($if_exits)){
				return_json(200101,"部门名称被占用,请重新设置",array());
			}
			$data['company_id']=$param['company_id'];
			$data['name']=$param['department_name'];
			$data['code']=create_uuid(time());
			$data['state']="1";
			$data['status']="1";
			$data['sort']="1";
			$data['create_time']=fun_date(1);
			$add=Db::table("department")->insertGetId($data);

			if($add){
				return_json(200200,"部门信息创建成功",array("new_id"=>$add));
			}else{
				return_json(200204,"部门信息创建失败",array());
			}
		}

		/**
		 * 用户注册系统,系统设置需要完善的字段列表
		 */
		function regCompletionFields(){
			header("Access-Control-Allow-Origin: *");
			$list=Db::table("sys_userinfo_fields")->where([['state','=','1'],['status','=','1']])->field("userfields_group_name,userfields_group_code")->order("sort",'asc')->select();
			if(empty($list)){
				return_json(200204,"系统当前不需要用户完善任何个人信息",array());
			}else{
				return_json(200200,"系统当前需要用户完善以下分组的个人信息",$list);
			}
		}

		/**
		 * 判断用户是否已经完善了全部系统要求需要的完善的字段
		 */
		function ifPerfectAllFields(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only('access_token,login_type');
			$validate = new validate\Login;
			if (!$validate->scene('ifPerfectAllFields')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			$user_info=$this->distinguish_end($param['login_type'],$param['access_token']);
			//dump($user_info);
			$sys_com_fields=Db::table("sys_userinfo_fields")->where([['state','=','1'],['status','=','1']])->field("userfields_group_code")->order("sort",'asc')->select();
			//dump($sys_com_fields);
			if(empty($sys_com_fields)){
				return_json(200200,"已经全部完善(#001)",array());
			}else{
				//注册用户现有的用户信息
				$reg_user_info=Db::table("project_user")->where([['id','=',$user_info['uid']]])->find();
				//dump($reg_user_info);
				//return_json(200200,"已经全部完善(#002)",array());
				$must_fields_arr=array();//系统规定必须完善的字段
				foreach($sys_com_fields as $key=>$value){
					$group_fields_list=Db::table("project_userfields_group")->where([['must_fillin','=','1'],['state','=','1'],['status','=','1'],['group_code','=',$value['userfields_group_code']]])->field("fields")->find();
					if(!empty($group_fields_list)){
						foreach($group_fields_list['fields'] as $key1=>$value1){
							array_push($must_fields_arr,$value1);
						}
					}
				}
				//dump($must_fields_arr);
				if(empty($must_fields_arr)){
					return_json(200200,"已经全部完善(#002)",array());
				}else{
					foreach($must_fields_arr as $key=>$value){
						if(!isset($reg_user_info[$value])||empty($reg_user_info[$value])||$reg_user_info[$value]==""){
							return_json(200204,"用户没有完善系统设置的必填用户信息字段(#001)",array());
						}
						if(isset($reg_user_info['name'])&&$reg_user_info['name']=="未设置"){
							return_json(200204,"用户没有完善系统设置的必填用户信息字段(#002)",array());
						}
					}

					return_json(200200,"已经全部完善(#003)",array());
				}

			}
		}

		/**
		 * 注册时,用户完善的数据提交
		 */
		function userCompletionFieldSubmit(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only('access_token,login_type,fields');
			$validate = new validate\Login;
			if (!$validate->scene('userCompletionFieldSubmit')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			$user_info=$this->distinguish_end($param['login_type'],$param['access_token']);

			if(!isset($param['fields'])||empty($param['fields'])){
				return_json(200101,"要完善的字段信息未传",array());
			}
			//用户提交的信息
			$fields_arr=json_decode(html_entity_decode($param['fields']),true);
			if(empty($fields_arr)){
				return_json(200101,"要完善的字段信息未传",array());
			}
			$fields_array=array();
			foreach($fields_arr as $key=>$value){
				if(!isset($value['field'])||!isset($value['val'])){
					return_json(200101,"必须完善字段传参结构错误",array());
				}else{
					$fields_array[$key]['field']=$value['field'];
					$fields_array[$key]['val']=$value['val'];
				}
			}
			$fields_array2 = array_column($fields_array,NULL,'field');
			//dump($fields_array2);

			//系统需要的必传字段
			$sys_com_fields=Db::table("sys_userinfo_fields")->where([['state','=','1'],['status','=','1']])->field("userfields_group_code")->order("sort",'asc')->select();
			$must_fields_arr=array();//系统规定必须完善的字段
			foreach($sys_com_fields as $key=>$value){
				$group_fields_list=Db::table("project_userfields_group")->where([['must_fillin','=','1'],['state','=','1'],['status','=','1'],['group_code','=',$value['userfields_group_code']]])->field("fields")->find();
				if(!empty($group_fields_list)){
					foreach($group_fields_list['fields'] as $key1=>$value1){
						array_push($must_fields_arr,$value1);
					}
				}
			}
			if(empty($must_fields_arr)){
				return_json(200204,"系统不需要用户完善任何用户描述信息",array());
			}
			//dump($must_fields_arr);

			foreach($must_fields_arr as $key=>$value){

				if(!isset($fields_array2[$value])||!isset($fields_array2[$value]['val'])||empty($fields_array2[$value]['val'])||$fields_array2[$value]['val']==""){
					return_json(200101,"用户传值缺少必传字段值".$value,array());
				}
				if(isset($fields_array2['password'])&&isset($fields_array2['password']['val'])){
					$fields_array2['password_md5']['field']="password_md5";
					$fields_array2['password_md5']['val']=MD5($fields_array2['password']['val']);
				}
			}
			//dump($fields_array2);

			$fields_array_edit=array();
			foreach($fields_array2 as $key1=>$value1){
				$fields_array_edit[$value1['field']]=$value1['val'];
			}

			$edit=Db::table("project_user")->where([['id','=',$user_info['uid']]])->update($fields_array_edit);
			if($edit!==false){
				return_json(200200,"完善信息成功",array());
			}else{
				return_json(200204,"完善信息失败,请重试",array());
			}
		}


		/**
		 *用户加入项目时,需要完善的用户个人描述字段分组
		 */
		function applyProjectFieldgroupList(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only('access_token,login_type,app_id');
			$validate = new validate\Login;
			if (!$validate->scene('applyProjectFieldgroupList')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			$user_info=$this->distinguish_end($param['login_type'],$param['access_token']);

			$where[]=['app_id','=',$param['app_id']];
			$where[]=['state','=','1'];

			$field_group=Db::table("project_config")->where($where)->field("registered_fields")->find();
			if(empty($field_group)){
				return_json(200200,"加入项目申请不需要完善任何用户描述字段(#001)",array());
			}else{
				if(empty($field_group['registered_fields'])){
					return_json(200200,"加入项目申请不需要完善任何用户描述字段(#002)",array());
				}else{


					$list2=array();//项加入需要晚上的字段分组

					//dump($field_group);
					$field_group_code_str=implode(",",$field_group['registered_fields']);

					$list=Db::table("project_userfields_group")->where([['group_code','in',$field_group_code_str]])->field("name,group_code")->order("sort","asc")->select();
					/*echo  "项目需要完善的";
					dump($list);*/
					if(!empty($list)){

						//判断系统注册时需要的注册的字段分组
						$sys_userinfo_fields_arr=Db::table("sys_userinfo_fields")->where([['state','=','1'],['status','=','1']])->field("userfields_group_code,userfields_group_name")->select();

						/*echo "系统需要完善的";
						dump($sys_userinfo_fields_arr);*/
						if(!empty($sys_userinfo_fields_arr)){
							$sys_userinfo_fields_arr2=array_column($sys_userinfo_fields_arr,NULL,'userfields_group_code');
							/*echo "系统需要完善的1";
							dump($sys_userinfo_fields_arr2);*/
							foreach($list as $key=>$value){
								if(!isset($sys_userinfo_fields_arr2[$value['group_code']])){
										array_push($list2,$value);
								}
							}
						}
						return_json(200204,"用户信息描述字段分组获取成功",$list2);
					}else{
						return_json(200200,"加入项目申请不需要完善任何用户描述字段(#003)",array());
					}
				}
			}

		}


		/**
		 * 判断是否完善了要加入项目需要完善的所有字段分组
		 */
		function applyProjectFieldgroupPerfect(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only('access_token,login_type,app_id');
			$validate = new validate\Login;
			if (!$validate->scene('applyProjectFieldgroupList')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			$user_info=$this->distinguish_end($param['login_type'],$param['access_token']);

			$user_allinfo=Db::table("project_user")->where([['id','=',$user_info['uid']]])->find();

			$where[]=['app_id','=',$param['app_id']];
			$where[]=['state','=','1'];

			$field_group=Db::table("project_config")->where($where)->field("registered_fields")->find();
			if(empty($field_group)){
				return_json(200200,"加入项目申请不需要完善任何用户描述字段(#001)",array());
			}else{
				if(empty($field_group['registered_fields'])){
					return_json(200200,"加入项目申请不需要完善任何用户描述字段(#002)",array());
				}else{

					//dump($field_group);
					$field_group_code_str=implode(",",$field_group['registered_fields']);

					$list=Db::table("project_userfields_group")->where([['group_code','in',$field_group_code_str]])->field("name,group_code")->order("sort","asc")->select();
					/*echo  "项目需要完善的";
					dump($list);*/
					if(!empty($list)){

						$list_idlist_str=implode(",",array_column($list,'group_code'));
						//查询分组有哪些字段
						$project_userfields_group_fields=Db::table("project_userfields_group")->where([['group_code','in',$list_idlist_str],['state','=','1'],['status','=','1']])->field("fields,group_code")->select();
						//echo   "分组中的字段";
						//dump($project_userfields_group_fields);
						$list22= array_column($list,NULL,'group_code');
						//echo  "加入项目需要的分组";
						//dump($list22);

						$project_userfields_group_fields_arr=array();
						foreach($project_userfields_group_fields as $key=>$value){
							if(isset($value['fields'])&&!empty($value['fields'])){
								foreach($value['fields'] as $key1=>$value1){

									if(!isset($user_allinfo[$value1])||empty($user_allinfo[$value1])||$user_allinfo[$value1]=="未设置"){
										array_push($project_userfields_group_fields_arr,$list22[$value['group_code']]);
										break;
									}

								}
							}
						}

						//dump($project_userfields_group_fields_arr);
						if(empty($project_userfields_group_fields_arr)){
							return_json(200200,"用户已经晚上了所有信息,可以加入项目",array());
						}else{
							return_json(200204,"用户存在信息不完善,不能加入项目",$project_userfields_group_fields_arr);
						}
						//return_json(200204,"用户信息描述字段分组获取成功",array());
					}else{
						return_json(200200,"用户不需要完善任何信息,可以加入项目",array());
					}
				}
			}

		}




		/**
		 * 获取用户信息,用于完善需要完善的数据展示
		 */
		function userInfoShow(){

			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only('access_token,login_type,app_id');
			$validate = new validate\Login;
			if (!$validate->scene('applyProjectFieldgroupList')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}
			$user_info=$this->distinguish_end($param['login_type'],$param['access_token']);

			$user_infoshow=Db::table("project_user")->where([['id','=',$user_info['uid']]])->field("name,head_img,sex,age,record_schooling,professional,professional,if_reading,graduation_date,company_id,company_name,department_id,department_name,job_number,user_title")->find();

			if(empty($user_infoshow)){
				return_json(200204,"用户信息不存在",array());
			}else{
				return_json(200200,"用户信息获取成功",$user_infoshow);
			}

		}


	}




