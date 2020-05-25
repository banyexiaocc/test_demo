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


class Menu extends Authuser
{

    protected $param;
    protected $support_end = ['pc','app','wechat_program'];
//方法已经可用
    public function __construct(){
        parent::__construct();

    }






    /**
     * 获取所有用户管理端目录
     * sys_admin_module [] 模块绑定后台功能
    */
    public function pc_admin_menu_list()
    {
        $list = Db::table('development_module')
            ->where('state','1')
            ->field('id,name,icon,code,status,pid')
            ->order('sort','asc')
            ->where('type','1')
            ->field('id,name,pid,code,sub_level')
            ->select();
//        $sys_admin_module = [];
//
//        if(isset($param['sys_admin_module']) && !empty($param['sys_admin_module']))
//        {
//            $sys_admin_module = json_decode(html_entity_decode($param['sys_admin_module']),true);
//            if(!is_array($sys_admin_module))
//            {
//                $sys_admin_module = [];
//            }
//        }
//        foreach ($list as $k => $v )
//        {
//            if(in_array($v['code'],$sys_admin_module))
//            {
//                $list[$k]['sign'] = 1;
//            }else{
//                $list[$k]['sign'] = 0;
//            }
//        }
        $list = $this -> make_tree($list,'pid');

        return return_json('200200','操作成功',$list);
    }





    /**
     * 生成无限极分类树
     * arr 数组
     *
     */
    public function make_tree($arr,$parent_id = 'parent_id'){
        $refer = array();
        $tree = array();
        foreach($arr as $k => $v){
            $refer[$v['id']] = & $arr[$k]; //创建主键的数组引用
        }
        foreach($arr as $k => $v){
            if(!isset($arr[$k]['children'])){
                $arr[$k]['children'] = [];

            }
            $pid = $v[$parent_id];  //获取当前分类的父级id

            if($pid == 0){
                $tree[] = & $arr[$k];  //顶级栏目
            }else{
                if(isset($refer[$pid])){

                    $refer[$pid]['children'][] = & $arr[$k]; //如果存在父级栏目，则添加进父级栏目的子栏目数组中
                }
            }
        }
        return $tree;
    }

    /**
     *  新增菜单导航
     * type 1 后台菜单 2 前台菜单
     * name 功能模块名称
     * pid 上级功能分组
     * application_platform 此功能支持的多少端 如 pc app 小程序
     * icon 图标
     * content 功能描述
     * status 1施工中 2上线 3过期 4禁用
     * is_jurisdiction 是否公共
     * sort
     */
    public function add_menu()
    {
        $param = Request::param();
        if(!isset($param['name']) || empty(trim($param['name'],' ')))
        {
            return return_json('200201','功能模块名称不能为空',[]);
        }
        if(!isset($param['icon']))
        {
            $param['icon'] = '';
//            return return_json('200201','功能图标不能为空',[]);
        }
        if(!isset($param['content']))
        {
            $param['content'] = '';
//            return return_json('200201','功能图标不能为空',[]);
        }
        if(!isset($param['sort']) || !is_numeric($param['sort']))
        {
            $param['sort'] = 1;
        }
        if(!isset($param['type']) || !is_numeric($param['type']) || $param['type'] < 1 || $param['type'] > 2)
        {
            return return_json('200201','菜单类型有误',[]);
        }
        if(!isset($param['is_jurisdiction']) || !is_numeric($param['is_jurisdiction']) || $param['is_jurisdiction'] < 1 || $param['is_jurisdiction'] > 2)
        {
            return return_json('200201','请设置菜单是否是公共菜单',[]);
        }

            if(!isset($param['application_platform']) || empty($param['application_platform']))
            {
                return return_json('200201','请选择该功能可应用的平台',[]);
            }
            $application_platform = json_decode(html_entity_decode($param['application_platform']),true);
            if(!is_array($application_platform) || count($application_platform) < 1)
            {
                return return_json('200201','请设置该功能可应用的平台',[]);
            }
            $application_platform = array_unique($application_platform);
            $application = [];
            foreach ($application_platform as $k => $v )
            {
                if(!isset($v['name']) || empty($v['name']))
                {
                    return return_json('200201',' 请填写支持的应用平台',[]);
                }
                if(!isset($v['img']) || empty($v['img']))
                {
                    return return_json('200201',' 请添加对应平台图标',[]);
                }
//                if(!isset($v['content']) || empty($v['content']))
//                {
//                    $v['content'] = '';
//                }
                if(!in_array($v['name'],$this -> support_end))
                {
                    return return_json('200201',$v.' 是不存在的可选应用平台',[]);
                }
                $application[] = ['name' => $v,'img' => $v['img'],'content' => ''];
            }

        if(!isset($param['pid']) || empty($param['pid']))
        {
//            return return_json('200201','请选择功能模块所属分组',[]);
            $param['pid'] = '0';
            $path = '';
            $level = "1";
            $code_pid = '0';
            $code_path = '';

        }else{
            $development = Db::table('development_module')
                ->where('type',(string)$param['type'])
                ->where('id',$param['pid'])
                ->where('state','1')
                ->whereIn('status','1,2')
                ->find();
            if(empty($development))
            {
                return return_json('200201','选择的功能分组不存在',[]);
            }
            if($development['is_jurisdiction'] == 1)
            {
                if($param['is_jurisdiction'] == 2)
                {
                    return return_json('200201','上级权限为非开放权限，子级不可开放',[]);
                }
            }

            $path = $development['path'];
            $level = $development['level'] + 1;
            $code_pid = $development['code'];
            $code_path = $development['code_path'];
        }
        if(!isset($param['status']) || !is_numeric($param['status']) || $param['status'] < 1 || $param['status'] > 4)
        {
            $param['status'] = '1';
        }

        $data = [
            'type' => (string)$param['type'],
            'is_jurisdiction' => (string)$param['is_jurisdiction'],
            'sys_admin_module' => [],
            'icon' => $param['icon'],
            'name' => $param['name'],
            'code' => create_uuid(time()),
            'application_platform' => $application,
            'create_time' => date('Y-m-d H:i:s'),
            'pcadmin_href' => '',
            'app_href' => '',
            'pc_href' => '',
            'wechat_href' => '',
            'state' => '1',
            'status' => (string)$param['status'],
            'api_group_code_list' => [],
            'sort' => (int)$param['sort'],
            'pid' => $param['pid'],
            'code_pid' => $code_pid,
            'content' => $param['content'],
            'sub_level' => '1',
            'level' => (string)$level,

        ];
        $id = Db::table('development_module') -> insertGetId($data);
        if(empty($id))
        {
            return return_json('200204','操作失败',[]);
        }
        if($param['pid'])
        {
            Db::table('development_module') -> where('id',$id) ->update(['sub_level' => '2']);
        }
        Db::table('development_module') -> where('id',$id) ->update(['path' => rtrim($path,',').','.$id.',','code_path' => rtrim($code_path,',').','.$data['code'].',']);
        return return_json('200200','操作成功',['id' => $id]);
    }

    /**
     * 获取菜单列表
     * type 1 后台功能 2 前台功能
     * pid 上级功能主键id
     * page
     * size
     * key 搜索条件
    */

    public function menu_list()
    {
        $param = Request::param();
        if(!isset($param['type']) || !is_numeric($param['type']) || $param['type'] < 1 || $param['type'] > 2)
        {
            return return_json('200201','请选择要查看前台菜单还是后台菜单',[]);
        }
        if(!isset($param['pid']) || empty($param['pid']))
        {
            $param['pid'] = '0';
        }
        if(!isset($param['page']) || !is_numeric($param['page']) || $param['page'] < 1)
        {
            $param['page'] = 1;
        }
        if(!isset($param['size']) || !is_numeric($param['size']) || $param['size'] < 1)
        {
            $param['size'] = 50;
        }
        $list = Db::table('development_module')
            -> where('pid',$param['pid'])
            ->where('state','1')
            ->page($param['page'],$param['size'])
            ->field('id,name,icon,code,status')
            ->order('sort','asc')
            ->select();
        return return_json('200200','操作成功',$list);
    }


    /**
     * 功能模块详情
     * id 主键id
    */
    public function menu_details()
    {
        $param = Request::param();
        if(!isset($param['id']) || empty($param['id']))
        {
            return return_json('200201','请选择要查看的功能详情',[]);
        }

        $mess = Db::table('development_module')
            ->where('state','1')
            ->where('id',$param['id'])
            ->field('name,code,status,sort,is_jurisdiction,api_group_code_list,application_platform,icon,content,pcadmin_href,app_href,pc_href,wechat_href,sys_admin_module,resource_type_code_list')
            ->find();
        if(empty($mess))
        {
            return return_json('200201','不存在的功能模版详情',[]);
        }
        return return_json('200200','操作成功',$mess);
    }

    /**
     * 获取所有功能模块组
     * page 页码
     * size 条数
    */
    public function api_group_list()
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
        $list = Db::table('api_url_group')
            ->where('status','1')
            ->where('state','1')
            ->field('id,code,name')
            ->page($param['page'],$param['size'])
            ->select();
        return return_json('200201','操作成功',$list);
    }

    /**
     * 编辑模块信息
     * id 模块主键id
     * name 功能模块名称
     * icon 图标
     * application_platform 此功能支持的多少端 如 pc app 小程序
     * content 功能描述
     * status 1施工中 2上线 3过期 4禁用
     * sort 排序
     * pcadmin_href 管理员端 标记
     * app_href app 端 编辑
     * pc_href pc 端 标记
     * wechat_href 小程序 端 标记
     * sys_admin_module 绑定的对应管理员功能 code
     * api_group_code_list 关联接口分组 code
     */

    public function edit_menu()
    {
        $param = Request::param();
        if(!isset($param['id']) || empty($param['id']))
        {
            return return_json('200201','请选择要编辑的模块',[]);
        }
        if(!isset($param['name']) || empty(trim($param['name'],' ')))
        {
            return return_json('200201','功能模块名称不能为空',[]);
        }
        if(!isset($param['icon']))
        {
            $param['icon'] = '';
//            return return_json('200201','功能图标不能为空',[]);
        }
        if(!isset($param['content']))
        {
            $param['content'] = '';
//            return return_json('200201','功能图标不能为空',[]);
        }
        if(!isset($param['sort']) || !is_numeric($param['sort']))
        {
            $param['sort'] = 1;
        }
        $mess = Db::table('development_module')
            ->where('id',$param['id'])
            ->where('state','1')
            ->find();
        if(empty($mess))
        {
            return return_json('200201','要编辑的信息不存在',[]);
        }

        if(!isset($param['application_platform']) || empty($param['application_platform']))
        {
            return return_json('200201','请选择该功能可应用的平台',[]);
        }
        $application_platform = json_decode(html_entity_decode($param['application_platform']),true);
        if(!is_array($application_platform) || count($application_platform) < 1)
        {
            return return_json('200201','请设置该功能可应用的平台',[]);
        }
        $application_platform = array_unique($application_platform);
        $application = [];
        foreach ($application_platform as $k => $v )
        {
            if(!isset($v['name']) || empty($v['name']))
            {
                return return_json('200201',' 请填写支持的应用平台',[]);
            }
            if(!isset($v['img']) || empty($v['img']))
            {
                return return_json('200201',' 请添加对应平台图标',[]);
            }
//                if(!isset($v['content']) || empty($v['content']))
//                {
//                    $v['content'] = '';
//                }
            if(!in_array($v['name'],$this -> support_end))
            {
                return return_json('200201',$v.' 是不存在的可选应用平台',[]);
            }
            $application[] = ['name' => $v,'img' => $v['img'],'content' => ''];
        }
        if(!isset($param['pcadmin_href']) || empty($param['pcadmin_href']))
        {
            $param['pcadmin_href'] = '';
        }
        if(!isset($param['app_href']) || empty($param['app_href']))
        {
            $param['app_href'] = '';
        }
        if(!isset($param['pc_href']) || empty($param['pc_href']))
        {
            $param['pc_href'] = '';
        }
        if(!isset($param['wechat_href']) || empty($param['wechat_href']))
        {
            $param['wechat_href'] = '';
        }
        if(!isset($param['status']) || $param['status'] < 1 || $param['status'] > 4)
        {
            return return_json('200201','功能模块状态有误',[]);
        }
        if($mess['type'] == 2)
        {
            if(!isset($param['sys_admin_module']) || empty($param['sys_admin_module']))
            {
                $sys_admin_module = [];
            }else{
                $sys_admin_module = json_decode(html_entity_decode($param['sys_admin_module']),true);
                if(!is_array($sys_admin_module))
                {
                    return return_json('200201','选择的对应后台管理功能有误',[]);
                }
            }
        }else{
            $sys_admin_module = [];
        }

        if(!isset($param['api_group_code_list']) || empty($param['api_group_code_list']))
        {
            $api_group_code_list = [];
        }else{
            $api_group_code_list = json_decode(html_entity_decode($param['api_group_code_list']),true);
            if(!is_array($api_group_code_list))
            {
                return return_json('200201','选择的对应后台管理功能有误',[]);
            }
        }

        //resource_type_code_list
        $data = [
            'icon' => $param['icon'],
            'name' => $param['name'],
            'content' => $param['content'],
            'application_platform' => $application,
            'pcadmin_href' => $param['pcadmin_href'],
            'app_href' => $param['app_href'],
            'pc_href' => $param['pc_href'],
            'wechat_href' => $param['wechat_href'],
            'status' => (string)$param['status'],
            'sys_admin_module' => $sys_admin_module,
            'api_group_code_list' => $api_group_code_list
        ];
        Db::table('development_module')
            ->where('id',$param['id'])
            ->update($data);
        return return_json('200201','操作成功',[]);

    }




}
