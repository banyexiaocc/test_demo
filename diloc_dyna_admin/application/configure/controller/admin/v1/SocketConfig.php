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


class SocketConfig extends Authuser
{

    protected $param;
    protected $support_end = ['pc','app','wechat_program'];
//方法已经可用
    public function __construct(){
        parent::__construct();
    }


    /**
     * 获取可选项目信息
     * key 搜索条件
    */
    public function project_list()
    {
        $param = Request::param();
        $list = Db::table('project')
            ->where('state','1')
            ->whereIn('status','1,2')
            ->field('name')
            ->select();
        return return_json('200200','操作成功',$list);
    }


    /**
     * app_id 项目id
     * sort 排序
     * account 账号
     * password 密码
     * desc 描述
     * type 1 参数相关 2 消息相关
    */
    public function add_socket_config()
    {
        $param = Request::param();
        if(!isset($param['app_id']) || empty(trim($param['app_id'],' ')))
        {
            return return_json('200201','请选择所属项目',[]);
        }
        if(!isset($param['account']) || empty(trim($param['account'],' ')))
        {
            return return_json('200201','账号名称不能为空',[]);
        }
        if(!isset($param['password']) || empty($param['password']))
        {
            return return_json('200201','密码不能为空',[]);
        }
        if(!isset($param['desc']) || empty(trim($param['desc'],' ')))
        {
            return return_json('200201','描述不能为空',[]);
        }
        if(!isset($param['type']) || !is_numeric($param['type']) || $param['type'] < 1 || $param['type'] > 2)
        {
            return return_json('200201','类型不能为空',[]);
        }
        $mess = Db::table('socket_account')->where('status','1')
            ->where('account',(string)trim($param['account'],' '))
            ->find();
        if($mess)
        {
            return return_json('200201','已存在的账号名称',[]);
        }
        if(!isset($param['sort']) || !is_numeric($param['sort']))
        {
            $param['sort'] = 1;
        }
        $project_mess = Db::table('project')
            ->where('id',$param['app_id'])->whereIn('status','1,2')->where('state','1')->find();
        if(empty($project_mess))
        {
            return return_json('200201','不存在的项目信息',[]);
        }
        // 获取项目数据库名称
        $db_name = Db::table('project_config')
            ->where('app_id',$param['app_id'])
            ->where('status','1')
            ->where('state','1')
            ->field('id,db_name')
            ->find();
        if(empty($db_name['db_name']))
        {
            return return_json('200201','请先设置项目数据库',[]);
        }

        $data = [
            'sort' => (int)$param['sort'],
            'account' => (string)trim($param['account'],' '),
            'password' => md5($param['password']),
            'pass' => $param['password'],
            'desc' => $param['desc'],
            'app_id' => $param['app_id'],
            'db_name' => $db_name['db_name'],
            'type' => (string)(int)$param['type'],
            'status' => '1'
        ];
        $id = Db::table('socket_account')->insertGetId($data);
        if(empty($id))
        {
            return return_json('200204','操作失败',[]);
        }

        return return_json('200200','操作成功',['id' => $id]);

    }

    /**
     * socket 连接信息列表
     * page
     * size
     * key
    */
    public function socket_list()
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
        if(isset($param['key']) && !empty($param['key']))
        {
            $where[] = ['account','like',''.(string)trim($param['key'],' ').''];
        }
        $list = Db::table('socket_account')
            ->where('status','1')
            ->where($where)
            ->page($param['page'],$param['size'])
            ->field('account,desc,type')
            ->order('sort','asc')
            ->select();
        return return_json('200200','操作成功',$list);
    }

    /**
     * 删除
     * id 主键id
    */
    public function del_socket()
    {
        $param = Request::param();
        if(!isset($param['id']) || empty(trim($param['id'],' ')))
        {
            return return_json('200201','请选择要删除的信息',[]);
        }
        $mess = Db::table('socket_account')
            ->where('status','1')
            ->where('id',$param['id'])
            ->find();
        if(empty($mess))
        {
            return return_json('200201','要操作的数据不存在',[]);
        }
        Db::table('socket_account')
            ->where('status','1')
            ->where('id',$param['id'])
            ->update(['status' => '2']);
        return return_json('200200','操作成功',[]);

    }



    /**
     * id 主键id
     * app_id 项目id
     * sort 排序
     * account 账号
     * password 密码
     * desc 描述
     * type 1 参数相关 2 消息相关
     */
    public function edit_socket_config()
    {
        $param = Request::param();
        if(!isset($param['app_id']) || empty(trim($param['app_id'],' ')))
        {
            return return_json('200201','请选择所属项目',[]);
        }
        if(!isset($param['account']) || empty(trim($param['account'],' ')))
        {
            return return_json('200201','账号名称不能为空',[]);
        }
        if(!isset($param['password']) || empty($param['password']))
        {
            return return_json('200201','密码不能为空',[]);
        }
        if(!isset($param['desc']) || empty(trim($param['desc'],' ')))
        {
            return return_json('200201','描述不能为空',[]);
        }
        if(!isset($param['type']) || !is_numeric($param['type']) || $param['type'] < 1 || $param['type'] > 2)
        {
            return return_json('200201','类型不能为空',[]);
        }
        if(!isset($param['id']) || empty(trim($param['id'],' ')))
        {
            return return_json('200201','请选择要修改的信息',[]);
        }
        $mess = Db::table('socket_account')
            ->where('status','1')
            ->where('id',$param['id'])
            ->find();
        if(empty($mess))
        {
            return return_json('200201','要操作的数据不存在',[]);
        }
        $count = Db::table('socket_account')->where('status','1')
            ->where('account',(string)trim($param['account'],' '))
            ->where('id','<>',$mess['id'])
            ->find();
        if($count)
        {
            return return_json('200201','已存在的账号名称',[]);
        }
        if(!isset($param['sort']) || !is_numeric($param['sort']))
        {
            $param['sort'] = 1;
        }
        $project_mess = Db::table('project')
            ->where('id',$param['app_id'])->whereIn('status','1,2')->where('state','1')->find();
        if(empty($project_mess))
        {
            return return_json('200201','不存在的项目信息',[]);
        }
        // 获取项目数据库名称
        $db_name = Db::table('project_config')
            ->where('app_id',$param['app_id'])
            ->where('status','1')
            ->where('state','1')
            ->field('id,db_name')
            ->find();
        if(empty($db_name['db_name']))
        {
            return return_json('200201','请先设置项目数据库',[]);
        }

        $data = [
            'sort' => (int)$param['sort'],
            'account' => (string)trim($param['account'],' '),
            'password' => md5($param['password']),
            'pass' => $param['password'],
            'desc' => $param['desc'],
            'app_id' => $param['app_id'],
            'db_name' => $db_name['db_name'],
            'type' => (string)(int)$param['type'],
            'status' => '1'
        ];
        Db::table('socket_account')->where('id',$mess['id'])->update($data);


        return return_json('200200','操作成功',[]);

    }
}
