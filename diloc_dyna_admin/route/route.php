<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\facade\Route;//静态代理路由.


/**
 * 用户登录
 */
Route::group('login/:module/:version/',[
	//戴纳管理员登录超管pc
    'dyna_login'=>'login/:module.:version.Login/dynaMemberLogin',
	//普通用户验证码注册|登录
	'person_reg_login'=>'login/:module.:version.AverageUserLogin/userRegisteredLogin',
	//用户账号密码登录
	'person_account_login'=>'login/:module.:version.AverageUserLogin/userAccountLogin',
	//退出登录 销毁access_token
	'person_logout'=>'login/:module.:version.AverageUserLogin/personLogout',
	//用户申请加入指定项目
	'user_apply_project'=>'login/:module.:version.AverageUserLogin/userApplyProject',
	//用户卡申请加入的项目列表
	'could_apply_project_list'=>'login/:module.:version.AverageUserLogin/couldApplyProjectList',
	//判断指定用户是否是项目的管理员
	'is_project_admin'=>'login/:module.:version.AverageUserLogin/isProjectAdmin',
	//用户可申请加入的企业信息列表
	'user_couldapply_company_list'=>'login/:module.:version.AverageUserLogin/userCouldApplyCompanyList',
		//用户可加入的部门信息了列表
	'user_couldapply_department_list'=>'login/:module.:version.AverageUserLogin/userCouldApplyDepartmentList',
	'user_setself_companyid'=>'login/:module.:version.AverageUserLogin/userSetselfCompanyId',
	'user_setself_departmentid'=>'login/:module.:version.AverageUserLogin/userSetselfDepartmentId',
	//系统需要新注册用户完善的字段
	'completion_field'=>'login/:module.:version.AverageUserLogin/regCompletionFields',
	//判断用户是否已经完善了全部系统要求需要的完善的字段
	'if_perfect_allfields'=>'login/:module.:version.AverageUserLogin/ifPerfectAllFields',
	//用户提交完善的字段信息
	'user_completion_fieldsubmit'=>'login/:module.:version.AverageUserLogin/userCompletionFieldSubmit',
	//用户加入项目时,需要完善的用户个人描述字段分组
	'apply_project_fieldgroup_list'=>'login/:module.:version.AverageUserLogin/applyProjectFieldgroupList',

	//判断是否完善了要加入项目需要完善的所有字段分组
	'apply_project_fieldgroup_perfect'=>'login/:module.:version.AverageUserLogin/applyProjectFieldgroupPerfect',
	//获取用户信息,用于完善需要完善的数据展示
	'user_info_show'=>'login/:module.:version.AverageUserLogin/userInfoShow'



])->allowCrossDomain();


	/**
	 * 用户个人信息管理
	 * 修改  展示
	 *
	 */
	Route::group('login/:module/:version/',[

	])->allowCrossDomain();


	/**
	 * 实体资源联网信息管理
	 */
	Route::group('entityresconninter/:module/:version/',[

		//添加实体资源信息
		'physical_resource_create'=>'entityresconninter/:module.:version.Physresource/physResourceCreate',

		//实体资源绑定传感器
		'create_resource_sensor_relation'=>'entityresconninter/:module.:version.Physresource/resourceSensorRelationAdd',

		//为实体传感器添加监控参数
		'sensor_signal_add'=>'entityresconninter/:module.:version.Physresource/sensorSignalAdd',

		//为实体传感器修改监控参数
		'sensor_signalinfo_edit'=>'entityresconninter/:module.:version.Physresource/sensorSignalInfoEdit',

		//监控参数报警辅助条件删除
		'signalwarn_condition_del'=>'entityresconninter/:module.:version.Physresource/signalwarnConditionDel',

		//实体监控参数详情信息
		'signalinfo_show'=>'entityresconninter/:module.:version.Physresource/signalInfoShow',

		//监控参数报警辅助条件列表
		'signalwarn_condition_list'=>'entityresconninter/:module.:version.Physresource/signalwarnConditionList',

		//参数报警n级报警数据删除按钮
		'signalwarn_level_delbtn'=>'entityresconninter/:module.:version.Physresource/signalWarnlevelDelBtn',




		//实体传感器下监控参数列表
		'resource_sensor_signallist'=>'entityresconninter/:module.:version.Physresource/resourceSensorSignalList',

		//实体资源关联的传感器数据列表
		'resource_sensorlist'=>'entityresconninter/:module.:version.Physresource/resourceSensorList',
		//实体资源和传感器解除绑定   同时删除资源和监控参数的关系
		'resource_sensor_unbundling'=>'entityresconninter/:module.:version.Physresource/resourceSensorUnbundling',

		// 监控参数和实体资源关系创建
		'resource_signal_relationadd'=>'entityresconninter/:module.:version.Physresource/resourceSignalRelationAdd',

		// 监控参数和实体资源关系解除
		'resource_signal_relationdel'=>'entityresconninter/:module.:version.Physresource/resourceSignalRelationDel',

		//修改资源和监控参数关系数据
		'resource_signal_relationedit'=>'entityresconninter/:module.:version.Physresource/resourceSignalRelationEdit'



	])->allowCrossDomain();






/**
 * 项目基本信息相关
 */
Route::group('project/:module/:version/',[
    'project_list'=>'project/:module.:version.Project/industryInfoList',
    'industry_choose_list'=>'project/:module.:version.Project/industryChooseList',
    'project_create'=>'project/:module.:version.Project/projectInfoCreate',
    'project_info'=>'project/:module.:version.Project/projectInfoMess',
    'project_edit'=>'project/:module.:version.Project/projectInfoEdit',
])->allowCrossDomain();

/**
 * 项目配置信息相关
 */
Route::group('projectconfig/:module/:version/',[
    'configedit_general'=>'project/:module.:version.Projectconfig/configGeneralEdit',
    'configedit_ronglian'=>'project/:module.:version.Projectconfig/configRonglianEdit',
    'configedit_domain'=>'project/:module.:version.Projectconfig/configDomainEdit',
    'configedit_shared'=>'project/:module.:version.Projectconfig/configSharedEdit',
    'configedit_wechat'=>'project/:module.:version.Projectconfig/configWechatEdit',
    'configinfo'=>'project/:module.:version.Projectconfig/configInfo',
	'configedit_userapply'=>'project/:module.:version.Projectconfig/configUserApplyEdit'
])->allowCrossDomain();


/**
 * 资源分类基本信息
 */
Route::group('resource/:module/:version',[
    'type_create'=>'resource/:module.:version.Resourcetype/resourceTypeCreate',
    'type_list'=>'resource/:module.:version.Resourcetype/resourceTypeList',
	'type_info'=>'resource/:module.:version.Resourcetype/resourceTypeInfo',
	'type_edit'=>'resource/:module.:version.Resourcetype/resourceTypeEdit',
	'type_del'=>'resource/:module.:version.Resourcetype/resourceTypeDel'
])->allowCrossDomain();

	/**
	 * 资源分类关联的字段分组信息
	 */
	Route::group('resource/:module/:version',[
		'fieldgrop_create'=>'resource/:module.:version.Resourcedictionary/fieldGroupCreate',
		'fieldgrop_list'=>'resource/:module.:version.Resourcedictionary/fieldGroupList',
		'fieldgrop_edit'=>'resource/:module.:version.Resourcedictionary/fieldGroupEdit',
		'fieldgrop_del'=>'resource/:module.:version.Resourcedictionary/fieldGroupDel',

		'fielddict_create'=>'resource/:module.:version.Resourcedictionary/fieldDictCreate',
		'fielddict_info'=>'resource/:module.:version.Resourcedictionary/fieldDictInfo',
		'fielddict_edit'=>'resource/:module.:version.Resourcedictionary/fieldDictEdit',
		'fielddict_list'=>'resource/:module.:version.Resourcedictionary/fieldDictList',
		'fielddict_del'=>'resource/:module.:version.Resourcedictionary/fieldDictDel',

		'fielddict_auxiliary_create'=>'resource/:module.:version.Resourcedictionary/fieldAuxiliarycodeCreate',
		'fielddict_auxiliary_edit'=>'resource/:module.:version.Resourcedictionary/fieldAuxiliarycodeEdit',

		'fielddict_auxiliary_info'=>'resource/:module.:version.Resourcedictionary/fieldAuxiliarycodeInfo',
		'fielddict_auxiliary_list'=>'resource/:module.:version.Resourcedictionary/fieldAuxiliarycodeList',
		'fielddict_auxiliary_del'=>'resource/:module.:version.Resourcedictionary/fieldAuxiliarycodeDel',
	])->allowCrossDomain();


	/**
	 * 资源模型(基本信息)相关
	 */
	Route::group('resource/:module/:version',[
		'show_resorucemodel_form'=>'resource/:module.:version.Resourcemodel/showForm',
		'resource_template_create'=>'resource/:module.:version.Resourcemodel/resourceTemplateCreate',
		'resource_template_list'=>'resource/:module.:version.Resourcemodel/resourceTemplateList',
		'resource_template_info'=>'resource/:module.:version.Resourcemodel/resourceTemplateInfo',
		'resource_template_edit'=>'resource/:module.:version.Resourcemodel/resourceTemplateEdit',
		'resource_templatedit_status'=>'resource/:module.:version.Resourcemodel/resourceTemplateIfused',
		'resource_template_del'=>'resource/:module.:version.Resourcemodel/resourceTemplateDel',

		'resourcetemplate_manual_create'=>'resource/:module.:version.Resourcemodel/resourceTemplateManualCreate',
		'resourcetemplate_manual_edit'=>'resource/:module.:version.Resourcemodel/resourceTemplateManualEdit',
		'resourcetemplate_manual_list'=>'resource/:module.:version.Resourcemodel/resourceTemplateManualList',
		'resourcetemplate_manual_info'=>'resource/:module.:version.Resourcemodel/resourceTemplateManualInfo',
		'resourcetemplate_manual_del'=>'resource/:module.:version.Resourcemodel/resourceTemplateManualDel',

	])->allowCrossDomain();

	/**
	 * 资源模型(实体资源模型和传感器模型的关系)相关
	 */
	Route::group('resource/:module/:version',[
		'resource_template_sensor_add'=>'resource/:module.:version.Resourcemonitoringmodel/resourceTemplateSensorAdd',
		'resource_template_sensor_list'=>'resource/:module.:version.Resourcemonitoringmodel/resourceTemplateSensorList',
		'resource_template_sensor_del'=>'resource/:module.:version.Resourcemonitoringmodel/resourceTemplateSensorDel',
		'resource_template_sensor_edit'=>'resource/:module.:version.Resourcemonitoringmodel/resourceTemplateSensorEdit'

	])->allowCrossDomain();


	/**
	 * 传感器模型和监控参数模型的关系
	 */
	Route::group('resource/:module/:version',[
		'sensor_template_signal_add'=>'resource/:module.:version.Resourcesignalmodel/sensorTemplateSignalAdd',
		'sensor_template_signal_list'=>'resource/:module.:version.Resourcesignalmodel/sensorTemplateSignalList',
		'sensor_template_signal_info'=>'resource/:module.:version.Resourcesignalmodel/sensorTemplateSignalInfo',
		'sensor_template_signal_edit'=>'resource/:module.:version.Resourcesignalmodel/sensorTemplateSignalEdit',
		'templatesignal_warnlevel_del'=>'resource/:module.:version.Resourcesignalmodel/templateSignalWarnLevelDel',

		'template_signalwarn_condition_list'=>'resource/:module.:version.Resourcesignalmodel/templateSignalWarnConditionList',
		'template_signalwarn_condition_del'=>'resource/:module.:version.Resourcesignalmodel/templateSignalWarnConditionDel',
		'template_signalwarn_condition_add'=>'resource/:module.:version.Resourcesignalmodel/templateSignalWarnConditionAdd',
		'template_signalwarn_condition_edit'=>'resource/:module.:version.Resourcesignalmodel/templateSignalWarnConditionEdit'

	])->allowCrossDomain();


	/**
	 * 定时任务
	 */
	Route::group('crontab/:module/:version',[

		//预约订单超时自动取消
		'order_appoint_passtime_cancel'=>'crontab/:module.:version.Crontaborder/orderAppointPasstimeCancel',
		//培训认证自动过期
		'training_autopassdate'=>'crontab/:module.:version.Traininguser/trainingBeOverdue',

		//程序代码版本信息输出
		'code_version'=>'crontab/:module.:version.Traininguser/codeVersion'


	])->allowCrossDomain();




/**
	 * 工具类接口分组
	 * (APP端)
	 */
	Route::group('tool/:module/:version/',[
		'send_sms'=>'tool/:module.:version.Tool/sendVerificationCode',//发送手机短信验证码 ok
		/* 'refresh_token'=>'tool/:module.:version.Tool/verifyToken',//刷新access_token ok
		 'valid_token'=>'tool/:module.:version.Tool/validToken',//验证access_token是否有效 ok
		 'mobile_number_exits'=>'tool/:module.:version.Tool/mobileNumberExits'//手机号是否已经注册 ok*/
		'upload_img'=>'tool/:module.:version.Tool/upoloadImg',//图片上传
		'upload_file'=>'tool/:module.:version.Tool/upoloadFile',//文件上传

		'upload2_img'=>'tool/:module.:version.Tool/uploadZhuImg',//图片上传
		'upload2_file'=>'tool/:module.:version.Tool/upoloadZhuFile',//文件上传


		'valid_mobile_code'=>'tool/:module.:version.Tool/validMobileCode',//找回密码时判断手机验证码是否正确
		'set_newpwd'=>'tool/:module.:version.Tool/setNewPwd'//重置登录密码


	])->allowCrossDomain();





	//=====================以下liufei====================






	/**
	 * 菜单导航
	 */
	Route::group('menu/:module/:version/',[

		// 新增功能模块
		'add_menu'=>'configure/:module.:version.Menu/add_menu',
		// 获取功能模块列表
		'menu_list'=>'configure/:module.:version.Menu/menu_list',
		// 获取功能模块详情
		'menu_details'=>'configure/:module.:version.Menu/menu_details',
		// 接口分类列表获取
		'api_group_list'=>'configure/:module.:version.Menu/api_group_list',

		// 获取所有pc 管理功能
		'pc_admin_menu_list'=>'configure/:module.:version.Menu/pc_admin_menu_list',
		// 编辑功能模块
		'edit_menu'=>'configure/:module.:version.Menu/edit_menu',


	])->allowCrossDomain();

	/**
	 * 消息通知管理
	 */
	Route::group('message/:module/:version/',[
		// 新增消息类型
		'add_message'=>'configure/:module.:version.Message/add_message',
		// 获取消息列表
		'message_list'=>'configure/:module.:version.Message/message_list',
		// 修改消息类型
		'edit_message'=>'configure/:module.:version.Message/edit_message',


		// 新增消息模版
		'message_module_add'=>'configure/:module.:version.Message/message_module_add',
		// 获取消息类型下模版
		'message_module_list'=>'configure/:module.:version.Message/message_module_list',
		// 获取模版消息详情
		'message_module_details'=>'configure/:module.:version.Message/message_module_details',
		// 编辑模版信息
		'edit_message_module'=>'configure/:module.:version.Message/edit_message_module',


	])->allowCrossDomain();

	/**
	 * 项目管理员相关
	 */
	Route::group('project_admin/:module/:version/',[
		// 新增项目管理人员时获取对应项目人员用
		'project_user_list'=>'project/:module.:version.ProjectAdmin/project_user_list',
		// 新增项目管理人员
		'add_project_admin'=>'project/:module.:version.ProjectAdmin/add_project_admin',
		// 获取项目下管理人员列表
		'project_admin_list'=>'project/:module.:version.ProjectAdmin/project_admin_list',
		// 删除项目下管理人员
		'del_project_user'=>'project/:module.:version.ProjectAdmin/del_project_user',

	])->allowCrossDomain();


	/**
	 * 项目菜单相关
	 */
	Route::group('project_menu/:module/:version/',[
		// 获取所有可选菜单
		'system_module_list'=>'configure/:module.:version.ProjectMenu/system_module_list',
		// 新增项目功能
		'add_project_user_module'=>'configure/:module.:version.ProjectMenu/add_project_user_module',
		// 获取项目下已开通资源
		'project_menu_list'=>'configure/:module.:version.ProjectMenu/project_menu_list',
		// 获取功能模块详情
		'project_menu_details'=>'configure/:module.:version.ProjectMenu/project_menu_details',
		// 编辑功能模块信息
		'edit_project_menu'=>'configure/:module.:version.ProjectMenu/edit_project_menu',


	])->allowCrossDomain();

	/**
	 * 项目息资源分类
	 */

	Route::group('project_resource/:module/:version/',[

		// 获取所有可选资源分类
		'resource_type_list'=>'configure/:module.:version.ProjectResourceType/resource_type_list',
		//  添加项目下可选资源分类
		'add_resource_type'=>'configure/:module.:version.ProjectResourceType/add_resource_type',
		// 获取项目下资源分类列表
		'project_resource_type_list'=>'configure/:module.:version.ProjectResourceType/project_resource_type_list',
		// 编辑项目下资源分类信息
		'edit_project_resource_type'=>'configure/:module.:version.ProjectResourceType/edit_project_resource_type',
		// 删除项目下资源分类
		'del_project_resource_type'=>'configure/:module.:version.ProjectResourceType/del_project_resource_type',


	])->allowCrossDomain();


	/**
	 * 项目共享资源
	 */

	Route::group('project_share/:module/:version/',[

		// 获取所有可选资源分组
		'share_way_group_list'=>'configure/:module.:version.ProjectShareType/share_way_group_list',
		// 添加共享资源
		'add_share_type'=>'configure/:module.:version.ProjectShareType/add_share_type',
		// 获取项目下共享资源列表
		'share_type_list'=>'configure/:module.:version.ProjectShareType/share_type_list',
		// 删除项目资源分类组合
		'del_share_type'=>'configure/:module.:version.ProjectShareType/del_share_type',



	])->allowCrossDomain();


	/**
	 * 用户管理
	 */

	Route::group('user/:module/:version/',[

		// 获取所有可选资源分组
		'user_list'=>'configure/:module.:version.User/user_list',
		// 获取用户详情
		'user_details'=>'configure/:module.:version.User/user_details',
		// 编辑用户状态
		'edit_user_status'=>'configure/:module.:version.User/edit_user_status',
		// 编辑用户信息
		'edit_user_details'=>'configure/:module.:version.User/edit_user_details',

	])->allowCrossDomain();


	/**
	 * 企业管理
	 */

	Route::group('company/:module/:version/',[

		// 企业列表
		'company_list'=>'configure/:module.:version.Company/company_list',
		// 企业详情
		'company_details'=>'configure/:module.:version.Company/company_details',
		// 获取企业下部门
		'company_department_list'=>'configure/:module.:version.Company/company_department_list',
		// 获取企业下用户
		'company_user_list'=>'configure/:module.:version.Company/company_user_list',


	])->allowCrossDomain();

	/**
	 * 课题管理
	 */

	Route::group('subject/:module/:version/',[

		// 课题列表
		'subject_team_list'=>'configure/:module.:version.SubjectTeam/subject_team_list',
		// 课题详情
		'subject_team_details'=>'configure/:module.:version.SubjectTeam/subject_team_details',
		// 课题组下人员列表
		'subject_team_user'=>'configure/:module.:version.SubjectTeam/subject_team_user',
		// 修改课题组状态
		'edit_subject_team_status'=>'configure/:module.:version.SubjectTeam/edit_subject_team_status',



	])->allowCrossDomain();

	/**
	 * 系统配置
	 */

	Route::group('sys_config/:module/:version/',[

		// 获取配置信息
		'sys_config_list'=>'configure/:module.:version.SysConfig/sys_config_list',
		// 修改
		'edit_config'=>'configure/:module.:version.SysConfig/edit_config',

	])->allowCrossDomain();

	/**
	 * soket 设置
	 */

	Route::group('socket/:module/:version/',[

		// 获取可选项目列表
		'project_list'=>'configure/:module.:version.SocketConfig/project_list',
		// 添加soket 设置
		'add_socket_config'=>'configure/:module.:version.SocketConfig/add_socket_config',
		// 获取soket 链接列表
		'socket_list'=>'configure/:module.:version.SocketConfig/socket_list',
		// 删除
		'del_socket'=>'configure/:module.:version.SocketConfig/del_socket',
		// 编辑
		'edit_socket_config'=>'configure/:module.:version.SocketConfig/edit_socket_config',

	])->allowCrossDomain();

	/**
	 * 审核管理
	 */

	Route::group('audit/:module/:version/',[

		// 新增审核
		'add_audit'=>'configure/:module.:version.Audit/add_audit',
		// 获取审核类型列表
		'audit_list'=>'configure/:module.:version.Audit/audit_list',
		// 编辑
		'edit_audit'=>'configure/:module.:version.Audit/edit_audit',


	])->allowCrossDomain();

	/**
	 * 项目审核信息
	 */

	Route::group('project_audit/:module/:version/',[

		// 获取可选审核信息
		'audit_type_list'=>'configure/:module.:version.ProjectAudit/audit_type_list',
		// 项目添加审核信息
		'add_project_audit'=>'configure/:module.:version.ProjectAudit/add_project_audit',
		// 获取项目下审核列表
		'project_audit_list'=>'configure/:module.:version.ProjectAudit/project_audit_list',
		// 删除项目下审核类型
		'del_project_audit'=>'configure/:module.:version.ProjectAudit/del_project_audit',



	])->allowCrossDomain();



