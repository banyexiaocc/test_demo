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


class Message extends Authuser
{

    public function __construct(){
        parent::__construct();

    }

    /**
     * 新增消息类型
     * type 类型名称
     * pid 上级id
     *
    */

    public function add_message()
    {
        $param = Request::param();
        if(!isset($param['type']) || empty(trim($param['type'],' ')))
        {
            return return_json('200201','消息类型名称不能为空',[]);
        }
        if(!isset($param['pid']) || empty($param['pid']))
        {
            $param['pid'] = '0';
            $path = '';
            $level = "1";
        }else{
            $message_type = Db::table('notification_message_type')
                ->where('id',$param['pid'])
                ->where('state','1')->find();
            if(empty($message_type))
            {
                return return_json('200201','不存在的上级模块',[]);
            }
            $path = $message_type['path'];
            $level = $message_type['level'] + 1;
        }

        $data = [
            'type' => $param['type'],
            'code' => create_uuid(time()),
            'pid' => $param['pid'],
            'path' => $path,
            'level' => (string)$level,
            'state' => '1',
            'status' => '1',
            'sort' => 1,
            'create_time' => date('Y-m-d H:i:s'),
        ];
        $id = Db::table('notification_message_type') -> insertGetId($data);
        if(empty($id))
        {
            return return_json('200204','操作失败',[]);
        }
        Db::table('notification_message_type') -> where('id',$id)->update(['path' => rtrim($path,',').','.$id.',']);
        return return_json('200200','操作成功',['id' => $id]);
    }

    /**
     * 获取消息通知类型列表
     * pid 一级为 0
     * key 搜索条件
     * page 页码
     * size 每页条数
    */
    public function message_list()
    {
        $param = Request::param();

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
        $where = [];
        if(isset($param['key']) && !empty($param['key']))
        {
            $where[] = ['type','like',''.(string)trim($param['key'],' ').''];
        }
        $list = Db::table('notification_message_type')
            ->where('state','1')
            ->where('pid',$param['pid'])
            ->where($where)
            ->page($param['page'],$param['size'])
            ->field('id,type,code')
            ->select();
        return return_json('200200','操作成功',$list);
    }
    /**
     * 编辑消息类型
     * id 要编辑的消息类型
     * type 类型名称
    */
    public function edit_message()
    {
        $param = Request::param();
        if(!isset($param['id']) || empty($param['id']))
        {
            return return_json('200201','请选择要编辑的消息类型',[]);
        }
        if(!isset($param['type']) || empty(trim($param['type'],' ')))
        {
            return return_json('200201','要编辑的类型名称',[]);
        }
        $mess = Db::table('notification_message_type')
            ->where('id',$param['id'])
            ->where('state','1')
            ->find();
        if(empty($mess))
        {
            return return_json('200201','要编辑的消息类型不存在',[]);
        }
        if($param['type'] == $mess['type'])
        {
            return return_json('200200','操作成功',[]);
        }
        Db::table('notification_message_type')
            ->where('id',$param['id'])
            ->update(['type' => $param['type']]);
        return return_json('200200','操作成功',[]);


    }



    /**
     * 模版添加
     * id 所属类型主键id
     * template_name 模版名称
     * remark 模版描述
     * content 模版内置内容
    */
    public function message_module_add()
    {

        $param = Request::param();
        if(!isset($param['template_name']) || empty(trim($param['template_name'],' ')))
        {
            return return_json('200201','消息模版名称不能为空',[]);
        }
        if(!isset($param['id']) || empty(trim($param['id'],' ')))
        {
            return return_json('200201','请选择模版所属类型',[]);
        }
        if(!isset($param['remark']) || empty(trim($param['remark'],' ')))
        {
            $param['remark'] = '';
        }
        if(!isset($param['content']) || empty(trim($param['content'],' ')))
        {
            return return_json('200201','模版内置内容不能为空',[]);
        }

        $message_type = Db::table('notification_message_type')
            ->where('id',$param['id'])
            ->where('state','1')
            ->where('status','1')
            ->find();
        if(empty($message_type))
        {
            return return_json('200201','不存在的类型',[]);
        }


        $data = [
            'message_type_id' => $message_type['id'],
            'template_name' => $param['template_name'],
            'remark' => $param['remark'],
            'code' => create_uuid(time()),
            'content' => $param['content'],
            'state' => '1',
            'status' => '1',
            'sort' => 1,
            'create_time' => date('Y-m-d H:i:s'),
        ];
        $id = Db::table('notification_message_info') -> insertGetId($data);
        if(empty($id))
        {
            return return_json('200204','操作失败',[]);
        }
        return return_json('200200','操作成功',['id' => $id]);
    }


    /**
     * 获取模版列表
     * id 主键id
     * key 搜索条件
     * page 页码
     * size 每页条数
    */
    public function message_module_list()
    {
        $param = Request::param();

        if(!isset($param['id']) || empty($param['id']))
        {
            return return_json('200201','请选择要查看的类型',[]);
        }
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
            $where[] = ['template_name','like',''.(string)trim($param['key'],' ').''];
        }
        $list = Db::table('notification_message_info')
            ->where('state','1')
            ->where('message_type_id',$param['id'])
            ->where($where)
            ->page($param['page'],$param['size'])
            ->field('id,template_name,code')
            ->select();
        return return_json('200200','操作成功',$list);

    }

    /**
     * 获取模版详情
     * id 模版主键id
    */
    public function message_module_details()
    {


        $param = Request::param();
        if(!isset($param['id']) || empty($param['id']))
        {
            return return_json('200201','请选择要查看的模版',[]);
        }
        $mess = Db::table('notification_message_info')
            ->where('state','1')
            ->where('id',$param['id'])
            ->field('id,code,template_name,remark,content')
            ->find();
        if(empty($mess))
        {
            return return_json('200201','不存在的模版消息',[]);
        }

        return return_json('200200','操作成功',$mess);


    }

    /**
     * 编辑模版信息
     * id 模版主键id
     * template_name 模版名称
     * remark 模版描述
     * content 通知内容
    */
    public function edit_message_module()
    {
        $param = Request::param();
        if(!isset($param['id']) || empty($param['id']))
        {
            return return_json('200201','请选择要编辑的模版',[]);
        }
        if(!isset($param['template_name']) || empty($param['template_name']))
        {
            return return_json('200201','模版名称不能为空',[]);
        }

        if(!isset($param['remark']) || empty(trim($param['remark'],' ')))
        {
            $param['remark'] = '';
        }
        if(!isset($param['content']) || empty(trim($param['content'],' ')))
        {
            return return_json('200201','模版内置内容不能为空',[]);
        }
        $mess = Db::table('notification_message_info') -> where('id',$param['id'])->where('status','1')->where('state','1')->find();
        if(empty($mess))
        {
            return return_json('200201','要编辑的模版不存在',[]);
        }
        $data = [
            'template_name' => $param['template_name'],
            'remark' => $param['remark'],
            'content' => $param['content'],
        ];
        $id = Db::table('notification_message_info') -> where('id',$param['id']) -> update($data);
        if(empty($id))
        {
            return return_json('200204','操作失败',[]);
        }
        return return_json('200200','操作成功',[]);

    }

}
