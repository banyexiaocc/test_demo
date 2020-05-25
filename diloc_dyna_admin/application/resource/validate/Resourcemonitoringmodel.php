<?php
	/**
	 * Created by PhpStorm.
	 * User: fu.hy
	 * Date: 2019/9/24
	 * Time: 10:51
	 */
	namespace app\resource\validate;
	use think\Validate;

	class Resourcemonitoringmodel extends Validate
	{

		protected $rule = [
			'resource_template_code'=>'require|alphaDash',
			'sensor_code'=>'require|alphaDash',
			'sort'=>'number',
			'aim_id'=>'require|alphaNum'



			/*  'status' => 'integer',
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

			'resource_template_code.require'=>'资源模型唯一编码不得为空',
			'resource_template_code.alphaDash'=>'资源模型唯一编码为字母和数字，下划线_及破折号-组成',
			'sensor_code.require'=>'传感器模型唯一编码必传',
			'sensor_code.alphaDash'=>'传感器模型唯一编码为字母和数字，下划线_及破折号-组成',
			'sort.number'=>'排序字段传值为数字',
			'aim_id.require'=>'目标数据编号未传',
			'aim_id.alphaNum'=>'目标数据编号为为字母和数字组成'




			/* 'status.integer' => '项目状态数字',
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
			/*  'projectInfoList' => ['status', 'keywords','create_time','page','size'],
			  'projectInfoCreate'=>['name','logo','industry_type_id','head_person','head_person_mobile','due_time','contract_amount','registration_code','company_name','company_addr','company_phone','company_fax','company_email','connect_person','connect_person_mobile','sort'],
			  'projectInfoMess'=>['pro_id']*/

			'resourceTemplateSensorAdd'=>['resource_template_code','sensor_code','sort'],
			'resourceTemplateSensorList'=>['resource_template_code'],
			'resourceTemplateSensorDel'=>['resource_template_code','sensor_code'],
			'resourceTemplateSensorEdit'=>['aim_id','sort']



		];

	}
