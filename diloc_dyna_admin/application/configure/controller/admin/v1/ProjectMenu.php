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


class ProjectMenu extends Authuser
{
    protected $support_end = ['pc','app','wechat_program'];

    public function __construct(){
        parent::__construct();

    }
    /**
     * 获取平台所有可选用户功能
    */
    public function system_module_list()
    {
        $list = Db::table('development_module')
            ->where('state','1')
            ->where('status','2')
            ->field('id,name,pid,code,application_platform')
            ->where('type','2')
            ->select();
        $menu = new Menu();
        foreach ($list as $k => $v )
        {
            $list[$k]['platform'] = array_column($v['application_platform'],'name');
        }
        $list = $menu -> make_tree($list,'pid');
        return return_json('200200','操作成功',$list);
    }

    /**
     * 新增用户功能
     * pro_id 项目主键id
     * id 所选用户功能主键
     * pid 上级主键id
     * end_time
     * module_type_alias 功能名称
     * sort 排序
     * application_platform 功能支持平台
     *
    */

    public function add_project_user_module()
    {
        $param = Request::param();
        if(!isset($param['pro_id']) || empty($param['pro_id']))
        {
            return return_json('200201','请选择要添加的项目',[]);
        }
        // 获取项目信息
        $project_mess = Db::table('project')->where('id',$param['pro_id'])->where('state','1')->find();
        if(empty($project_mess))
        {
            return return_json('200201','不存在的项目信息',[]);
        }
        // 获取项目数据库名称
        $db_name = Db::table('project_config')
            ->where('app_id',$param['pro_id'])
            ->where('status','1')
            ->where('state','1')
            ->field('id,db_name')
            ->find();
        if(empty($db_name['db_name']))
        {
            return return_json('200201','请先设置项目数据库',[]);
        }

        if(!isset($param['pid']) || empty($param['pid']))
        {
            $param['pid'] = '0';
            $path = '';
        }else{

            $firt = Db::table($db_name['db_name'].'.project_opening_module_relation')
                ->where('user_type','2')
                ->where('id',$param['pid'])
                ->where('state','1')
                ->where('status','1')
                ->find();
            if (empty($firt))
            {
                return return_json('200201','所属分组编号有误',[]);
            }
            $path = $firt['path'];
        }
        if(!isset($param['module_type_alias']) || empty($param['module_type_alias']))
        {
            return return_json('200201','功能名称不能为空',[]);
        }
        if(!isset($param['code']) || empty($param['code']))
        {
            return return_json('200201','请选择对应功能库功能',[]);
        }

        $development_module= Db::table('development_module')
            ->where('code',$param['code'])
            ->where('status','2')
            ->where('state','1')
            ->where('type','2')
            ->find();
        if(empty($development_module))
        {
            return return_json('200201','选择的对应功能模块有误',[]);
        }
        $whether = Db::table($db_name['db_name'].'project_opening_module_relation')
            ->where('user_type','2')
            ->where('module_code',$param['code'])
            ->where('state','1')
            ->find();
        if($whether)
        {
            return return_json('200201','该项目已添加了该功能，不可重复添加',[]);
        }
        if(!isset($param['end_time']) || empty($param['end_time']))
        {
            return return_json('200201','请设置功能结束时间',[]);
        }
        $end_time = $this -> ver_date($param['end_time']);
        if(empty($end_time))
        {
            return return_json('200201','结束时间有误',[]);
        }
        if($end_time <= date('Y-m-d'))
        {
            return return_json('200201','结束时间不能小于或等于当前时间',[]);
        }
        $param['end_time'] = $end_time;

        if(!isset($param['sort']) || !is_numeric($param['sort']))
        {
            $param['sort'] = 1;
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
        $development_module_platform = array_column($development_module['application_platform'],'name');
        foreach ($application_platform as $k => $v )
        {
            if(!in_array($v,$development_module_platform))
            {
                return return_json('200201',$v.' 是不存在的可选应用平台',[]);
            }
            $application[] = ['name' => $v,'img' => '','content' => ''];
        }
        $data = [
            'type' => "2",
            'user_type' => '2',
            'is_jurisdiction' => $development_module['is_jurisdiction'],
            'module_code' => $development_module['code'],
            'module_type_alias' => $param['module_type_alias'],
            'pid' => $param['pid'],
            'path' => $path,
            'application_platform' => $application,
            'pcadmin_href' => $development_module['pcadmin_href'],
            'app_href' => $development_module['app_href'],
            'pc_href' => $development_module['pc_href'],
            'wechat_href' => $development_module['wechat_href'],
            'end_time' => $param['end_time'],
            'state' => '1',
            'status' => '1',
            'sort' => (int)$param['sort'],
            'create_time' => date('Y-m-d H:i:s'),
            'sys_admin_module'=> $development_module['sys_admin_module'],
        ];

        $add_id = Db::table($db_name['db_name'].'.project_opening_module_relation')
            ->insertGetId($data);
        if(empty($add_id))
        {
            return return_json('200201','操作失败',[]);
        }
        Db::table($db_name['db_name'].'.project_opening_module_relation')
            -> where('id',$add_id)->update(['path' => rtrim($path,',').','.$add_id.',']);
        if($param['pid'])
        {
            $firt = Db::table($db_name['db_name'].'project_opening_module_relation')
                ->where('user_type','2')
                ->where('id',$param['pid'])
                ->update(['type' => '1']);
        }
        // 添加对应后台功能
        $this -> project_admin_module($development_module['sys_admin_module'],$param['end_time'],$db_name['db_name'],$add_id);

        return return_json('200201','操作成功',['id' => $add_id]);
    }

    /**
     * 前台功能开通 设定对应后台功能
    */
    protected  function project_admin_module($code,$end_time,$db_name,$user_model_id)
    {

        $list = Db::table('development_module')
            ->whereIn('code',implode(',',$code))
            ->where('status','2')
            ->where('state','1')
            ->field('path,code_path')
            ->select();
        if(empty($list))
        {
            return true;
        }
        $path = [];
        $code_path = [];
        foreach ($list as $k => $v )
        {
            $path = array_merge($path,explode(',',trim($v['path'],',')));
            $code_path = array_merge($code_path,explode(',',trim($v['code_path'],',')));
        }
        // 查询已存在的

        $project_module = Db::table($db_name.'.project_opening_module_relation')
            ->where('user_type','1')
            ->whereIn('module_code',$code_path)
            ->where('state','1')
            ->select();
        $project_code_list = [];
        foreach ($project_module as $k => $v )
        {
            array_push($v['related_sources'],['id' => $user_model_id,'end_time' => $end_time]);
            if($end_time >= $v['end_time'] )
            {
                Db::table($db_name.'.project_opening_module_relation')->where('id',$v['id'])->update(['end_time' => $end_time,'related_sources' => $v['related_sources']]);
            }else{
                Db::table($db_name.'.project_opening_module_relation')->where('id',$v['id'])->update(['related_sources' => $v['related_sources']]);

            }

            $project_code_list[] = $v['module_code'];
        }
        // 最终要添加的后台菜单
        $add_project_admint_code = array_diff($code_path,$project_code_list);
        $all_modeul = Db::table('development_module')
            ->whereIn('code',implode(',',$add_project_admint_code))
            ->where('status','2')
            ->where('state','1')
            ->order('level','asc')
            ->select();
        foreach ($all_modeul as $k => $v )
        {
            // 一级菜单直接添加
            if($v['level'] == 1)
            {
                $path = '';
                $pid = '0';


            }else{

                // 如果不是一级，查找上级
                $first_code = explode(',',trim($v['code_path'],','))[$v['level'] -2];
                $first = Db::table($db_name.'.project_opening_module_relation')
                    ->where('module_code',$first_code)
                    ->find();
                $pid = $first['id'];
                $path = $first['path'];

            }
            $id = Db::table($db_name.'.project_opening_module_relation')
                ->insertGetId([
                    'type' => $v['sub_level'],
                    'is_jurisdiction' => $v['is_jurisdiction'],
                    'pid' => $pid,
                    'path' => $path,
                    'user_type' => '1',
                    'module_code' => $v['code'],
                    'module_type_alias' => $v['name'],
                    'application_platform' => [],
                    'pcadmin_href' => $v['pcadmin_href'],
                    'app_href' => $v['app_href'],
                    'pc_href' => $v['pc_href'],
                    'wechat_href' => $v['wechat_href'],
                    'end_time' => $end_time,
                    'state' => '1',
                    'status' => '1',
                    'sort' => $v['sort'],
                    'create_time' => date('Y-m-d H:i:s'),
                    'related_sources' => [],
                ]);
            Db::table($db_name.'.project_opening_module_relation') -> where('id',$id) ->update([
                'path' => rtrim($path,',').','.$id.',',
                'related_sources' => [["id" => $user_model_id,'end_time' => $end_time]],

            ]);
        }
        return true;
    }





    /**
     * 获取 项目下 用户功能列表
     * pro_id 项目主键id
    */
    public function project_menu_list()
    {
        $param = Request::param();
        if(!isset($param['pro_id']) || empty($param['pro_id']))
        {
            return return_json('200201','请选择要查看的项目',[]);
        }
        // 获取项目数据库名称
        $db_name = Db::table('project_config')
            ->where('app_id',$param['pro_id'])
            ->where('status','1')
            ->where('state','1')
            ->field('id,db_name')
            ->find();
        if(empty($db_name['db_name']))
        {
            return return_json('200201','请先设置项目数据库',[]);
        }
        $list = Db::table($db_name['db_name'].'.project_opening_module_relation')
            ->where('state','1')
            ->field('module_type_alias,id,pid,user_type,module_code')
            ->select();
        $code = array_column($list,'module_code');
        $model = db('development_module')->whereIn('code',implode(',',$code))->field('name,code')->select();
        $model = $this ->key_change($model,'code');
        $user_model = [];
        $admin_model = [];
        foreach ($list as $k => $v )
        {
            if(isset($model[$v['module_code']]))
            {
                $v['platform_name'] = $model[$v['module_code']]['name'];
            }else{
                $v['platform_name'] = '';
            }
            if($v['user_type'] == 1)
            {
                array_push($admin_model,$v);
            }else{
                array_push($user_model,$v);
            }
        }
        $user_model = $this->make_tree($user_model,'pid');
        $admin_model = $this->make_tree($admin_model,'pid');
        return return_json('200200','操作成功',['user_model' => $user_model,'admin_model' => $admin_model]);
    }

    /**
     * 获取功能详情
     * pro_id 项目主键
     * id id
     */
    public function project_menu_details()
    {
        $param = Request::param();
        if(!isset($param['pro_id']) || empty($param['pro_id']))
        {
            return return_json('200201','请选择要查看的项目',[]);
        }
        if(!isset($param['id']) || empty($param['id']))
        {
            return return_json('200201','请选择要查看的功能',[]);
        }
        // 获取项目数据库名称
        $db_name = Db::table('project_config')
            ->where('app_id',$param['pro_id'])
            ->where('status','1')
            ->where('state','1')
            ->field('id,db_name')
            ->find();
        if(empty($db_name['db_name']))
        {
            return return_json('200201','请先设置项目数据库',[]);
        }
        $mess = Db::table($db_name['db_name'].'.project_opening_module_relation')
            ->where('state','1')
            ->where('user_type','2')
            ->where('id',$param['id'])
            ->field('module_type_alias,id,end_time,user_type,application_platform,module_code,sort')
            ->find();
        if(empty($mess))
        {
            return return_json('200201','不存在的数据',[]);
        }
        $model = db('development_module')->where('code',$mess['module_code'])->find();
        $mess['platform_name'] = $model['name'];
        $mess['platform_list'] = array_column($model['application_platform'],'name');
        return return_json('200200','操作成功',$mess);

    }

    /**
     * 编辑功能信息
     * pro_id 项目主键id
     * id 主键id
     * module_type_alias 功能重命名
     * sort 排序
     * ent_time 功能到期时间
     *
    */
    public function edit_project_menu()
    {
        $param = Request::param();
        if(!isset($param['pro_id']) || empty($param['pro_id']))
        {
            return return_json('200201','请选择要查看的项目',[]);
        }
        if(!isset($param['id']) || empty($param['id']))
        {
            return return_json('200201','请选择要查看的功能',[]);
        }
        if(!isset($param['module_type_alias']) || empty(trim($param['module_type_alias'],' ')))
        {
            return return_json('200201','功能重命名不能为空',[]);
        }
        if(!isset($param['sort']) || !is_numeric($param['sort']))
        {
            return return_json('200201','请填写功能排序',[]);
        }
        if(!isset($param['end_time']) || empty($param['end_time']))
        {
            return return_json('200201','请填写功能结束时间',[]);
        }
        $end_time = $this -> ver_date($param['end_time']);
        if(empty($end_time))
        {
            return return_json('200201','请填写功能结束时间',[]);
        }
        if($end_time <= date('Y-m-d'))
        {
            return return_json('200201','结束时间不能小于或等于当前时间',[]);
        }
        // 获取项目数据库名称
        $db_name = Db::table('project_config')
            ->where('app_id',$param['pro_id'])
            ->where('status','1')
            ->where('state','1')
            ->field('id,db_name')
            ->find();
        if(empty($db_name['db_name']))
        {
            return return_json('200201','请先设置项目数据库',[]);
        }
        $mess = Db::table($db_name['db_name'].'.project_opening_module_relation')
            ->where('state','1')
            ->where('user_type','2')
            ->where('id',$param['id'])
            ->find();
        if(empty($mess))
        {
            return return_json('200201','不存在的数据',[]);
        }
        // 获取当前功能整个path
        $all_model = Db::table($db_name['db_name'].'.project_opening_module_relation')
            ->whereIn('id',explode(',',trim($mess['path'],',')))
            ->where('state','1')
            ->select();
        // 修改时间的功能id
        $edit_module_edit_time = [];
        foreach ($all_model as $k => $v )
        {
            // 根据path 数组判断 是上级还是下级
            // 上级
            // 如果上级时间小于现在修改时间，自动延长
            if($v['end_time'] < $param['end_time'])
            {
                $edit_module_edit_time[] = ['code' => $v['module_code'],'end_time' => $end_time,'lao_end_time' => $v['end_time'],"sys_admin_module" => $v['sys_admin_module']];
                Db::table($db_name['db_name'].'.project_opening_module_relation')
                    ->where('id',$v['id'])
                    ->update(['end_time' => $end_time]);
            }else{
                if($v['id'] == $mess['id'])
                {
                    $edit_module_edit_time[] = ["id" => $v['id'],'code' => $v['module_code'],'end_time' => $end_time,'lao_end_time' => $v['end_time'],"sys_admin_module" => $v['sys_admin_module']];
                }
            }
        }
        // project_admin_module($code,$end_time,$db_name)
        // 获取所有下级
        $sub_level = $this -> down_resource_list($db_name['db_name'],$mess['id']);

        foreach ($sub_level as $k => $v )
        {
            if($v['end_time'] > $end_time)
            {
                $edit_module_edit_time[] = ["id" => $v['id'],'code' => $v['module_code'],'end_time' => $end_time,'lao_end_time' => $v['end_time'],"sys_admin_module" => $v['sys_admin_module']];
                Db::table($db_name['db_name'].'.project_opening_module_relation')
                    ->where('id',$v['id'])
                    ->update(['end_time' => $end_time]);
            }
        }

        $this -> edit_module($db_name['db_name'],$edit_module_edit_time);

        Db::table($db_name['db_name'].'.project_opening_module_relation')
            ->where('id',$param['id'])
            ->update([
                'module_type_alias' => $param['module_type_alias'],
                'sort' => (int)$param['sort'],
                'end_time' => $end_time,
            ]);
        return return_json('200200','操作成功',[]);
    }

    /**
     * 编辑修改后台菜单
    */

    protected function edit_module($db_name,$arr)
    {

        foreach ($arr as $k => $v )
        {
            if($v['sys_admin_module'])
            {
                $list = Db::table($db_name.'.project_opening_module_relation')
                    ->whereIn('user_type','1')
                    ->where('state','1')
                    ->whereIn('module_code',implode(',',$v['sys_admin_module']))
                    ->field('related_sources,end_time')
                    ->select();
                foreach ($list as $kk => $vv )
                {

                    foreach ($vv['related_sources'] as $key => $val)
                    {
                        if($val['id'] == $v['id'])
                        {
                            $vv['related_sources'][$key]['end_time'] = $v['end_time'];
                        }

                    }
                    $end_times = array_column($vv['related_sources'],'end_time');
                    rsort($end_times);
                    Db::table($db_name.'.project_opening_module_relation')->where('id',$vv['id'])->update(["end_time" => $end_times[0],'related_sources' => $vv['related_sources']]);
                }
            }

        }

        return true;

    }


    /**
     * 获取用户所有下级
    */
    protected function down_resource_list($db_name,$pid){
        static $data = array();
        $group_id = Db($db_name.'.project_opening_module_relation')->where('pid',$pid)->select();
        for($i=0;$i<count($group_id);$i++)
        {
            $data[] = $group_id[$i];
            down_resource_list($group_id[$i]['id']);
        }
        return $data;
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
}
