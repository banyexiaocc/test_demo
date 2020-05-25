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
use think\route\RuleItem;


class ProjectAudit extends Authuser
{

    protected $param;
    protected $support_end = ['pc','app','wechat_program'];
//方法已经可用
    public function __construct(){
        parent::__construct();

    }

    /**
     * 获取审核功能列表
     *
     */
    public function audit_type_list()
    {
        $param = Request::param();

        $where = [];
        if(isset($param['key']) && !empty(trim($param['key'],' ')))
        {
            $where[] = ['name','like',trim($param['key'],' ')];
        }
        $list = Db::table('audit_type_info')
            ->where('state','1')
            ->where($where)
            ->where('status','1')
            ->order('sort','asc')
            ->field('id,name,icon,code')
            ->select();
        return return_json('200200','操作成功',$list);
    }


    /**
     * 项目新增审核
     * name 名称
     * codes 要关联的审核名称
     * pro_id 对应app主键id
    */
    public function add_project_audit()
    {
        $param = Request::param();
        if(!isset($param['pro_id']) || empty($param['pro_id']))
        {
            return return_json('200201','请选要操作的项目',[]);
        }
        if(!isset($param['name']) || empty(trim($param['name'],' ')))
        {
            return return_json('200201','名称不能为空',[]);
        }
        if(!isset($param['codes']) || empty(trim($param['codes'],' ')))
        {
            return return_json('200201','请选择要关联的审核信息',[]);
        }
        $db_name = Db::table('project_config')
            ->where('app_id',$param['pro_id'])
            ->where('status','1')
            ->where('state','1')
            ->field('id,db_name')
            ->find();
        if(empty($db_name))
        {
            return return_json('200201','该项目尚未配置项目信息',[]);
        }
        if(empty($db_name['db_name']))
        {
            return return_json('200201','该项目尚未配置项目信息',[]);
        }
        $count = Db::table($db_name['db_name'].'.audit')
            ->where('state','1')
            ->where('name',trim($param['name'],' '))
            ->find();
        if($count)
        {
            return return_json('200201','已存在的名称',[]);
        }
        $codes = json_decode(html_entity_decode($param['codes']),true);
        if(!is_array($codes) || count($codes) < 1)
        {
            return return_json('200201','请选择要关联的审核项',[]);
        }
        $type_count = Db::table($db_name['db_name'].'.audit_type')
            ->where('state','1')
            ->whereIn('code',implode(',',$codes))
            ->find();
        if($type_count)
        {
            return return_json('200201','包含已存在的审核项',[]);
        }

        $add_type_list = Db::table('audit_type_info')
            ->where('state','1')
            ->where('status','1')
            ->whereIn('code',implode(',',$codes))
            ->select();
        if(empty($add_type_list))
        {
            return return_json('200201','要关联的审核项不存在',[]);
        }
        $audit_type_data  = [];
        $audit_type_id = [];
        $audit_type_name = [];
        foreach ($add_type_list as $k => $v )
        {
            $audit_type_data[] = [
                'name' => $v['name'],
                'code' => $v['code'],
                'type' => $v['type'],
                'state' => '1',
                'status' => '1',
                'sort' => 1,
                'create_time' => date('Y-m-d H:i:s'),
            ];
            $audit_type_name[] = $v['name'];
        }
        $data = [
            'name' => $param['name'],
            'state' => '1',
            'status' => '1',
            'audit_type_name' => $audit_type_name,
            'create_time' => date('Y-m-d H:i:s'),
            'sort' => 1,
        ];
        $id = Db::table($db_name['db_name'].'.audit')->insertGetId($data);
        if(empty($id))
        {
            return return_json('200204','操作失败',[]);
        }
        foreach ($audit_type_data as $k => $v )
        {
            $audit_type_data[$k]['audit_id'] = $id;
        }
        Db::table($db_name['db_name'].'.audit_type')->insertAll($audit_type_data);
        return return_json('200200','操作成功',['id' => $id]);

    }

    /**
     * 获取项目下审核信息
     * pro_id 项目主键id
    */
    public function project_audit_list()
    {
        $param = Request::param();
        if(!isset($param['pro_id']) || empty(trim($param['pro_id'],' ')))
        {
            return return_json('200201','请选择要查看的项目',[]);
        }
        $db_name = Db::table('project_config')
            ->where('app_id',$param['pro_id'])
            ->where('status','1')
            ->where('state','1')
            ->field('id,db_name')
            ->find();
        if(empty($db_name))
        {
            return return_json('200201','该项目尚未配置项目信息',[]);
        }
        if(empty($db_name['db_name']))
        {
            return return_json('200201','该项目尚未配置项目信息',[]);
        }

        $list = Db::table($db_name['db_name'].'.audit')
            ->where('state','1')
            ->field('name,audit_type_name')
            ->select();
        return return_json('200200','操作成功',$list);
    }

    /**
     * 删除审核类型
     * pro_id 项目主键id
     * id 要删除的审核类型
    */

    public function del_project_audit()
    {
        $param = Request::param();
        if(!isset($param['pro_id']) || empty(trim($param['pro_id'],' ')))
        {
            return return_json('200201','请选择要查看的项目',[]);
        }
        if(!isset($param['id']) || empty(trim($param['id'],' ')))
        {
            return return_json('200201','请选择要删除的信息',[]);
        }
        $db_name = Db::table('project_config')
            ->where('app_id',$param['pro_id'])
            ->where('status','1')
            ->where('state','1')
            ->field('id,db_name')
            ->find();
        if(empty($db_name))
        {
            return return_json('200201','该项目尚未配置项目信息',[]);
        }
        if(empty($db_name['db_name']))
        {
            return return_json('200201','该项目尚未配置项目信息',[]);
        }

        $mess = Db::table($db_name['db_name'].'.audit')
            ->where('state','1')
            ->field('name,audit_type_name')
            ->where('id',$param['id'])
            ->find();
        if(empty($mess))
        {
            return return_json('200201','要删除的信息不存在',[]);
        }
        Db::table($db_name['db_name'].'.audit')
            ->where('state','1')
            ->field('name,audit_type_name')
            ->where('id',$param['id'])
            ->update(['state' => '2']);
        Db::table($db_name['db_name'].'.audit_type')
            ->where('state','1')
            ->where('audit_id',$mess['id'])
            ->update(['state' => '2']);
        return return_json('200200','操作成功',[]);
    }



    public function return_random()
    {
        return mt_rand(10000000,99999999);
    }


}
