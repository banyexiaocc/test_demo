<?php
	/**
	 * Created by PhpStorm.
	 * User: fu.hy
	 * Date: 2020/2/24
	 * Time: 15:03
	 */
	namespace app\entityresconninter\controller\pcapp\v1;
	use think\Controller;
	use think\facade\Request;//数据接收-静态代理
	use think\Db;
	use app\entityresconninter\validate;
	use app\auth\controller\admin\v1\Authuser;

	class Resourceconninter extends Authuser
	{

		//方法已经可用
		public function __construct(){
			parent::__construct();
		}

		/**
		 * 为实体资源添加参数传感器 可批量
		 */
		function enResourceAddsensor(){
			header("Access-Control-Allow-Origin: *");
			$param=Request::instance()->only("res_id,sensor_idlist");
			$validate = new validate\Resourceconninter;
			if (!$validate->scene('enResourceAddsensor')->check($param)) {
				return_json(200101,$validate->getError(),array());
			}











		}


	}