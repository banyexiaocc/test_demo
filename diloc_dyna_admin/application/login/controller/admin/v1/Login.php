<?php
/**
 * Created by PhpStorm.
 * User: fu.hy
 * Date: 2019/3/29
 * Time: 11:15
 */

namespace app\login\controller\admin\v1;
use think\Controller;
use think\facade\Request;//数据接收-静态代理
use app\facade\ConnRedis;//引入静态代理类,连接Redis
use app\facade\ValidationFunction;//引入静态代理类,连接Redis
use think\Db;
use app\login\validate;


class Login
{

    function __construct()
    {
        //静态代理类绑定,redis连接类
        \think\Facade::bind('app\facade\ConnRedis', 'app\common\ConnRedis');
    }

    /**
     *戴纳后台-账号|密码登录
     */
    function dynaMemberLogin(){

        header("Access-Control-Allow-Origin: *");
        $param=Request::instance()->only('type,mobile,account,password,mobile_code');
        $validate = new validate\Login;
        if(!isset($param['type'])||empty($param['type'])){
            return_json(200101,"登录类型未传",array());
        }
        switch($param['type']){
            case '1'://账号登录
                if (!$validate->scene('account_pass_login')->check($param)) {
                    return_json(200101,$validate->getError(),array());
                }
                $where=[['account','=',$param['account']],['state','=','1'],['password','=',md5($param['password'])]];

                $if_exits=Db::table("user")->where($where)->find();
                //dump($if_exits);
                if(empty($if_exits)){
                    return_json(200204,"账号密码错误(#001)",array());
                }else{
                    switch($if_exits['status']){
                        case '2':
                            return_json(200204,"您的账号被锁定,请联系云实验室部进行解锁!",array());
                            break;
                        case '3':
                            return_json(200204,"账号密码错误(#002)",array());
                            break;
                    }
                }
                break;
                case '2'://验证码登录
                if (!$validate->scene('mobile_code_login')->check($param)) {
                    return_json(200101,$validate->getError(),array());
                }
                $where=[['mobile','=',$param['mobile']],['state','=','1']];
                //手机验证码验证
                    $phone_code=ConnRedis::createConn()->get("phone_yzm".$param['mobile_code'].$param['mobile']);
                    if(!$phone_code){
                        return_json(200101,"验证码失效,请重新获取",array());
                    }else{
                        if($phone_code!=$param['mobile_code']){
                            return_json(200101,"验证码错误,请确认!",array());
                        }else{
                            ConnRedis::createConn()->del("phone_yzm".$param['mobile_code'].$param['mobile']);
                        }
                    }
                    $if_exits=Db::table("user")->where($where)->find();

                break;
            default:
                return_json(200101,"请传入正确的类型方式类型",array());
        }
        //dump($if_exits);
        $login_uid=$if_exits['id'];
        //生成dyna登录token
        //生成token信息
        //登录信息生成
        $if_login_user=ConnRedis::createConn()->hMGet("diloc_dyna_login_uid".$login_uid,array("diloc_dyna_access_token","diloc_dyna_refresh_token"));//注意此信息保存时间等于refresh_token保存时间
        if($if_login_user['diloc_dyna_access_token']==false){
            $access_token=creat_access_token($login_uid);
            $refresh_token=creat_refresh_token($login_uid);
            //将token信息保存
            ConnRedis::createConn()->hMset("diloc_dyna_login_uid".$login_uid,array("diloc_dyna_access_token"=>$access_token,"diloc_dyna_refresh_token"=>$refresh_token,"uid"=>$login_uid));
            ConnRedis::createConn()->expire("diloc_dyna_login_uid".$login_uid,3600*24*30);
            ConnRedis::createConn()->hMset("diloc_dyna_access_token".$access_token,array("diloc_dyna_refresh_token"=>$refresh_token,"uid"=>$login_uid,"sign"=>"diloc_dyna_access_token"));
            ConnRedis::createConn()->expire("diloc_dyna_access_token".$access_token,3600*24*15);
            ConnRedis::createConn()->hMset("diloc_dyna_refresh_token".$refresh_token,array("diloc_dyna_access_token"=>$access_token,"uid"=>$login_uid,"sign"=>"diloc_dyna_refresh_token"));
            ConnRedis::createConn()->expire("diloc_dyna_refresh_token".$refresh_token,3600*24*30);
            ConnRedis::createConn()->close();
        }else{
            //说明refresh_token依然存在,重新生成access_token,跟新refresh_token的保存时间,refresh_token不需要刷新
            $access_token=creat_access_token($login_uid);
            $refresh_token=$if_login_user['diloc_dyna_refresh_token'];
            ConnRedis::createConn()->del("diloc_dyna_access_token".$if_login_user['diloc_dyna_access_token']);
            ConnRedis::createConn()->expire("diloc_dyna_refresh_token".$if_login_user['diloc_dyna_refresh_token'],3600*24*30);
            ConnRedis::createConn()->expire("diloc_dyna_login_uid".$login_uid,3600*24*30);
            ConnRedis::createConn()->hMset("diloc_dyna_access_token".$access_token,array("diloc_dyna_refresh_token"=>$if_login_user['diloc_dyna_refresh_token'],"uid"=>$login_uid,"sign"=>"diloc_dyna_access_token"));
            ConnRedis::createConn()->expire("diloc_dyna_access_token".$access_token,3600*24*15);
            //修改保存信息
            ConnRedis::createConn()->hSet('diloc_dyna_login_uid'.$login_uid,'diloc_dyna_access_token',$access_token);
            ConnRedis::createConn()->hSet('diloc_dyna_refresh_token'.$if_login_user['diloc_dyna_refresh_token'],'diloc_dyna_access_token',$access_token);
            ConnRedis::createConn()->close();
        }

        $member_info_data['access_token']=$access_token;
        $member_info_data['expires_in_access_token']="15 days";
        $member_info_data['refresh_token']=$refresh_token;
        $member_info_data['expires_in_refresh_token']="30 days";
        $member_info_data['account']=$if_exits['account'];
        $member_info_data['mobile']=$if_exits['mobile'];
        $member_info_data['head_img']=$if_exits['head_img'];
        $member_info_data['nick_name']=$if_exits['nick_name'];
        $member_info_data['uid']=$if_exits['id'];
        return_json(200200,"DYNA管理员登录成功",$member_info_data);

    }

}
