<?php
/**
 * Created by PhpStorm.
 * User: fu.hy
 * Date: 2019/9/24
 * Time: 10:51
 */
namespace app\tool\validate;
use think\Validate;

class Tool extends Validate{

    protected $rule =   [
        'mobile'  => 'require|mobile',//手机号
        'template_id'   => 'require|number',//容联短信验证码模板编号
        'save_seconds'=>'number',
        'access_token'=>'require|alphaNum',
        'refresh_token'=>'require|alphaNum',
		'mobile_code'=>'require',

		'pwd'=>'require',
		'repwd'=>'require'
    ];

    protected $message = [
        'mobile.require'=>'手机号必传',
        'mobile.mobile' => '请确认手机号',
        'template_id.require'     => '短信验证码模板编号未传',
        'template_id.number'     => '短信验证码模板编号未传必须是数字',
        'save_seconds.number' => '验证码保存时间(s)',
        'access_token.require'=>'access_token必传',
        'access_token.alphaNum'=>'access_token由字符,数字组成',
        'refresh_token.require'=>'refresh_token必传',
        'refresh_token.alphaNum'=>'refresh_token由字符,数字组成',
		'mobile_code.require'=>'手机验证码必传'
    ];

    protected $scene = [
        //发送短信验证码
        'send_message_code'  =>  ['mobile','template_id','save_seconds'],
        //access_token刷新
        'verify_token'=>['access_token','refresh_token'],
        //access_token是否有效(存在)
        'valid_token'=>['access_token'],
        //判断手机号是否被注册
        'mobile_number_exits'=>['mobile'],
		//判断手机验证码是否正确
		'validMobileCode'=>['mobile','mobile_code'],
		'setNewPwd'=>['mobile_code','pwd','repwd','mobile']

    ];





}