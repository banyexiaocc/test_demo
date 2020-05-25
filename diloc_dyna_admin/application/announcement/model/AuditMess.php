<?php
/**
 * Created by PhpStorm.
 * User: DELL
 * Date: 2019/5/10
 * Time: 10:34
 */

namespace app\announcement\model;
use think\Model;
use think\Db;


/**
 * 日志记录
 */
class AuditMess extends Model
{
    protected $table = 'audit_message';# 表名
    static $table_name = 'audit_message';# 表名
    protected $autoWriteTimestamp = false;# 取消自动设置时间戳

    static public function table_name($unique_code)
    {
        switch ($unique_code)
        {
            // 用户入驻
            case "D9823B82-EDB1-B0C5-A221-F2D5E1800445-0C693EDA32":
                $table_name = 'project_user_relation';
                break;

        }
        return $table_name;

    }
    /**
     * 根据审核类型 返回 审核信息
     *
    */
     static public function audit_tier_list($db_name,$audit_type_id)
    {
        $tire_list = Db::table($db_name.'.audit_tier')
            ->where('audit_type_id',$audit_type_id)
            ->where('state','1')
            ->where('status','1')
            ->order('audit_sort','asc')
            ->field('name,type,audit_type_code')
            ->select();
        if(empty($tire_list))
        {
            return [];
        }
        $tier_id = array_column($tire_list,'id');
        $user_list = Db::table($db_name.'.audit_user')
            ->where('state','1')
            ->where('status','1')
            ->whereIn('audit_tier_id',implode(',',$tier_id))
            ->field('audit_tier_id,audit_user_type,audit_way,audit_user_id')
            ->select();
        $data = [];
        foreach ($tire_list as $k => $v )
        {
            $v['send'] = 1;
            $data[$v['id']] = $v;
        }
         foreach ($user_list as $k => $v )
         {
             $data[$v['audit_tier_id']]['audit_list'][] = $v;
         }
        return array_values($data);


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
     * 审核发起表
    */
    static public function audit_list_sort($db_name,$app_id,$unique_code,$resource_id,$param)
    {
        $audit_type = Db::table($db_name.'.audit_type')
            ->where('status',"1")
            ->where('state',"1")
            ->where('code',$unique_code)
            ->find();
        if(empty($audit_type))
        {
            // 通过
            return self::audit_adopt($db_name,$app_id,$unique_code,$resource_id,$param);
        }
        //查询审核流程
        $audit_list = self::audit_tier_list($db_name,$audit_type['id']);
        if (empty($audit_list))
        {
            // 通过
            return self::audit_adopt($db_name,$app_id,$unique_code,$resource_id,$param);
        }

        switch ($unique_code)
        {
            // 用户入驻
            case 'D9823B82-EDB1-B0C5-A221-F2D5E1800445-0C693EDA32':
                // mess 被审核数据
                $mess = Db::table(self::table_name($unique_code))->where('id',$resource_id)->find();
                $list = Db::table(self::table_name($unique_code))->where('id',$resource_id)->update(['audit_list' => $audit_list]);
                if(empty($list))
                {
                    return ['code' => 200204,'msg' => '操作失败',[]];
                }
                $audit_tier = [];
                foreach ($audit_list as $k => $v )
                {
                    $audit_list[$k]['send'] = 2;

                    $usr_list = self::audit_user_list($v,self::table_name($unique_code));
                    if($usr_list['audit'])
                    {
                        $audit_tier = $v;
                        break;
                    }


                }
                if(empty($usr_list['audit']))
                {
                    // 没有审核人 直接通过
                    return self::audit_adopt($db_name,$app_id,$unique_code,$resource_id,$param);
                }
                //给审核人发送
                $arr = [];
                foreach ($usr_list['audit'] as $k => $v )
                {
                    $arr[] = [
                        'name' => $mess['name'],
                        'member_id' => $mess['member_id'],
                        'show_type_name' => '用户入驻',
                        'member_name' => $mess['name'],
                        'audit_user_id' => $v['id'],
                        'audit_tier_id' =>$audit_tier['id'],
                        'resource_id' => $resource_id,
                        'audit_type_id' => $audit_type['id'],
                        'audit_type_name' => $audit_type['name'],
                        'resource_mess' => $mess,
                        'code' => $audit_type['code'],
                        'type' => $audit_type['type'],
                        'state' => '1',
                        'status' => '1',
                        'resource_status' => $mess['status'],
                        'operation_id' => '',
                        'operation_name' => '1',
                        'opertion_time' => '',
                        'create_time' => date('Y-m-d H:i:s'),
                    ];
                }

                // 抄送
            foreach ($usr_list['noicte'] as $k => $v )
            {
                //
            }
            Db::table($db_name.'.audit_message') -> insertAll($arr);
             ModelLog::add_log($db_name,[
                 ['member_id' => $mess['member_id'],'create_time' => date('Y-m-d H:i:s'),'member_name' => $mess['name'],'resource_id' => self::table_name($unique_code),'remark' => '提交了用户注册申请【'.$mess['name'].'】']
             ]);

                break;
        }

        return ['code' => 200200,'msg' => '操作成功','data' => []];

    }

    /**
     * 返回审核下人员信息
    */
    static public function audit_user_list($audit,$table_name)
    {
        $noicte  = [];
        $audit_user = [];
        foreach ($audit['audit_list'] as $k => $v )
        {
            if($table_name == 'order')
            {

            }else{
                if($v['audit_way'] == 1 && $v['audit_user_type'] == 1)
                {
                    $audit_user[] = $v['audit_user_id'];
                }
                if($v['audit_way'] == 2 && $v['audit_user_type'] == 1)
                {
                    $noicte[] = $v['audit_user_id'];
                }

            }
        }
        $audit_user = Db::table('project_user')->whereIn('id',implode(',',$audit_user))->field('id,name')->select();
        $noicte = Db::table('project_user')->whereIn('id',implode(',',$noicte))->field('id,name')->select();
        return ['audit' => $audit_user,'noicte' => $noicte];
    }


    /**
     * 审核通过
     * db_name 数据库名称
     * unique_code 唯一码
     * resource_id 主键id
     * param 接口参数
    */
    static public function audit_adopt($db_name,$app_id,$unique_code,$resource_id,$param)
    {
        switch ($unique_code)
        {
            // 用户入驻审核
            case 'D9823B82-EDB1-B0C5-A221-F2D5E1800445-0C693EDA32':
                Db::table('project_user_relation')
                    ->where('app_id',$app_id)
                    ->where('state','1')
                    ->where('id',$resource_id)
                    ->update(['status' => '2','audit_time' => date('Y-m-d H:i:s')]);

                break;
        }
        return ['code' => 200200,'msg' => '操作成功',[]];

    }

}