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


class ProjectResourceType extends Authuser
{

    protected $param;
    protected $support_end = ['pc','app','wechat_program'];
//方法已经可用
    public function __construct(){
        parent::__construct();
    }

    /**
     * 获取所有可选资源
     * page
     * size
    */
    public function resource_type_list()
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
        $list = Db::table('base_resource_type')
            ->where('state','1')
            ->where('status','1')
            ->page($param['page'],$param['size'])
            ->field('id,icon,name,code')
            ->where('if_dictionary','1')
            ->select();
        return return_json('200201','操作成功',$list);


    }

    /**
     * 设置用户可选资源分类
     * details ["base_resource_type_code":"资源分类唯一码","end_time"]
     * pro_id 要设置的项目
    */
    public function add_resource_type()
    {
//        echo json_encode([
//            ['code' => 'BA24F8CD-C184-60AF-01DF-95C7EB9380EC-411BD56756','end_time' => '2020-03-11'],
//            ['code' => '34D5EB44-F878-D826-37F7-2E0CB923DA64-41D3DD9224','end_time' => '2020-03-20'],
//
//        ]);die;
        $param = Request::param();
        if(isset($param['details']) && !empty($param['details']))
        {
            $details = json_decode(html_entity_decode($param['details']),true);
            if(!is_array($details) || count($details) < 1)
            {
                return return_json('200201','参数有误3',[]);
            }
        }else{
            return return_json('200201','请选择要设置的',[]);
        }

        if(!isset($param['pro_id']) || empty($param['pro_id'])){
            return return_json('200201','请选择要设置的项目',[]);
        }
        $project_mess = Db::table('project')->where('id',$param['pro_id'])->where('state','1')->find();
        if(empty($project_mess))
        {
            return return_json('200201','不存在的项目信息',[]);
        }
        $db_name = Db::table('project_config')->where('app_id',$project_mess['id'])->where('status','1')->where('state','1')->field('id,db_name')->find();
        if(empty($db_name))
        {
            return return_json('200201','该项目尚未配置项目信息',[]);
        }
        if(empty($db_name['db_name']))
        {
            return return_json('200201','该项目尚未配置项目信息',[]);
        }
        $code = [];
        foreach ($details as $k => $v )
        {
            if(!isset($v['base_resource_type_code']) || empty($v['base_resource_type_code']))
            {
                return return_json('200201','选择的资源分类信息有误',[]);
            }
            if(!isset($v['end_time']) || empty($v['end_time']))
            {
                return return_json('200201','请设置到期时间',[]);
            }
            $end_time = $this -> ver_date($v['end_time']);
            if(empty($end_time))
            {
                return return_json('200201','设置的到期日期有误',[]);
            }
            if($end_time <= date('Y-m-d'))
            {
                return return_json('200201','到期时间不可小于或等于当前时间',[]);
            }
            $code[] = $v['base_resource_type_code'];
            $details[$k]['end_time'] = $end_time;
        }
        $resource_type_list = Db::table('base_resource_type')
            ->where('state','1')
            ->where('status','1')
            ->where('if_dictionary','1')
            ->whereIn('code',implode(',',$code))
            ->select();
        if(empty($resource_type_list))
        {
            return return_json('200201','参数有误1',[]);
        }
        $project_type = Db::table($db_name['db_name'].'.project_base_resource_type')
            ->whereIn('base_resource_type_code',implode(',',$code))
            ->where('state','1')
            ->find();
        if($project_type)
        {
            return return_json('200201',$project_type['name'].' 已添加不可重复操作',[]);
        }

        $resource_type_list = $this -> key_change($resource_type_list,'code');
        $data = [];
        foreach ($details as $k => $v )
        {

            if(!isset($resource_type_list[$v['base_resource_type_code']]))
            {
                return return_json('200201','参数有误2',[]);
            }
            $data[] = [
                'name' => $resource_type_list[$v['base_resource_type_code']]['name'],
                'icon' => $resource_type_list[$v['base_resource_type_code']]['icon'],
                'base_resource_type_code' => $v['base_resource_type_code'],
                'end_time' => $v['end_time'],
                'state' => '1',
                'status' => '1',
                'sort' => (int)$resource_type_list[$v['base_resource_type_code']]['sort'],
                'create_time' => date('Y-m-d H:i:s'),
            ];
            unset($resource_type_list[$v['base_resource_type_code']]);

        }

        $add = Db::table($db_name['db_name'].'.project_base_resource_type')
            ->insertAll($data);
        if(empty($add))
        {
            return return_json('200204','操作失败',[]);
        }
        return return_json('200200','操作成功',[]);


    }



    /**
     * 获取项目下资源分类列表
     * pro_id 项目主键id
     * page 页码
     * size 条数
    */
    public function project_resource_type_list()
    {
        $param = Request::param();
        if(!isset($param['pro_id']) || empty($param['pro_id']))
        {
            return return_json('200201','请选择要查看的项目',[]);
        }
        $db_name = Db::table('project_config')->where('app_id',$param['pro_id'])->where('status','1')->where('state','1')->field('id,db_name')->find();
        if(empty($db_name))
        {
            return return_json('200201','该项目尚未配置项目信息',[]);
        }
        if(empty($db_name['db_name']))
        {
            return return_json('200201','该项目尚未配置项目信息',[]);
        }
        if(!isset($param['page']) || !is_numeric($param['page']) || $param['page'] < 1)
        {
            $param['page'] = 1;
        }
        if(!isset($param['size']) || !is_numeric($param['size']) || $param['size'] < 1)
        {
            $param['size'] = 50;
        }
        $list = Db::table($db_name['db_name'].'.project_base_resource_type')
            ->where('state','1')
            ->where('status','1')
            ->page($param['page'],$param['size'])
            ->field('id,name,base_resource_type_code,end_time')
            ->select();
        return return_json('200200','操作成功',$list);
    }

    /**
     * 编辑项目下资源分类
     * pro_id 项目主键id
     * id 主键id
     * end_time 到期时间
    */
    public function edit_project_resource_type()
    {
        $param = Request::param();
        if(!isset($param['pro_id']) || empty($param['pro_id']))
        {
            return return_json('200201','请选要操作的项目',[]);
        }
        if(!isset($param['id']) || empty($param['id']))
        {
            return return_json('200201','请选择要编辑的资源分类',[]);
        }
        $db_name = Db::table('project_config')->where('app_id',$param['pro_id'])->where('status','1')->where('state','1')->field('id,db_name')->find();
        if(empty($db_name))
        {
            return return_json('200201','该项目尚未配置项目信息',[]);
        }
        if(empty($db_name['db_name']))
        {
            return return_json('200201','该项目尚未配置项目信息',[]);
        }
        $mess = Db::table($db_name['db_name'].'.project_base_resource_type')
            ->where('state','1')
            ->where('id',$param['id'])
            ->where('status','1')
            ->find();
        if(empty($mess))
        {
            return return_json('200201','要编辑的资源分类不存在',[]);
        }
        if(!isset($param['end_time']) || empty($param['end_time']))
        {
            return return_json('200201','请填写到期时间',[]);
        }
        $end_time = $this -> ver_date($param['end_time']);
        if(empty($end_time))
        {
            return return_json('200201','设置的到期日期有误',[]);
        }
        if($end_time <= date('Y-m-d'))
        {
            return return_json('200201','到期时间不可小于或等于当前时间',[]);
        }
        Db::table($db_name['db_name'].'.project_base_resource_type')
            ->where('state','1')
            ->where('id',$param['id'])
            ->where('status','1')
            ->update(['end_time' => $param['end_time']]);
        return return_json('200200','操作成功',[]);

    }

    /**
     * 删除项目下资源分类
     * pro_id 项目主键id
     * id 主键id
     */
    public function del_project_resource_type()
    {
        $param = Request::param();
        if(!isset($param['pro_id']) || empty($param['pro_id']))
        {
            return return_json('200201','请选要操作的项目',[]);
        }
        if(!isset($param['id']) || empty($param['id']))
        {
            return return_json('200201','请选择要删除的资源分类',[]);
        }
        $db_name = Db::table('project_config')->where('app_id',$param['pro_id'])->where('status','1')->where('state','1')->field('id,db_name')->find();
        if(empty($db_name))
        {
            return return_json('200201','该项目尚未配置项目信息',[]);
        }
        if(empty($db_name['db_name']))
        {
            return return_json('200201','该项目尚未配置项目信息',[]);
        }
        $mess = Db::table($db_name['db_name'].'.project_base_resource_type')
            ->where('state','1')
            ->where('id',$param['id'])
            ->where('status','1')
            ->find();
        if(empty($mess))
        {
            return return_json('200201','要删除的资源分类不存在',[]);
        }

        Db::table($db_name['db_name'].'.project_base_resource_type')
            ->where('state','1')
            ->where('id',$param['id'])
            ->where('status','1')
            ->update(['state' => '2']);
        return return_json('200200','操作成功',[]);

    }

    /**
     * 转换数据，数组id 为下标
     */
    protected function key_change($list,$key = 'id')
    {
        $data = [];
        foreach ($list as $k => $v )
        {
            $data[$v[$key]] = $v;
        }
        return $data;
    }

    /**
     * 验证日期是否有效 并返回
    */
    public function ver_date($data,$type = 1)
    {
        $date = strtotime($data);
        if(checkdate(date('m'),date('d'),date('Y')))
        {
            if($type == 1)
            {

                return date('Y-m-d',$date);
            }else{
                return date('Y-m-d H:s:s',$date);

            }
        }else{
            return false;
        }
    }


}
