<?php
/**
 * Created by PhpStorm.
 * User: fu.hy
 * Date: 2019/9/24
 * Time: 10:51
 */
namespace app\login\validate;
use think\Validate;

class Login extends Validate{

    protected $rule =   [
        'mobile'  => 'require|mobile',//手机号
        'mobile_code'   => 'require|number|length:4',//手机验证码
        'password'=>'require|alphaNum',
        'account'=>'require|alphaNum|number',
		'mobile_code'=>'require|number|max:4',
		'login_type'=>'alpha|require',
		'access_token'=>'require|alphaDash',
		'app_id'=>'require|alphaDash',
		'page'=>'integer',
		'size'=>'integer',
		'company_id'=>'require|alphaDash',
		'company_name'=>'require',
		'department_name'=>'require'


    ];

    protected $message = [
        'mobile.require'=>'手机号必传',
        'mobile.mobile' => '请确认手机号',
        'account.require'=>'账号不得为空',
        'account.alphaNum'=>'账号需要是字母和数字',
        'password.require'=>'密码为空',
        "password.alphaNum"=>'密码需要是数字和字母',
        'mobile_code.require'     => '验证码必传',
        'mobile_code.number'     => '验证码必须是数字',
        'mobile_code.length'   => '验证码长度为4',
        'app_sign.require'=>'注册码必传',
        'app_sign.alphaNum'=>'注册码应由字母,数字组成',
		'mobile_code.require'=>'手机验证码必传',
		'mobile_code.number'=>'手机验证码必须是数字',
		'mobile_code.max'=>'手机验证码最长4个数字',
		'login_type.require'=>'登录端类型未传',
		'login_type.alpha'=>'登录端类型为字母',
		'account.number'=>'账号必须是数字',
		'access_token.require'=>'ACCESSTOKEN 必传',
		'access_token.alphaDash'=>'ACCESSTOKEN 为字母和数字，下划线_及破折号-',
		'app_id.require'=>'APPID 必传',
		'app_id.alphaDash'=>'APPID 为字母和数字，下划线_及破折号-',
		'page.integer'=>'页码为正整数',
		'size.integer'=>'显示数量为正整数',
		'company_id.require'=>'企业数据编号必传',
		'company_id.alphaDash'=>'企业数据编号为字母和数字，下划线_及破折号-',
		'company_name.require'=>'企业名称必传',
		'department_name.require'=>'部门名称必传'

    ];

    protected $scene = [
        //验证登录表单验证,验证字段
        'mobile_code_login'  =>  ['mobile','mobile_code'],
        'account_pass_login' =>['account','passsword'],
		'userRegistered'=>['mobile','mobile_code','login_type'],
		'userAccountLogin'=>['account','password','login_type'],
		'userApplyProject'=>['access_token','app_id','login_type'],
		'couldApplyProjectList'=>['access_token','login_type'],
		'isProjectAdmin'=>['app_id','access_token'],
		'userCouldApplyCompanyList'=>['access_token','page','size','login_type'],
		'userCouldApplyDepartmentList'=>['access_token','page','size','company_id','login_type'],
		'userSetselfCompanyId'=>['access_token','company_name','login_type'],
		'userSetselfDepartmentId'=>['access_token','company_id','department_name','login_type'],
		'personLogout'=>['access_token','login_type'],
		'ifPerfectAllFields'=>['access_token','login_type'],
		'userCompletionFieldSubmit'=>['access_token','login_type'],
		'applyProjectFieldgroupList'=>['access_token','login_type','app_id']

    ];







}