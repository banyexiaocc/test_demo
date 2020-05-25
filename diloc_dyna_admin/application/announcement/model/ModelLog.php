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
class ModelLog extends Model
{
    protected $table = 'db_log';# 表名
    static $table_name = 'db_log';# 表名
    protected $autoWriteTimestamp = false;# 取消自动设置时间戳

    static public function add_log($db_name,$arr)
    {
        foreach ($arr as $k => $v )
        {
            $v['state'] = '1';
            $v['status'] = '1';
            $v['create_time'] = date('Y-m-d H:i:s');
            $arr[$k] = $v;
        }

        Db::table($db_name.'.'.self::$table_name)->insertAll($arr);
        return true;
    }

}