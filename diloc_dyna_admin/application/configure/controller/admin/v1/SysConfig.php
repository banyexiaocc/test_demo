<?php
/**
 * Created by PhpStorm.
 * User: fu.hy
 * Date: 2019/3/29
 * Time: 11:15
 */

namespace app\configure\controller\admin\v1;
use app\auth\controller\admin\v1\Authuser;
use think\Controller;
use think\facade\Request;//数据接收-静态代理
use app\facade\ConnRedis;//引入静态代理类,连接Redis
use think\Db;
use app\login\validate;


class SysConfig extends Authuser
{

    protected $param;
    protected $support_end = ['pc','app','wechat_program'];
//方法已经可用
    public function __construct(){
        parent::__construct();
    }

    /**
     * 对应配置唯一码
     *
    */
    public function sys_config_list()
    {

        $list = [
            '507869C5-CA58-DC71-4C68-D9C9CED808281-AC30F76B8F1' => ['code' => '507869C5-CA58-DC71-4C68-D9C9CED808281-AC30F76B8F1','name' => '用户注册审核','type' => 1],
            'BA1DFC08-5DF8-4F21-81FC-35CB0228FA83-105BDD51FB' => ['code' => 'BA1DFC08-5DF8-4F21-81FC-35CB0228FA83-105BDD51FB','name' => '企业创建审核','type' => 1],
            '507869C5-CA58-DC71-4C68-D9C9CED80828-AC30F76B8F' => ['code' => '507869C5-CA58-DC71-4C68-D9C9CED80828-AC30F76B8F','name' => '课题创建审核','type' => 1],
            '9FE2E98F-92F2-7A69-8B1D-66161082ECD8-0FEB304D2C' => ['code' => '9FE2E98F-92F2-7A69-8B1D-66161082ECD8-0FEB304D2C','name' => '个人信息是否需要完善','type' => 2],
        ];
        // 1 是 2 否
        $sys_user = Db::table('sys_userinfo_fields')->where('userfields_group_code','9FE2E98F-92F2-7A69-8B1D-66161082ECD8-0FEB304D2C')
            ->field('status,userfields_group_name,userfields_group_code')
            ->where('state','1')
            ->find();
        $list[$sys_user['userfields_group_code']]['status'] = $sys_user['status'];

        $audit = Db::table('audit_action_switch')
            ->where('state','1')
            ->where('switch_val,code')
            ->select();
        foreach ($audit as $k => $v )
        {
            $list[$v['code']]['status'] = $v['switch_val'];
        }


        return return_json('200200','操作成功',$list);
    }

    /**
     * 编辑
     * code
     * status 1 开启 2 关闭
     * type 列表type字段
    */
    public function edit_config()
    {
        $param = Request::param();
        if(!isset($param['code']) || empty(trim($param['code'])))
        {
            return return_json('200201','请选择要编辑数据',[]);
        }
        if(!isset($param['status']) || !is_numeric($param['status']) || $param['status'] < 1 || $param['status'] > 2)
        {
            return return_json('200201','参数有误',[]);
        }

        if(!isset($param['type']) || !is_numeric($param['type']) || $param['type'] < 1 || $param['type'] > 2)
        {
            return return_json('200201','参数有误',[]);
        }
        switch ($param['type'])
        {
            case 1:
                $table_name = 'audit_action_switch';
                $file_name = 'code';
                $edit_file = 'switch_val';
                break;
            case 2:
                $table_name = 'sys_userinfo_fields';
                $file_name = 'userfields_group_code';
                $edit_file = 'status';
                break;
            default:
                return return_json('200201','参数有误',[]);
        }
        $mess = Db::table($table_name)->where($file_name,$param['code'])->where('state','1')->find();
        if(empty($mess))
        {
            return return_json('200201','参数有误',[]);
        }
        if($mess[$edit_file] == $param['status'])
        {
            return return_json('200200','操作成功',[]);
        }
        switch ($param['status'])
        {
            case 1:
                Db::table($table_name)->where($file_name,$param['code'])->where('state','1')->update([$edit_file => (string)$param['status']]);
                break;
            case 2:
                Db::table($table_name)->where($file_name,$param['code'])->where('state','1')->update([$edit_file => (string)$param['status']]);
                break;
            default:
                return return_json('200201','参数有误',[]);

        }
        return return_json('200200','操作成功',[]);


    }

}
