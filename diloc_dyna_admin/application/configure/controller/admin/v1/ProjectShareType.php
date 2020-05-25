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


class ProjectShareType extends Authuser
{

    protected $param;
    protected $support_end = ['pc','app','wechat_program'];
//方法已经可用
    public function __construct(){
        parent::__construct();
    }

    /**
     * 获取所有可选分类
     * page
     * size
     */
    public function share_way_group_list()
    {
        $param = Request::param();
        $list = Db::table('share_way_group')
            ->where('state','1')
            ->where('status','1')
            ->field('name,code,type,way')
            ->select();

        return return_json('200201','操作成功',$list);
    }

    /**
     * 新增 共享资源
     * pro_id 项目主键id
     * share_way_group_code 所选组
     * way [] 索选方式
     * resource_type_list 包含资源分类 code
     * img 图标
    */
    public function add_share_type()
    {
        $param = Request::param();
        if(!isset($param['pro_id']) || empty($param['pro_id']))
        {
            return return_json('200201','请选择要操作的项目',[]);
        }
        if(!isset($param['name']) || empty($param['name']))
        {
            return return_json('200201','请填写资源分类组合名称',[]);
        }
        if(!isset($param['share_way_group_code']) || empty($param['share_way_group_code']))
        {
            return return_json('200201','请选择搜索分组',[]);
        }
        if(!isset($param['img']) || empty($param['img']))
        {
            return return_json('200201','图标不能为空',[]);
        }
        if(!isset($param['resource_type_list']) || empty($param['resource_type_list']))
        {
            return return_json('200201','请选择包含资源分类',[]);
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
        $share_way_group = Db::table('share_way_group')
            ->where('state','1')
            ->where('status','1')
            ->where('code',$param['share_way_group_code'])
            ->find();
        if(empty($share_way_group))
        {
            return return_json('200201','选择所属分组有误',[]);
        }
        $resource_type_code = json_decode(html_entity_decode($param['resource_type_list']),true);
        if(!is_array($resource_type_code) || count($resource_type_code) < 1)
        {
            return return_json('200201','请选择包含的资源分类',[]);
        }
        $resource_type_list =  Db::table($db_name['db_name'].'.project_base_resource_type')
            ->where('state','1')
            ->whereIn('base_resource_type_code',implode(',',$resource_type_code))
            ->select();

        if(empty($resource_type_list))
        {
            return return_json('200201','选择的资源分类有误',[]);
        }
        $count = Db::table($db_name['db_name'].'.share_type')
            ->where('state','1')
            ->where('name',$param['name'])
            ->find();
        if($count)
        {
            return return_json('200201','已存在的名称，不可重复使用',[]);
        }
        if($share_way_group['type'] == 1)
        {
            if(!isset($param['way']) || empty($param['way']))
            {
                return return_json('200201','请选择可支持的模式',[]);
            }
            $way = array_unique(json_decode(html_entity_decode($param['way']),true));
            if(!is_array($way) || count($way) > 1)
            {
                return return_json('200201','请选择可支持的模式',[]);
            }
            $all_way = array_column($share_way_group['way'],'code');
            foreach ($way as $k => $v )
            {
                if(!in_array($v,$all_way))
                {
                    return return_json('200201','参数有误',[]);
                }

            }
        }else{
            $way = [];
        }
        if(!isset($param['end_time']) || empty($param['end_time']))
        {
            return return_json('200201','请设置到期时间',[]);
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

        $data = [
            'name' => $param['name'],
            'code' => create_uuid(time()),
            'way_group' => $share_way_group['code'],
            'resource_type_list' => array_column($resource_type_list,'base_resource_type_code'),
            'status' => '1',
            'state' => '1',
            'sort' => 1,
            'img' => $param['img'],
            'end_time' => $end_time,
            'pcadmin_href' => $share_way_group['pcadmin_href'],
            'app_href' => $share_way_group['app_href'],
            'pc_href' => $share_way_group['pc_href'],
            'wechat_href' => $share_way_group['wechat_href'],
            'create_time' => date('Y-m-d H:i:s'),
            'type' => $share_way_group['type']
        ];

        $share_type_add = Db::table($db_name['db_name'].'.share_type')
            ->insertGetId($data);
        if(empty($share_type_add))
        {
            return return_json('200204','操作失败',[]);
        }
        if($share_way_group['type'] == 1)
        {
            $model_list = [];
            foreach ($way as $k => $v )
            {
                $model_list[] = [
                    'app_id' => $param['pro_id'],
                    'share_type_code' => $data['code'],
                    'sharing_model_code' => create_uuid(time()),
                    'code' => $v,
                    'description_info' => '',
                    'create_time' => date('Y-m-d H:i:s'),
                    'state' => '1',
                    'sort' => 1,
                    'status' => '1',
                ];
            }
            $add_model = Db::table($db_name['db_name'].'.resource_sharing_model')->insertAll($model_list);
            if(empty($add_model))
            {
                Db::table($db_name['db_name'].'.share_type')->where('id',$share_type_add)->update(['state' => '2']);
            }
        }
        return return_json('200201','操作成功',['id' => $share_type_add]);


    }


    /**
     * 获取项目下共享资源列表
     * pro_id 项目主键id
     * page 页码
     * size 每页条数
     *
    */
    public function share_type_list()
    {
        $param = Request::param();
        if(!isset($param['pro_id']) || empty($param['pro_id']))
        {
            return return_json('200201','请选择要查看的项目',[]);
        }
        if(!isset($param['page']) || !is_numeric($param['page']) || $param['page'] < 1)
        {
            $param['page'] = 1;
        }
        if(!isset($param['size']) || !is_numeric($param['size']) || $param['size'] < 1)
        {
            $param['size'] = 50;
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
        $list = Db::table($db_name['db_name'].'.share_type')
            ->where('state','1')
            ->page($param['page'],$param['size'])
            ->field('name,resource_type_list,end_time,img')
            ->select();
        $resource_type_list = array_column($list,'resource_type_list');
        $code = [];
        foreach ($resource_type_list as $k => $v )
        {
            $code = array_merge($code,$v );
        }
        $code = array_unique($code);
        $resource_name_list = Db::table($db_name['db_name'].'.project_base_resource_type')
            ->whereIn('base_resource_type_code',implode(',',$code))
            ->field('id,name,base_resource_type_code')
            ->select();
        $resource_name_list = $this -> key_change($resource_name_list,'base_resource_type_code');
        foreach ($list as $k => $v )
        {
            $resource_name = [];
            foreach ($v['resource_type_list'] as $key => $val  )
            {
                $resource_name[] = $resource_name_list[$val]['name'];
            }
            $list[$k]['resource_type_name'] = implode('、',$resource_name);
        }
        return return_json('200201','操作成功',$list);
    }

    /**
     * 编辑
    */


    /**
     * 删除项目下共享资源分类
     * pro_id 项目主键id
     * id 列表页主键id
    */
    public function del_share_type()
    {
        $param = Request::param();
        if(!isset($param['pro_id']) || empty($param['pro_id']))
        {
            return return_json('200201','请选择要查看的项目',[]);
        }
        if(!isset($param['id']) || empty($param['id']))
        {
            return return_json('200201','请选择要删除的资源分类组合',[]);
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
        $mess = Db::table($db_name['db_name'].'.share_type')
            ->where('state','1')
            ->where('id',$param['id'])
            ->find();
        if(empty($mess))
        {
            return return_json('200201','要删除的数据不存在',[]);
        }
        $del = Db::table($db_name['db_name'].'.share_type')
            ->where('state','1')
            ->where('id',$param['id'])
            ->update(['state' => '2']);
        if(empty($del))
        {
            return return_json('200201','操作失败',[]);
        }
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
