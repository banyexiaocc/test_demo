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


class Company extends Authuser
{

    protected $param;
    protected $support_end = ['pc','app','wechat_program'];
//方法已经可用
    public function __construct(){
        parent::__construct();
    }
    /**
     * 企业管理 企业列表
     * page
     * size
     * key
     * status 1待审核   2正常   3审核不通过 4禁用
     *
     */
    public function company_list()
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
        if(isset($param['status']) && !empty(trim($param['status'],' ')))
        {
            $status = json_decode(html_entity_decode($param['status']),true);
            if(is_array($status) || count($status) >= 1)
            {
                $where[] = ['status','in',implode(',',$status)];
            }
        }
        if(isset($param['key']) && !empty($param['key']))
        {
            $where[] = ['name','like',''.(string)trim($param['key'],' ').''];
        }
        $list = Db::table('company')
            ->where('state','1')
            ->page($param['page'],$param['size'])
            ->where($where)
            ->field('name,creater_id,create_time,status,if_new_info')
            ->select();
        if($list)
        {
            $user_ids = array_column($list,'creater_id');
            $user_list = Db::table('project_user')->whereIn('id',implode(',',$user_ids))->field('name')->select();
            $user_list = $this -> key_change($user_list);
            foreach ($list as $k => $v )
            {
                $list[$k]['user_name'] = $user_list[$v['creater_id']]['name'];
            }
        }
        return return_json('200200','操作成功',$list);
    }

    /**
     * 企业详情
     * id 企业主键id
     * type 1 查看详情  2 查看原企业信息
    */
    public function company_details()
    {
        $param = Request::param();
        if(!isset($param['id']) || empty($param['id']))
        {
            return return_json('200201','请选择要查看的企业',[]);
        }
        if(!isset($param['type']) || !is_numeric($param['type']))
        {
            $param['type'] = 1;
        }
        $mess = Db::table('company')
            ->where('id',$param['id'])
            ->where('state','1')
            ->field('name,status,if_new_info,social_credit_code,creater_id,registered_address,set_up_time,company_phone,company_fax,company_web,legal_person,legal_person_phone,legal_person_email,business_license_url,create_time')
            ->find();
        if(empty($mess))
        {
            return return_json('200201','请选择要查看的企业',[]);
        }
        $creater_id = $mess['creater_id'];
        if($mess['if_new_info'] == 1 && $param['type'] == 1)
        {
            $mess = Db::table('new_info_company')
                ->where('company_id',$mess['id'])
                ->where('state','1')
                ->find();
        }

        $user_mess = Db::table('project_user')->where('id',$creater_id)->find();
        $mess['user_name'] = $user_mess['name'];
        $mess['mobile'] = $user_mess['mobile'];

        return return_json('200200','操作成功',$mess);


    }

    /**
     * 企业下部门
     * id 企业主键id
     * page 页码
     * size 条数
     * key 搜索条件
    */
    public function company_department_list()
    {
        $param = Request::param();
        if(!isset($param['id']) || empty(trim($param['id'],' ')))
        {
            return return_json('200201','请选择要查看的部门',[]);
        }
        $where = [];
        if(isset($param['key']) && !empty($param['key']))
        {
            $where[] = ['name','like',''.(string)trim($param['key'],' ').''];
        }
        if(!isset($param['page']) || !is_numeric($param['page']) || $param['page'] < 1)
        {
            $param['page'] = 1;
        }
        if(!isset($param['size']) || !is_numeric($param['size']) || $param['size'] < 1)
        {
            $param['size'] = 50;
        }
        $list = Db::table('department')
            ->where('company_id',$param['id'])
            ->where($where)
            ->page($param['page'],$param['size'])
            ->where('state','1')
            ->field('name,create_time')
            ->select();
        return return_json('200200','操作成功',$list);
    }



    /**
     * 企业状态修改
     * id 企业主键id
     * type 1 通过 2 拒绝 3 撤销 4 禁用 5 启用
     */
    public function edit_company_status()
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
        $mess = Db::table('company')
            ->where('id',trim($param['id'],' '))
            ->where('state','1')
            ->find();
        if(empty($mess))
        {
            return return_json('200201','不存在的用户信息',[]);
        }
        // 1待审核   2正常   3审核不通过 4禁用"
        switch ($param['type'])
        {
            case 1:
                // 修改为通过
                if($mess['status'] != 1)
                {
                    return return_json('200201','非待审核状态',[]);
                }
                if($mess['if_new_info'] == 1)
                {
                    Db::table('company')
                        ->where('id',$mess['id'])
                        ->where('state','1')
                        ->update(['status' => '2']);
                }else{
                    $new_info_company = Db::table('new_info_company')
                        ->where('company_id',$mess['id'])
                        ->where('state','1')
                        ->find();
                    $data = [
                        'name' => $new_info_company['name'],
                        'social_credit_code' => $new_info_company['social_credit_code'],
                        'registered_address' => $new_info_company['registered_address'],
                        'set_up_time' => $new_info_company['set_up_time'],
                        'company_phone' => $new_info_company['company_phone'],
                        'company_fax' => $new_info_company['company_fax'],
                        'company_email' => $new_info_company['company_email'],
                        'company_web' => $new_info_company['company_web'],
                        'legal_person' => $new_info_company['legal_person'],
                        'legal_person_phone' => $new_info_company['legal_person_phone'],
                        'legal_person_email' => $new_info_company['legal_person_email'],
                        'business_license_url' => $new_info_company['business_license_url'],
                        'status' => '2',
                        'if_new_info' => '2',
                    ];

                    Db::table('company')
                        ->where('id',$mess['id'])
                        ->where('state','1')
                        ->update($data);
                    // 修改用户 职位 和 工号
                    Db::table('company_user_relation')
                        ->where('state','1')
                        ->where('status','2')
                        ->where('company_id',$mess['id'])
                        ->where('user_id',$new_info_company['user_id'])
                        ->update(['user_title' => $new_info_company['user_title'],'job_number' => $new_info_company['job_number']]);
                    Db::table('project_user')->where('id',$new_info_company['user_id'])->update(['user_title' => $new_info_company['user_title'],'job_number' => $new_info_company['job_number'],'company_name' => $new_info_company['name']]);
                    Db::table('project_user_relation')->where('member_id',$new_info_company['user_id'])->where('state','1')->update(['user_title' => $new_info_company['user_title'],'job_number' => $new_info_company['job_number']]);




                }

                break;
            // 修改为拒绝
            case 2:
                if($mess['status'] != 1)
                {
                    return return_json('200201','非待审核状态',[]);
                }
                if($mess['if_new_info'] == 1)
                {
                    Db::table('company')
                        ->where('id',$mess['id'])
                        ->where('state','1')
                        ->update(['status' => '2']);
                     Db::table('new_info_company')
                        ->where('company_id',$mess['id'])
                        ->where('state','1')
                        ->update(['state' => '2']);

                }else{
                    Db::table('company')
                        ->where('id',$mess['id'])
                        ->where('state','1')
                        ->update(['status' => '3']);
                }
                break;
            case 3:
                // 修改为待审核
                if($mess['status'] != 3)
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
                $status = '4';
                break;
            case 5:
                // 修改为正常

                if($mess['status'] != 4)
                {
                    return return_json('200201','用户非禁用状态',[]);
                }
                $status = '2';
                break;
            default:
                return return_json('200201','参数有误',[]);
        }

        return return_json('200200','操作成功',[]);


    }




    /**
     * 获取企业下成员
     * id 企业主键id
     * identity [] 1 管理员 2 普通用户
     * key 搜索条件
     * start_time
     * end_time 搜索时间
    */
    public function company_user_list()
    {
        $param = Request::param();
        if(!isset($param['id']) || empty(trim($param['id'],' ')))
        {
            return return_json('200201','请选择要查看的企业',[]);
        }
        if(!isset($param['page']) || !is_numeric($param['page']) || $param['page'] < 1)
        {
            $param['page'] = 1;
        }
        if(!isset($param['size']) || !is_numeric($param['size']) || $param['size'] < 1)
        {
            $param['size'] = 50;
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

        if(isset($param['key']) && !empty($param['key']))
        {
            $where[] = ['user_name|user_title|job_number','like',''.(string)trim($param['key'],' ').''];
        }
        if(isset($param['identity']) && !empty(trim($param['identity'],' ')))
        {
            $identity = json_decode(html_entity_decode($param['identity']),true);
            if(is_array($identity) || count($identity) >= 1)
            {
                $where[] = ['identity','in',implode(',',$identity)];
            }
        }
        $list = Db::table('company_user_relation')
            ->where('company_id',$param['id'])
            ->where('state','1')
            ->where('status','2')
            ->where($where)
            ->page($param['page'],$param['size'])
            ->field('id,user_name,user_title,job_number,create_time,identity,user_id')
            ->select();
        $user_id = array_column($list,'user_id');
        $department = Db::table('project_user')->whereIn('id',implode(',',$user_id))->field('id,department_name')->select();
        $department = $this->key_change($department,'id');
        foreach ($list as $k => $v )
        {
            $list[$k]['department_name'] = $department[$v['user_id']]['department_name'];
        }

        return return_json('200200','操作成功',$list);
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
