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


class User extends Authuser
{

    protected $param;
    protected $support_end = ['pc','app','wechat_program'];
//方法已经可用
    public function __construct(){
        parent::__construct();
    }
    /**
     * 用户列表
     * page
     * size
     * key
     * status
     *
    */
   public function user_list()
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
           $where[] = ['name|account','like',''.(string)trim($param['key'],' ').''];
       }
       $list = Db::table('project_user')
           ->where('state','1')
           ->page($param['page'],$param['size'])
           ->where($where)
           ->field('name,company_name,department_name,account,status,sex')
           ->select();
       return return_json('200200','操作成功',$list);
   }


   /**
    * 获取用户详情
    * id 用户主键id
   */
   public function user_details()
   {
       $param = Request::param();

       if(!isset($param['id']) || empty($param['id']))
       {
           return return_json('200201','请选择要查看的用户',[]);
       }
       $mess = Db::table('project_user')
           ->where('state','1')
           ->where('id',$param['id'])
           ->field('account,head_img,sex,age,user_title,job_number,record_schooling,professional,if_reading,graduation_date,company_name,company_id,
           department_id,department_name')
           ->find();

       if(empty($mess))
       {
           return return_json('200201','不存在的用户信息',[]);
       }
       // 用户所在课题组查询
       $list = Db::table('subject_team_user_relation')
           ->where('uid',$mess['id'])
           ->where('state','1')
           ->field('subject_team_id')
           ->select();
       if(empty($list))
       {
           $subject_team = [];
       }else{
           $subject_team_id = array_column($list,'subject_team_id');
           $subject_team = Db::table('subject_team_info')->whereIn('id',implode(',',$subject_team_id))
               ->where('state','1')->where('status','2')->field('name,teacher_name,uid')->select();
           foreach ($subject_team as $k => $v )
           {
               if($v['uid'] == $param['id'])
               {
                   $subject_team[$k]['sign'] = 1;
               }else{
                   $subject_team[$k]['sign'] = 2;

               }
           }
       }

       return return_json('200201','操作成功',array(['user_details' => $mess,'subject' => $subject_team]));


   }


   /**
    * 用户状态修改
    * id 用户主键id
    * type 1 通过 2 拒绝 3 撤销 4 禁用 5 启用
   */
   public function edit_user_status()
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
       $mess = Db::table('project_user')
           ->where('id',trim($param['id'],' '))
           ->where('state','1')
           ->find();
       if(empty($mess))
       {
           return return_json('200201','不存在的用户信息',[]);
       }
       // 1正常 2禁用 3待审核  4审核被拒绝"
       switch ($param['type'])
       {
           case 1:
               // 修改为通过
               if($mess['status'] != 3)
               {
                   return return_json('200201','非待审核状态',[]);
               }
               $status = '1';
               break;
               // 修改为拒绝
           case 2:
               if($mess['status'] != 3)
               {
                   return return_json('200201','非待审核状态',[]);
               }
               $status = '4';
               break;
           case 3:
               // 修改为待审核
               if($mess['status'] != 4)
               {
                   return return_json('200201','非审核被拒绝状态',[]);
               }
               $status = '3';
               break;
           case 4:
               // 修改为禁用
               if($mess['status'] != 1)
               {
                   return return_json('200201','用户非正常状态',[]);
               }
               $status = '2';
               break;
           case 5:
               // 修改为正常

               if($mess['status'] != 2)
                   {
                       return return_json('200201','用户非禁用状态',[]);
                   }
                   $status = '1';
                   break;
           default:
               return return_json('200201','参数有误',[]);
       }
       Db::table('project_user')
           ->where('id',trim($param['id'],' '))
           ->where('state','1')
           ->update(['status' => $status]);
       return return_json('200200','操作成功',[]);


   }

   /**
    * 编辑用户疾病信息
    * id 用户主键id
    * sex 性别 1 男 2 女
    * head_img 头像
    * age 年龄
    * record_schooling 学历
    * professional 专业
    * if_reading 是否在读
    * graduation_date 毕业日期
    * user_title 头衔
    * job_number 工号
    * department_id 部门id
    * company_id 企业id
    * password 密码
    * name 用户姓名
    * subject_team_list [] 用户要加入课题 此json
    *
   */
    public function edit_user_details()
    {
        $param = Request::param();
        if(!isset($param['id']) || empty(trim($param['id'],' ')))
        {
            return return_json('200201','请选择要编辑的用户信息',[]);
        }
        if(!isset($param['sex']) || !is_numeric($param['sex']))
        {
            return return_json('200201','请选择用户姓别',[]);
        }
        if(!isset($param['age']) || !is_numeric($param['age']) || $param['age'] <= 0)
        {
            return return_json('200201','请填写用户年龄',[]);
        }
        if(!isset($param['record_schooling']) || empty(trim($param['record_schooling'],' ')))
        {
            return return_json('200201','学历不能为可能空',[]);
        }
        if(!isset($param['professional']) || empty(trim($param['professional'],' ')))
        {
            return return_json('200201','专业不能为空',[]);
        }
        if(!isset($param['if_reading']) || !is_numeric($param['if_reading']) || $param['if_reading'] > 2 || $param['if_reading'] < 1)
        {
            return return_json('200201','请选择是否在读',[]);
        }
        if(!isset($param['graduation_date']) || empty(trim($param['graduation_date'],' ')))
        {
            return return_json('200201','毕业日期不能为空',[]);
        }
        $graduation_date = $this -> ver_date($param['graduation_date']);
        if(empty($graduation_date))
        {
            return return_json('200201','毕业日期有误',[]);
        }
        if(!isset($param['user_title']) || empty(trim($param['user_title'],' ')))
        {
            return return_json('200201','职位不能为空',[]);
        }
        if(!isset($param['job_number']) || empty(trim($param['job_number'],' ')))
        {
            return return_json('200201','工号不能为空',[]);
        }
        if(!isset($param['head_img']) || empty(trim($param['head_img'],' ')))
        {
            return return_json('200201','头像不能为空',[]);
        }
        $mess = Db::table('project_user')->where('id',$param['id'])->where('state','1')->find();
        if(empty($mess))
        {
            return return_json('200201','不存在的用户信息',[]);
        }
        if(!isset($param['name']) || empty(trim($param['name'],' ')))
        {
            return return_json('200201','用户姓名不能为空',[]);
        }
        if(!isset($param['department_id']) || empty(trim($param['department_id'],' ')))
        {
            return return_json('200201','用户所属部门不能为空',[]);
        }
        if(!isset($param['company_id']) || empty(trim($param['company_id'],' ')))
        {
            return return_json('200201','用户所属企业不能为空',[]);
        }



        $data = [
            'sex' =>(string)(int)$param['sex'],
            'head_img' => $param['head_img'],
            'age' => (string)(int)$param['age'],
            'record_schooling' => $param['record_schooling'],
            'professional' => $param['professional'],
            'if_reading' => (string)$param['if_reading'],
            'graduation_date' => $graduation_date,
            'user_title' => $param['user_title'],
            'job_number' => (string)$param['job_number'],
            'name' => $param['name'],
        ];
        if(isset($param['password']) && !empty(trim($param['password'])))
        {
            $data['password'] = $param['password'];
            $data['password_md5'] = md5(trim($param['password']));
        }
        if($param['department_id'] != $mess['department_id'])
        {
            $department_mess = Db::table('department')->where('state','1')->where('id',$param['department_id'])->find();
            if(empty($department_mess))
            {
                return return_json('200201','不存在的部门信息',[]);
            }
            $company_mess = Db::table('company')->where('state','1')->where('status','2')
                ->where('id',$department_mess['company_id'])->find();
            if(empty($company_mess))
            {
                return return_json('200201','企业信息不存在',[]);
            }

            $data['company_id'] = $company_mess['id'];
            $data['company_name'] = $company_mess['name'];
            $data['department_id'] = $department_mess['id'];
            $data['department_name'] = $department_mess['name'];
            if($company_mess['id'] != $mess['company_id'])
            {
                $company_mess = Db::table('company_user_relation')->where('user_id',$mess['id'])
                    ->where('state','1')
                    ->where('status','2')
                    ->where('company_id',$mess['company_id'])
                    ->find();
                if($company_mess['identity'] == 1)
                {
                    return return_json('200201','此用户是企业管理员，不可修改企业',[]);
                }

            }
        }
        $data['account'] = $mess['account'];
        Db::table('project_user')->where('id',$param['id'])->update($data);

        // 修改用户姓名
        if($mess['name'] != $param['name'])
        {
            $this -> edit_user_name($param['id'],$param['name'],$param['job_number'],$param['user_title']);
        }else{
            if($param['job_number'] != $mess['job_number'] || $mess['user_title'] != $param['user_title'])
            {
                Db::table('project_user_relation')
                    ->where('member_id',$param['id'])->update([
                        'job_number' => $param['job_number']]);
                Db::table('company_user_relation')
                    ->where('user_id',$param['id'])
                    ->where('state','1')
                    ->update(['job_number' => $param['job_number'],'user_title' => $param['user_title']]);

            }
        }
        if($param['department_id'] != $mess['department_id'])
        {
            $this -> edit_user_department($param['id'],$data['department_id'],$data['department_name'],$data['company_id'],$data['company_name'],$mess['company_id'],$data['department_id'],$data);
        }


        return return_json('200201','操作成功',[]);


    }

    /**
     * 用户姓名修改
    */
    protected function edit_user_name($id,$name,$job_number,$user_title)
    {
        // 课题
        Db::table('subject_team_info')->where('uid',$id)->update(['teacher_name' => $name]);
        // 用户项目关系
        Db::table('project_user_relation')->where('member_id',$id)->update(['name' => $name,'job_number' => $job_number]);
        Db::table('company_user_relation')->where('user_id',$id)->update(['user_name' => $name,'job_number' => $job_number,'user_title' => $user_title]);

    }


    /**
     * 用户部门修改
    */
    protected function edit_user_department($id,$department_id,$department_name,$company_id,$company_name,$yuan_company_id,$yuan_department_id,$data)
    {
        if($yuan_company_id)
        {
            // 修改用户企业关系
            Db::table('company_user_relation')->where('user_id',$id)
                ->where('company_id',$yuan_company_id)
                ->update([
                    'company_id' => $company_id,
                ]);
            Db::table('department_user_relation')->where('user_id',$id)
                ->where('department_id',$yuan_department_id)
                ->update(['department_id' => $department_id,'company_id' => $company_id]);

        }else{
            Db::table('company_user_relation')->insert([
               'user_id' => $id,
               'user_name' => $data['name'],
               'user_mobile' => $data['account'] ,
                'user_title' => $data['user_title'],
                'job_number' => $data['job_number'],
                'company_id' => $data['company_id'],
                'sex' => $data['sex'],
                'identity' => '2',
                'state' => '1',
                'status' => '2',
                'sort' => 1,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
            Db::table('department_user_relation')->insert([
                'user_id' => $id,
                'department_id' => $data['department_id'],
                'company_id' => $data['company_id'],
                'state' => '1',
                'status' => '2',
                'sort' => 1,
                'create_time' => date('Y-m-d H:i:s')
            ]);
        }

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
