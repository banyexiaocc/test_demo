<?php
/**
 * Created by PhpStorm.
 * User: fu.hy
 * Date: 2019/9/24
 * Time: 10:51
 */
namespace app\project\validate;
use think\Validate;

class Projectconfig extends Validate
{

    protected $rule = [
        'app_id'=>'require|alphaNum',
        'db_name'=>'alphaDash',
        'registry_set'=>'integer',

        'cloud_account'=>'alphaNum',
        'cloud_token'=>'alphaNum',
        'cloud_appid'=>'alphaNum',
        'code_minimum_interval'=>'integer',
        'code_savetime_interval'=>'integer',

        'website_name'=>'chsDash',
        'project_password'=>'alphaNum',
        'search_keywords'=>'chsDash',

        'share_permissions'=>'chsDash',

        'wechat_publicnumber_name'=>'chsDash',
        'wechat_appid'=>'alphaNum',
        'wechat_secret'=>'alphaNum',
		'registered_fields'=>'require'



        /*'status' => 'integer',
        'keywords' => 'chsAlphaNum',
        'create_time' => 'dateFormat:Y-m-d H:i:s',
        'page' => 'integer',
        'size' => 'integer',

        'name'=>'require',
        'logo'=>'require',
        'industry_type_id'=>'require',
        'head_person'=>'require|chsAlpha',
        'head_person_mobile'=>'require|mobile',
        'due_time'=>'require|dateFormat:Y-m-d',
        'contract_amount'=>'require',
        'registration_code'=>'require',
        'company_name'=>'require',
        'company_addr'=>'require',
        'company_phone'=>'require',
        'company_fax'=>'require',
        'company_email'=>'require|email',
        'connect_person'=>'require|chsAlpha',
        'connect_person_mobile'=>'require|mobile',
        'sort'=>'number',
        'pro_id'=>'require'*/
    ];

    protected $message = [
        'app_id.alphaNum'=>'项目编号由字母和数字组成',
        'app_id.require'=>'项目编号必传',
        'db_name.alphaDash'=>'项目数据库名称由为字母和数字，下划线_及破折号-组成',

        'registry_set.integer'=>'注册设置传值应为正整数',
        'cloud_account.alphaNum'=>'容联账号由字母和数字组成',
        'cloud_appid.alphaNum'=>'容联项目编号由字母和数字组成',
        'code_minimum_interval.integer'=>'验证码发送最小间隔传值时正整数',
        'code_savetime_interval.integer'=>'验证码保存时长传值时正整数',

        'website_name.chsDash'=>'网站名称可包含汉字、字母、数字和下划线_及破折号-',
        'project_password.alphaNum'=>'项目密码可包含值是否为字母和数字',
        'search_keywords.chsDash'=>'关键词可包含汉字、字母、数字和下划线_及破折号-',

        'share_permissions.chsDash'=>"权限传参可包含汉字、字母、数字和下划线_及破折号-",

        'wechat_publicnumber_name.alphaNum'=>'公众号名称可含有汉字、字母、数字和下划线_及破折号-',
        'wechat_appid.alphaNum'=>'公众号项目编号含有字母和数字',
        'wechat_secret.alphaNum'=>'公众号秘钥含有字母和数字',
		'registered_fields.require'=>'用户加入项目必须完善的字段信息未设置'



        /*'status.integer' => '项目状态数字',
        'keywords.chsAlphaNum'=>'关键词汉字,字母和数字',
        'create_time.dateFormat:Y-m-d H:i:s'=>'项目创建日期格式0000-00-00 00:00:00',
        'page.integer'=>'页码数需要是数字',
        'size.integer'=>'每页显示数量需要是数字',

        'name.require'=>'项目名称必传',
        'logo.require'=>'项目logo必传',
        'industry_type_id.require'=>'项目所属行业数据编号必传',
        'head_person.require'=>'负责人名称必传',
        'head_person.chsAlpha'=>'负责人名称只能是汉字、字母',
        'due_time.require'=>'到期日期必传',
        'due_time.dateFormat:Y-m-d'=>'到期日期格式:0000-00-00',
        'contract_amount.require'=>'签订金额必传',
        'registration_code.require'=>'注册码必传',
        'company_name.require'=>'公司名称必传',
        'company_addr.require'=>'公司地址必传',
        'company_fax.require'=>'公司传真必传',
        'company_phone.require'=>'公司电话未传',
        'company_email.require'=>'公司邮箱必传',
        'company_email.email'=>'公司邮箱格式错误',
        'connect_person.require'=>'联系人必传',
        'connect_person.chsAlpha'=>'联系人只能是汉字、字母',
        'connect_person_mobile.require'=>'联系人手机必传',
        'connect_person_mobile.mobile'=>'联系人手机格式错误',
        'sort.number'=>'排序必须是数字',
        'pro_id.require'=>'项目数据编号必传'*/
    ];

    protected $scene = [
        'configGeneralEdit' => ['app_id', 'db_name','registry_set'],
        'configRonglianEdit'=>['app_id','cloud_account','cloud_token','cloud_appid','code_minimum_interval','code_savetime_interval'],
        'configDomainEdit'=>['app_id','website_name','project_password','search_keywords'],
        'configSharedEdit'=>['app_id','share_permissions'],
        'configWechatEdit'=>['app_id','wechat_publicnumber_name','wechat_appid','wechat_secret'],
		'configUserApplyEdit'=>['app_id','registered_fields']
    ];

}

