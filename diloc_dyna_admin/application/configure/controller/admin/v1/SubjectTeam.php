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


class SubjectTeam extends Authuser
{

    protected $param;
    protected $support_end = ['pc','app','wechat_program'];
//方法已经可用
    public function __construct(){
        parent::__construct();
    }

    /**
     * 获取课题列表
     * key 搜索条件
     * status 1待审核 2正常 3禁用(不可参与结账) 4解散(课题组管理员要是此课题组) 5审核不通过
    */
    public function subject_team_list()
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
            $where[] = ['name','like',''.(string)trim($param['key'],' ').''];
        }
        if(isset($param['status']) && !empty(trim($param['status'],' ')))
        {
            $status = json_decode(html_entity_decode($param['status']),true);
            if(is_array($status) || count($status) >= 1)
            {
                $where[] = ['status','in',implode(',',$status)];
            }
        }
        $list = Db::table('subject_team_info')
            ->where('state','1')
            ->page($param['page'],$param['size'])

            ->field('name,teacher_name,teacher_mobile,create_time,status')
            ->select();
        return return_json('200201','操作成功',$list);
    }


    /**
     * 课题详情
     * id 课题主键id
    */
    public function subject_team_details()
    {
        $param = Request::param();
        if(!isset($param['id']) || empty(trim($param['id'],' ')))
        {
            return return_json('200201','请选择要查看的课题',[]);
        }
        $mess = Db::table('subject_team_info')
            ->where('state','1')
            ->where('id',$param['id'])
            ->field('name,teacher_name,teacher_mobile,create_time,status')
            ->find();
        if(empty($mess))
        {
            return return_json('200201','不存在的课题信息',[]);
        }
        return return_json('200200','操作成功',$mess);

    }


    /**
     * 获取课题组下人员
     * id 课题组主键id
     * type 1 导师 2 成员
     *
    */
    public function subject_team_user()
    {
        $param = Request::param();
        if(!isset($param['id']) || empty($param['id']))
        {
            return return_json('200201','请选择要查看的课题信息',[]);
        }

        if(!isset($param['start_time']) || empty($param['start_time']))
        {
            $param['start_time'] = date('Y-m-d');
        }
        if(!isset($param['end_time']) || empty($param['end_time']))
        {
            $param['end_time'] = date('Y-m-d').' 23:59:59';
        }
        $where = [['create_time' ,">",(string)$param['start_time']],['create_time',"<",(string)$param['end_time']]];

        if(isset($param['type']) && !empty(trim($param['type'],' ')))
        {
            $status = json_decode(html_entity_decode($param['type']),true);
            if(is_array($status) || count($status) >= 1)
            {
                $where[] = ['type','in',implode(',',$status)];
            }
        }
        $subject_team = Db::table('subject_team_user_relation')
            ->where('subject_team_id',$param['id'])
            ->where('state','1')
            ->field('uid,type,create_time')
            ->where($where)
            ->select();
        $user_id = array_column($subject_team,'uid');
        $list = Db::table('project_user')
            ->whereIn('id',implode(',',$user_id))
            ->field('id,name,sex,company_name,department_name,user_title,job_number')
            ->select();
        $subject_team = $this -> key_change($subject_team,'uid');
        foreach ($list as $k => $v )
        {
            $list[$k]['create_time'] = $subject_team[$v['id']]['create_time'];
            $list[$k]['type'] = $subject_team[$v['id']]['type'];
        }
        return return_json('200200','操作成功',$list);

    }




    /**
     * 课题组状态修改
     * id 企业主键id
     * type 1 通过 2 拒绝 3 撤销 4 禁用 5 启用
     */
    public function edit_subject_team_status()
    {
        $param = Request::param();
        if(!isset($param['id']) || empty(trim($param['id'],' ')))
        {
            return return_json('200201','请选择要操作的用户',[]);
        }
        if(!isset($param['type']) || !is_numeric($param['type']))
        {
            return return_json('200201','参数有误',[]);
        }
        $mess = Db::table('subject_team_info')
            ->where('id',trim($param['id'],' '))
            ->where('state','1')
            ->find();
        if(empty($mess))
        {
            return return_json('200201','不存在的用户信息',[]);
        }
        // 1待审核 2正常 3禁用(不可参与结账) 4解散(课题组管理员要是此课题组) 5审核不通过
        switch ($param['type'])
        {
            case 1:
                // 修改为通过
                if($mess['status'] != 1)
                {
                    return return_json('200201','非待审核状态',[]);
                }
                $status = '2';
                break;
            // 修改为拒绝
            case 2:
                if($mess['status'] != 1)
                {
                    return return_json('200201','非待审核状态',[]);
                }
                $status = '5';
                break;
            case 3:
                // 修改为待审核
                if($mess['status'] != 5)
                {
                    return return_json('200201','非审核被拒绝状态',[]);
                }
                $status = '1';
                break;
            case 4:
                // 修改为禁用
                if($mess['status'] != 2)
                {
                    return return_json('200201','用户非正常状态',[]);
                }
                $status = '3';
                break;
            case 5:
                // 修改为正常

                if($mess['status'] != 3)
                {
                    return return_json('200201','用户非禁用状态',[]);
                }
                $status = '2';
                break;
            default:
                return return_json('200201','参数有误',[]);
        }
        Db::table('subject_team_info')
            ->where('id',trim($param['id'],' '))
            ->where('state','1')
            ->update(['status' => $status]);
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
