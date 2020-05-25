<?php
/**
 * Created by PhpStorm.
 * User: fu.hy
 * Date: 2019/4/9
 * Time: 10:52
 */
namespace app\tool\controller\app\v1;
use tests\RotateTest;
use think\Db;
use think\facade\Request;
use app\facade\ConnRedis;
use app\tool\validate;
use think\route\Resource;

class Tool
{
    function __construct()
    {
        //静态代理类绑定,redis连接类
        \think\Facade::bind('app\facade\ConnRedis', 'app\common\ConnRedis');
    }

    /**
     * 发送手机验证码
     */
    function sendVerificationCode(){
        header("Access-Control-Allow-Origin: *");
        $param= Request::instance()->only('mobile,template_id,save_seconds,find_pwd');

        //表单数据验证
        $validate = new validate\Tool;
        if (!$validate->scene('send_message_code')->check($param)) {
            return_json(200101,$validate->getError(),array());
        }

		//是用于找回密码
        if(isset($param['find_pwd'])&&$param['find_pwd']=="true"){
        	//判断手机号是否注册
			$if_exits_mobile=Db::table("project_user")->where([['state','=','1'],['status','in','1,2'],['mobile','=',$param['mobile']]])->find();
			if(empty($if_exits_mobile)){
				return_json(200101,"手机号未注册,无法使用验证码找回密码,请先注册",array());
			}
		}


        //dump($phone_config_mess);
        if(ConnRedis::createConn()->get("phone_send_time_pass".$param['mobile'])){
            return_json(200204,'操作频率过高,请'.$param['save_seconds']."秒之后重试!",array());
        }

        //生成验证码
        $mobile_code=create_ver_code();
        if(!isset($param['save_seconds'])||$param['save_seconds']==""){
            $param['save_seconds']=300;
        }
        $datas=array($mobile_code,$param['save_seconds']/60);
        ConnRedis::createConn()->set("phone_yzm".$mobile_code.$param['mobile'],$mobile_code,(int)$param['save_seconds']);
        //dump($a);
        ConnRedis::createConn()->set("phone_send_time_pass".$param['mobile'],"exist",20);

        $res=sms_mobile_send($param['template_id'],$datas,$param['mobile']);
        //dump($res);
        if($res=="2"){

            return_json(200200,'验证码发送成功!',array());
        }else{
            return_json(200204,'验证码发送过于频繁!',array());
        }
    }

	/**
	 * 判断手机验证码是否正确
	 */
	function validMobileCode(){
		header("Access-Control-Allow-Origin: *");
		$param= Request::instance()->only('mobile,mobile_code');

		//表单数据验证
		$validate = new validate\Tool;
		if (!$validate->scene('validMobileCode')->check($param)) {
			return_json(200101,$validate->getError(),array());
		}

		//手机验证码验证
		$phone_code=ConnRedis::createConn()->get("phone_yzm".$param['mobile_code'].$param['mobile']);
		if(!$phone_code){
			return_json(200101,"验证码失效,请重新获取",array());
		}else{
			if($phone_code!=$param['mobile_code']){
				return_json(200101,"验证码错误,请确认!",array());
			}else{

				//查找用户数据编号
				$user_info=Db::table("project_user")->where([['mobile','=',$param['mobile']],['state','=','1']])->find();

				return_json(200200,"验证码正确!",array("uid"=>$user_info['id']));
			}
		}


	}




	/**
	 * 用户登录密码找回
	 */
	function setNewPwd(){
		header("Access-Control-Allow-Origin: *");
		$param= Request::instance()->only('aim_uid,pwd,repwd,mobile_code,mobile');

		//表单数据验证
		$validate = new validate\Tool;
		if (!$validate->scene('setNewPwd')->check($param)) {
			return_json(200101,$validate->getError(),array());
		}

		//手机验证码验证
		$phone_code=ConnRedis::createConn()->get("phone_yzm".$param['mobile_code'].$param['mobile']);
		if(!$phone_code){
			return_json(200101,"验证码失效,请重新获取",array());
		}else{
			if($phone_code!=$param['mobile_code']){
				return_json(200101,"验证码错误,请确认!",array());
			}
		}

		if($param['pwd']!=$param['repwd']){
			return_json(200101,"重复密码错误",array());
		}
		$data['password']=$param['pwd'];
		$data['password_md5']=md5($param['repwd']);


		if(isset($param['aim_uid'])&&!empty($param['aim_uid'])){
			$edit=Db::table("project_user")->where([['id','=',$param['aim_uid']]])->update($data);
		}else{
			//通过手机号查询用户编号
			$user_info=Db::table("project_user")->where([['mobile','=',$param['mobile']],['state','=','1']])->find();
			$edit=Db::table("project_user")->where([['id','=',$user_info['id']]])->update($data);
		}
		if($edit!==false){
			return_json(200200,"密码找回成功",array());
		}else{
			return_json(200204,"密码找回失败",array());
		}

	}


    /**
     * access_token刷新
     */
    function verifyToken(){
        header("Access-Control-Allow-Origin: *");
        $param= Request::instance()->only('access_token,refresh_token');
        //表单数据验证
        $validate = new validate\Tool;
        if (!$validate->scene('verify_token')->check($param)) {
            return_json(200101,$validate->getError(),array());
        }

        $access_token=$param['access_token'];
        $refresh_token=$param['refresh_token'];

        $if_re_token_exit=ConnRedis::createConn()->hGetAll('refresh_token'.$refresh_token);
        if(empty($if_re_token_exit)){
            return_json("200414","refresh_token消失,请重新登录(#001)",(object)array());
        }
        $if_ac_token_exit=ConnRedis::createConn()->hGetAll('access_token'.$access_token);
        //dump($if_ac_token_exit);
        //通过refresh_token,
        $re_token_info=ConnRedis::createConn()->hGetAll('refresh_token'.$refresh_token);

        //dump($re_token_info);
        if(!isset($re_token_info['uid'])){
            return_json("200414","refresh_token消失,请重新登录(#002)",(object)array());
        }

        $access_token_new=creat_access_token($re_token_info['uid']);
        //dump($if_ac_token_exit);
        if(empty($if_ac_token_exit)){//access_token消失
            if(empty($re_token_info)){
                return_json(200204,"refresh_token消失,请重新登录",(object)array());
            }else{
                //若果存在,刷新access_token,并且置换refresh_token
                //生成新的access_token和refresh_token
                $refresh_token_new=creat_refresh_token($re_token_info['uid']);
                //修改login_uid信息
                ConnRedis::createConn()->hSet('login_uid'.$re_token_info['uid'],'access_token',$access_token_new);
                ConnRedis::createConn()->hMset('refresh_token'.$refresh_token_new,array("access_token" => $access_token_new,"uid" =>$re_token_info['uid'],"sign"=>"refresh_token"));
                ConnRedis::createConn()->expire("refresh_token".$refresh_token_new,3600*24*30);
                ConnRedis::createConn()->hSet('login_uid'.$re_token_info['uid'],'refresh_token',$refresh_token_new);
                ConnRedis::createConn()->expire("login_uid".$re_token_info['uid'],3600*24*30);
                ConnRedis::createConn()->hMset('access_token'.$access_token_new,array("refresh_token" => $refresh_token_new,"uid" =>$re_token_info['uid'],"sign"=>"access_token"));
                ConnRedis::createConn()->expire("access_token".$access_token_new,3600*24*15);
                ConnRedis::createConn()->del('refresh_token'.$refresh_token);
                $sign_access_token=$access_token_new;
                $sign_refresh_token=$refresh_token_new;
            }
        }else{
            //refresh_token 不变  access_token 新值
            ConnRedis::createConn()->hSet('login_uid'.$re_token_info['uid'],'access_token',$access_token_new);
            ConnRedis::createConn()->expire("login_uid".$re_token_info['uid'],3600*24*30);
            ConnRedis::createConn()->hSet('refresh_token'.$refresh_token,'access_token',$access_token_new);
            ConnRedis::createConn()->expire("refresh_token".$refresh_token,3600*24*30);
            ConnRedis::createConn()->hMset('access_token'.$access_token_new,array("refresh_token" => $refresh_token,"uid" =>$re_token_info['uid'],"sign"=>"access_token"));
            ConnRedis::createConn()->expire("access_token".$access_token_new,3600*24*15);
            ConnRedis::createConn()->del("access_token".$access_token);
            $sign_access_token=$access_token_new;
            $sign_refresh_token=$refresh_token;
        }
        $re_token_info=ConnRedis::createConn()->hGetAll('refresh_token'.$sign_refresh_token);
        ConnRedis::createConn()->close();
        $if_exits=Db::name("member")->where(array("id"=>$re_token_info['uid']))->find();
        //用户信息
        //登录次数+1
        Db::name("member")->where(array("id" => $if_exits['id']))->setInc('login_times', 1);
        //最后登录时间修改
        Db::name("member")->where(array("id" => $if_exits['id']))->update(array("last_login_time" => date('Y-m-d H:i:s')));
        $res['access_token']=$sign_access_token;
        $res['refresh_token']=$sign_refresh_token;
        return_json(200200,"token刷新成功",$res);
    }





    /**
     * 验证access_token是否有效
     */
    function validToken(){
        header("Access-Control-Allow-Origin: *");
        $param= Request::instance()->only('access_token');
        //必传字段过滤
        //表单数据验证
        $validate = new validate\Tool;
        if (!$validate->scene('valid_token')->check($param)) {
            return_json(200101,$validate->getError(),array());
        }
        $if_access_token=ConnRedis::createConn()->hGetAll("access_token".$param['access_token']);
        if(!empty($if_access_token)){
            return_json(200200,"access_token有效",(object)array());
        }else{
            return_json(200203,"access_token失效",(object)array());
        }

    }

    /**
     * 判断手机号是否被注册
     */
    function mobileNumberExits(){
        header("Access-Control-Allow-Origin: *");
        $param= Request::instance()->only('mobile');

        $validate = new validate\Tool;
        if (!$validate->scene('mobile_number_exits')->check($param)) {
            return_json(200101,$validate->getError(),array());
        }

        $where[]=array('mobile','=',$param['mobile']);
        $where[]=array('status','in','1,3,4');
        $member=Db::table("member")->where($where)->find();
        if(!empty($member)){
            $where_user_app_relation[]=array('member_id','=',$member['id']);
            $where_user_app_relation[]=array('belong_app_sign','=','register_into');
            $where_user_app_relation[]=array('status','in','0,1');
            $if_registered=Db::table("member_app_relation")->where($where_user_app_relation)->find();
            if(!empty($if_registered)){
                return_json(200200,"手机号已经注册",array());
            }
        }
        return_json(200204,"手机号未注册",array());
    }

    /**
     * 图片上传
     */
    function upoloadImg(){
        header("Access-Control-Allow-Origin: *");
        $param=Request::instance()->only("directory,app_id");
        if(isset($param['directory'])&&!empty($param['directory'])){
            $directory=$param['directory'];
        }else{
            $directory="";
        }
        if(isset($param['app_id'])&&!empty($param['app_id'])){
            $app_id=$param['app_id'];
        }else{
            $app_id="dyna";
        }
        $file_save_path="./uploads/"."APP_ID".$app_id."/img/".$directory."/";
        // 获取表单上传文件
        $files = request()->file('image');
        if(empty($files)){
            return_json(200101,"未传入要上传的文件",array());
        }
        $save_path_arr=array();
        foreach($files as $file){
            // 移动到框架应用根目录/uploads/ 目录下
            $info = $file->validate(['size'=>1567800,'ext'=>'jpg,png,jpeg'])->move($file_save_path);
           // $info = $file->move( $file_save_path);
            if($info){
                $img_save_path=$file_save_path.$info->getSaveName();
                array_push($save_path_arr,$img_save_path);
            }else{
                // 上传失败获取错误信息
                return_json(200204,$file->getError(),array("url"=>""));
            }
        }

        if(!empty($save_path_arr)){
            $save_path_str=implode(",",$save_path_arr);
            return_json(200200,"图片上传成功",array("url"=>$save_path_str));
        }else{
            return_json(200204,"图片上传失败",array("url"=>""));
        }
    }


    /**
     * 文件上传
     */
    function upoloadFile(){
        header("Access-Control-Allow-Origin: *");
        $param=Request::instance()->only("directory,app_id");
        if(isset($param['directory'])&&!empty($param['directory'])){
            $directory=$param['directory'];
        }else{
            $directory="";
        }
        if(isset($param['app_id'])&&!empty($param['app_id'])){
            $app_id=$param['app_id'];
        }else{
            $app_id="dyna";
        }
        $file_save_path="./uploads/"."APP_ID".$app_id."/file/".$directory."/";
        // 获取表单上传文件
        $files = request()->file('file');
        if(empty($files)){
            return_json(200101,"未传入要上传的文件",array());
        }
        $save_path_arr=array();
        foreach($files as $file){
            // 移动到框架应用根目录/uploads/ 目录下
            $info = $file->validate(['size'=>15678000,'ext'=>'pdf,docx,xlsx,txt,zip'])->move($file_save_path);
            // $info = $file->move( $file_save_path);
            if($info){
                $img_save_path=$file_save_path.$info->getSaveName();
                array_push($save_path_arr,$img_save_path);
            }else{
                // 上传失败获取错误信息
                return_json(200204,$file->getError(),array("url"=>""));
            }
        }

        if(!empty($save_path_arr)){
            $save_path_str=implode(",",$save_path_arr);
            return_json(200200,"文件上传成功",array("url"=>$save_path_str));
        }else{
            return_json(200204,"文件上传失败",array("url"=>""));
        }
    }



	/**
	 * 图片上传(朱改)
	 */
	function uploadZhuImg(){
		header("Access-Control-Allow-Origin: *");
		$param=Request::instance()->only("directory,app_id");
		if(isset($param['directory'])&&!empty($param['directory'])){
			$directory=$param['directory'];
		}else{
			$directory="";
		}
		if(isset($param['app_id'])&&!empty($param['app_id'])){
			$app_id=$param['app_id'];
		}else{
			$app_id="dyna";
		}
		if($directory)
		{
		    $mk = '/'.$directory.'/';
        }else{
		    $mk = '/';
        }
		$file_save_path="uploads/"."APP_ID".$app_id."/img".$mk;
		// 获取表单上传文件
		$files = request()->file('file');
		if(empty($files)){
			return_json(200101,"未传入要上传的文件",array());
		}
		$info = $files->validate(['size'=>1567800,'ext'=>'jpg,png,jpeg'])->move($file_save_path);
		if($info==false){

			$data['name']="";
			$data['status']="error";

			$data['url']="";
			$data['thumbUrl']="";

			//return_json(200200,"图片上传成功",$data);
			echo json_encode($data,JSON_UNESCAPED_UNICODE);


		}else{
			$filename = $info->getInfo();

			$exclePath=$filename['name'];


			//$save_ip=$_SERVER["REMOTE_ADDR"].":".$_SERVER['SERVER_PORT'];

			$img_save_path=	'http://'.$_SERVER["HTTP_HOST"].'/'.$file_save_path.$info->getSaveName();//文件保存路径
			//dump($img_save_path);


			$data['name']=$exclePath;
			$data['status']="done";

			$data['url']=$img_save_path;
			$data['thumbUrl']=$img_save_path;

			//return_json(200200,"图片上传成功",$data);
			echo json_encode($data,JSON_UNESCAPED_UNICODE);
		}



	}
	/**
	 * 文件上传(朱改)
	 */
	function upoloadZhuFile(){

		header("Access-Control-Allow-Origin: *");
		$param=Request::instance()->only("directory,app_id");
		if(isset($param['directory'])&&!empty($param['directory'])){
			$directory=$param['directory'];
		}else{
			$directory="";
		}
		if(isset($param['app_id'])&&!empty($param['app_id'])){
			$app_id=$param['app_id'];
		}else{
			$app_id="dyna";
		}
        if($directory)
        {
            $mk = '/'.$directory.'/';
        }else{
            $mk = '/';
        }
        $file_save_path="uploads/"."APP_ID".$app_id."/img".$mk;
		// 获取表单上传文件
		$files = request()->file('file');
		if(empty($files)){
			return_json(200101,"未传入要上传的文件",array());
		}

		$info = $files->validate(['size'=>15678000,'ext'=>'pdf,docx,xlsx,txt,zip'])->move($file_save_path);

		if($info==false){
			$data['name']="";
			$data['status']="error";

			$data['url']="";
			$data['thumbUrl']="";

			//return_json(200200,"图片上传成功",$data);
			echo json_encode($data,JSON_UNESCAPED_UNICODE);

		}else{
			$filename = $info->getInfo();





			$exclePath=$filename['name'];


			//$save_ip=$_SERVER["REMOTE_ADDR"].":".$_SERVER['SERVER_PORT'];

			$img_save_path='http://'.$_SERVER["HTTP_HOST"]."/".$file_save_path.$info->getSaveName();//文件保存路径

			//dump($img_save_path);


			$data['name']=$exclePath;
			$data['status']="done";

			$data['url']=$img_save_path;
			$data['thumbUrl']=$img_save_path;

			//return_json(200200,"图片上传成功",$data);
			echo json_encode($data,JSON_UNESCAPED_UNICODE);

		}



	}




}