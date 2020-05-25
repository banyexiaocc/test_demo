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


class Audit extends Authuser
{

    protected $param;
    protected $support_end = ['pc','app','wechat_program'];
//方法已经可用
    public function __construct(){
        parent::__construct();

    }

    /**
     * 新增审核功能
     * name
     * icon
    */
    public function add_audit()
    {
        $param = Request::param();
        if(!isset($param['name']) || empty(trim($param['name'],' ')))
        {
            return return_json('200201','名称不能为空',[]);
        }
        if(!isset($param['icon']) ||empty(trim($param['icon'],' ')))
        {
            $param['icon'] = '';
        }
        $mess = Db::table('audit_type_info')
            ->where('state','1')
            ->where('name',trim($param['name'],' '))
            ->find();

        if($mess)
        {
            return return_json('200201','已存在的名称',[]);
        }
        $type = Db::table('audit_type_info')
//            ->where('state','1')
            ->order('type','desc')
            ->find();
        if(empty($type))
        {
            $types = 1;
        }else{
            $types = (int)($type['type'] + 1);
        }
        $id = Db::table('audit_type_info')
            ->insertGetId([
                'name' => trim($param['name'],' '),
                'code' => create_uuid(time().$this ->return_random()),
                'type' => $types,
                'state' => '1',
                'status' => '1',
                'icon' => $param['icon'],
                'sort' => 1,
                'create_time' => date('Y-m-d H:i:s')]);
        if(empty($id))
        {
            return return_json('200204','操作失败',[]);
        }
        return return_json('200201','操作成功',['id' => $id]);
    }

    /**
     * 获取审核功能列表
     *
    */
    public function audit_list()
    {
        $param = Request::param();
        if(!isset($param['page']) || !is_numeric($param['page']) || $param['page'] < 1)
        {
            $param['page'] = 1;
        }
        if(!isset($param['size']) || !is_numeric($param['size']) || $param['size'] < 1)
        {
            $param['size'] = 50;
        }
        $where = [];
        if(isset($param['key']) && !empty(trim($param['key'],' ')))
        {
            $where[] = ['name','like',trim($param['key'],' ')];
        }
        if(isset($param['status']) && !empty(trim($param['status'],' ')))
        {
            $status = json_decode(html_entity_decode($param['status']),true);
            $where[] = ['status','in',implode(',',$status)];
        }
        $list = Db::table('audit_type_info')
            ->where('state','1')
            ->where($where)
            ->page($param['page'],$param['size'])
            ->field('id,name,icon,code,status,sort')
            ->select();
        return return_json('200200','操作成功',$list);
    }

    /**
     * 编辑审核信息
     * id 主键id
     * name 名称
     * status 1 正常 2 禁用
     * sort 排序
     * icon 功能图标
    */
    public function edit_audit()
    {
        $param = Request::param();
        if(!isset($param['id']) || empty(trim($param['id'],' ')))
        {
            return return_json('200201','请选择要编辑的信息',[]);
        }
        if(!isset($param['name']) || empty(trim($param['name'],' ')))
        {
            return return_json('200201','名称不能为空',[]);
        }
        if(!isset($param['sort']) || !is_numeric($param['sort']))
        {
            $param['sort'] = 1;
        }
        if(!isset($param['icon']) || empty(trim($param['icon'],' ')))
        {
            $param['icon'] = '';
        }
        if(!isset($param['status']))
        {
            return return_json('200201','状态不能为空',[]);
        }
        switch ($param['status'])
        {
            case 1:
                $param['status'] = '1';
                break;
            case 2:
                $param['status'] = '2';
                break;
            default:
                return return_json('200201','状态不能为空',[]);

        }
        $mess = Db::table('audit_type_info')
            ->where('state','1')
            ->where('id',$param['id'])
            ->find();

        if(empty($mess))
        {
            return return_json('200201','要编辑的信息不存在',[]);
        }
        $count = Db::table('audit_type_info')->where('state','1')->where('name',trim($param['name'],' '))->where('id','<>',$mess['id'])->find();
        if($count)
        {
            return return_json('200201','已存在的名称',[]);
        }
        $data = [
            'name' => $param['name'],
            'icon' => $param['icon'],
            'status' => $param['status'],
            'sort' => (int)$param['sort'],
        ];
        Db::table('audit_type_info')->where('state','1')->where('id',$mess['id'])->update($data);
        return return_json('200200','操作成功',[]);
    }


    public function return_random()
    {
        return mt_rand(10000000,99999999);
    }


}
