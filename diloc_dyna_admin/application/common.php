<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
use think\Db;
use think\facade\Cache;

/**
 * 生成access_token 方法
 * @param  [type] $username [description] 登录用户名
 * @param  [type] $pwd      [description] 登录密码
 *
 */
function creat_access_token($uid)
{
    $chars = $uid."abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789".$uid;
    $password = "";
    for ( $i = 0; $i < 8; $i++ ){
        $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
    }
    $data = $password.$_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']
        . time() . rand();
    return sha1(md5($data));
}


/**
 *所有串案都是必传参数时,可以使用次方法进行参数过滤
 * $param 接收的参数组成的一维数组
 * $param_num 参数个数
 */
function must_param_filtration($param,$param_num){
    if(empty($param)||count($param)!=$param_num){
        return "未传入所有必传字段值";
    }else{
        foreach($param as $key=>$value){
            if(empty($param[$key])){
                return $key."传参错误";
            }
        }
    }
}


/**
 * 生成refresh_token 方法
 * @param  [type] $username [description] 登录用户名
 * @param  [type] $pwd      [description] 登录密码
 *
 */
function creat_refresh_token($uid)
{

    $chars = $uid."abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $password = "";
    for ( $i = 0; $i < 10; $i++ ){
        $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
    }
    $data = $password.$_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']
        . time() . rand();
    return sha1(md5($data));
}

/**
 * 生成临时凭证code 方法
 * @param  [type] $uid [description] 登录用户的user表编号
 */
function create_code($uid){
    $chars = $uid."abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $password = "";
    for ( $i = 0; $i < 10; $i++ ){
        $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
    }
    $data = $password.$_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']
        . time() . rand();
    return substr(sha1(md5($data)),5,32);

}



/***
 * 生成随机用户名
 */
function create_nickname(){
    $tou=array('快乐','冷静','醉熏','潇洒','糊涂','积极','冷酷','深情','粗暴','温柔','可爱','愉快','义气','认真','威武','帅气','传统','潇洒','漂亮','自然','专一','听话','昏睡','狂野','等待','搞怪','幽默','魁梧','活泼','开心','高兴','超帅','留胡子','坦率','直率','轻松','痴情','完美','精明','无聊','有魅力','丰富','繁荣','饱满','炙热','暴躁','碧蓝','俊逸','英勇','健忘','故意','无心','土豪','朴实','兴奋','幸福','淡定','不安','阔达','孤独','独特','疯狂','时尚','落后','风趣','忧伤','大胆','爱笑','矮小','健康','合适','玩命','沉默','斯文','香蕉','苹果','鲤鱼','鳗鱼','任性','细心','粗心','大意','甜甜','酷酷','健壮','英俊','霸气','阳光','默默','大力','孝顺','忧虑','着急','紧张','善良','凶狠','害怕','重要','危机','欢喜','欣慰','满意','跳跃','诚心','称心','如意','怡然','娇气','无奈','无语','激动','愤怒','美好','感动','激情','激昂','震动','虚拟','超级','寒冷','精明','明理','犹豫','忧郁','寂寞','奋斗','勤奋','现代','过时','稳重','热情','含蓄','开放','无辜','多情','纯真','拉长','热心','从容','体贴','风中','曾经','追寻','儒雅','优雅','开朗','外向','内向','清爽','文艺','长情','平常','单身','伶俐','高大','懦弱','柔弱','爱笑','乐观','耍酷','酷炫','神勇','年轻','唠叨','瘦瘦','无情','包容','顺心','畅快','舒适','靓丽','负责','背后','简单','谦让','彩色','缥缈','欢呼','生动','复杂','慈祥','仁爱','魔幻','虚幻','淡然','受伤','雪白','高高','糟糕','顺利','闪闪','羞涩','缓慢','迅速','优秀','聪明','含糊','俏皮','淡淡','坚强','平淡','欣喜','能干','灵巧','友好','机智','机灵','正直','谨慎','俭朴','殷勤','虚心','辛勤','自觉','无私','无限','踏实','老实','现实','可靠','务实','拼搏','个性','粗犷','活力','成就','勤劳','单纯','落寞','朴素','悲凉','忧心','洁净','清秀','自由','小巧','单薄','贪玩','刻苦','干净','壮观','和谐','文静','调皮','害羞','安详','自信','端庄','坚定','美满','舒心','温暖','专注','勤恳','美丽','腼腆','优美','甜美','甜蜜','整齐','动人','典雅','尊敬','舒服','妩媚','秀丽','喜悦','甜美','彪壮','强健','大方','俊秀','聪慧','迷人','陶醉','悦耳','动听','明亮','结实','魁梧','标致','清脆','敏感','光亮','大气','老迟到','知性','冷傲','呆萌','野性','隐形','笑点低','微笑','笨笨','难过','沉静','火星上','失眠','安静','纯情','要减肥','迷路','烂漫','哭泣','贤惠','苗条','温婉','发嗲','会撒娇','贪玩','执着','眯眯眼','花痴','想人陪','眼睛大','高贵','傲娇','心灵美','爱撒娇','细腻','天真','怕黑','感性','飘逸','怕孤独','忐忑','高挑','傻傻','冷艳','爱听歌','还单身','怕孤单','懵懂');
    $do = array("的","爱","","与","给","扯","和","用","方","打","就","迎","向","踢","笑","闻","有","等于","保卫","演变");
    $wei=array('嚓茶','凉面','便当','毛豆','花生','可乐','灯泡','哈密瓜','野狼','背包','眼神','缘分','雪碧','人生','牛排','蚂蚁','飞鸟','灰狼','斑马','汉堡','悟空','巨人','绿茶','自行车','保温杯','大碗','墨镜','魔镜','煎饼','月饼','月亮','星星','芝麻','啤酒','玫瑰','大叔','小伙','哈密瓜，数据线','太阳','树叶','芹菜','黄蜂','蜜粉','蜜蜂','信封','西装','外套','裙子','大象','猫咪','母鸡','路灯','蓝天','白云','星月','彩虹','微笑','摩托','板栗','高山','大地','大树','电灯胆','砖头','楼房','水池','鸡翅','蜻蜓','红牛','咖啡','机器猫','枕头','大船','诺言','钢笔','刺猬','天空','飞机','大炮','冬天','洋葱','春天','夏天','秋天','冬日','航空','毛衣','豌豆','黑米','玉米','眼睛','老鼠','白羊','帅哥','美女','季节','鲜花','服饰','裙子','白开水','秀发','大山','火车','汽车','歌曲','舞蹈','老师','导师','方盒','大米','麦片','水杯','水壶','手套','鞋子','自行车','鼠标','手机','电脑','书本','奇迹','身影','香烟','夕阳','台灯','宝贝','未来','皮带','钥匙','心锁','故事','花瓣','滑板','画笔','画板','学姐','店员','电源','饼干','宝马','过客','大白','时光','石头','钻石','河马','犀牛','西牛','绿草','抽屉','柜子','往事','寒风','路人','橘子','耳机','鸵鸟','朋友','苗条','铅笔','钢笔','硬币','热狗','大侠','御姐','萝莉','毛巾','期待','盼望','白昼','黑夜','大门','黑裤','钢铁侠','哑铃','板凳','枫叶','荷花','乌龟','仙人掌','衬衫','大神','草丛','早晨','心情','茉莉','流沙','蜗牛','战斗机','冥王星','猎豹','棒球','篮球','乐曲','电话','网络','世界','中心','鱼','鸡','狗','老虎','鸭子','雨','羽毛','翅膀','外套','火','丝袜','书包','钢笔','冷风','八宝粥','烤鸡','大雁','音响','招牌','胡萝卜','冰棍','帽子','菠萝','蛋挞','香水','泥猴桃','吐司','溪流','黄豆','樱桃','小鸽子','小蝴蝶','爆米花','花卷','小鸭子','小海豚','日记本','小熊猫','小懒猪','小懒虫','荔枝','镜子','曲奇','金针菇','小松鼠','小虾米','酒窝','紫菜','金鱼','柚子','果汁','百褶裙','项链','帆布鞋','火龙果','奇异果','煎蛋','唇彩','小土豆','高跟鞋','戒指','雪糕','睫毛','铃铛','手链','香氛','红酒','月光','酸奶','银耳汤','咖啡豆','小蜜蜂','小蚂蚁','蜡烛','棉花糖','向日葵','水蜜桃','小蝴蝶','小刺猬','小丸子','指甲油','康乃馨','糖豆','薯片','口红','超短裙','乌冬面','冰淇淋','棒棒糖','长颈鹿','豆芽','发箍','发卡','发夹','发带','铃铛','小馒头','小笼包','小甜瓜','冬瓜','香菇','小兔子','含羞草','短靴','睫毛膏','小蘑菇','跳跳糖','小白菜','草莓','柠檬','月饼','百合','纸鹤','小天鹅','云朵','芒果','面包','海燕','小猫咪','龙猫','唇膏','鞋垫','羊','黑猫','白猫','万宝路','金毛','山水','音响','尊云','西安');
    $tou_num=rand(0,331);
    $do_num=rand(0,19);
    $wei_num=rand(0,327);
    $type = rand(0,1);
    $ranstr=substr(time().mt_rand(1,1000000),8,16);
    if($type==0){
        $username=$tou[$tou_num].$do[$do_num].$wei[$wei_num].$ranstr;
    }else{
        $username=$wei[$wei_num].$tou[$tou_num].$ranstr;
    }
    return $username;
}


/**
 * 生成uuid
 * @param  [type] $sign [description] 所属表标记
 * @return [type]       [description] F16C2267-3765-920E-F10F-00EC01D3B590-BF9AC94A7E
 *
 */
function create_uuid($sign){
    $table_sign=strtoupper(substr(md5($sign),10,10));//uuid标记(表标记)
    if (function_exists ( 'com_create_guid' )) {
        return com_create_guid ();
    } else {
        mt_srand ( ( double ) microtime () * 10000 ); //optional for php 4.2.0 and up.随便数播种，4.2.0以后不需要了。
        $charid = strtoupper ( md5 ( uniqid ( rand (), true ) ) ); //根据当前时间（微秒计）生成唯一id.
        $hyphen = chr ( 45 ); // "-"
        $uuid = '' . //chr(123)// "{"
            substr ( $charid, 0, 8 ) . $hyphen . substr ( $charid, 8, 4 ) . $hyphen . substr ( $charid, 12, 4 ) . $hyphen . substr ( $charid, 16, 4 ) . $hyphen . substr ( $charid, 20, 12 ).$hyphen.$table_sign;
        //.chr(125);// "}"
        return  $uuid;
    }
}

	/**
	 * @param $group_id
	 * @return string无限分类所有上级
	 */
	function tower_floor_room_mess_uplevel($pid,$db_name){
		static $data = array();
		$group_id = db($db_name.'.place')->where('id',$pid)->select();
		for($i=0;$i<count($group_id);$i++)
		{
			$data[] = $group_id[$i]['id'];
			tower_floor_room_mess_uplevel($group_id[$i]['pid'],$db_name);
		}
		return $data;
	}



	/**
 * 数返回”null“过滤
 */
function nulltostr($arr)
{

    foreach ($arr as $k=>$v){
        if(is_null($v)) {
            $arr [$k] = '';
        }
        if(is_array($v)) {
            $arr [$k] = nulltostr($v);
        }
    }
    return $arr;
}

/**
 * 返回json 结果
 */
function return_json($code,$msg,$data){
    $result['code']=(int)$code;
    $result['message']=$msg;
    $result['data']=$data;

    echo json_encode($result,JSON_UNESCAPED_UNICODE);die;

}


/**
 * 验证码生成方法
 */
function create_ver_code(){
    $chars ="0123456789";
    $password = "";
    for ( $i = 0; $i < 10; $i++ ){
        $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
    }
    $data = $password.$_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']
        . time() . rand();
    return substr($data,4,4);

}


/**
 * 手机号验证
 * @param  [type] $phone_num [description]
 * @return [type]            [description]
 * 正确返回1
 * 错误返回0
 */
function phone_num_verify($phone_num){
    //上面部分判断长度是不是11位
    if(!preg_match("/^1[34578629]\d{9}$/", $phone_num)){
        //这里有无限想象
        return false;
    }else{
        return true;
    }

}

/**
 * 邮箱格式验证
 * @param  [type] $email_num [description]
 * @return [type]            [description]
 * 正确返回1
 * 错误返回0
 */
function email_num_verify($email_num){
    return  $isMatched = preg_match('/^\w[-\w.+]*@([A-Za-z0-9][-A-Za-z0-9]+\.)+[A-Za-z]{2,14}$/',$email_num, $matches);
}




//二维数组根据某字段,对内部一维数组排序
function array_sort($arr,$keys,$type='desc'){
    $keysvalue = $new_array = array();
    foreach ($arr as $k=>$v){
        $keysvalue[$k] = $v[$keys];
    }
    if($type == 'asc'){
        asort($keysvalue);
    }else{
        arsort($keysvalue);
    }
    reset($keysvalue);
    foreach ($keysvalue as $k=>$v){
        $new_array[$k] = $arr[$k];
    }
    return $new_array;
}


//字段md5加密
function fun_md5($data){
    return md5($data);
}

//时间字段
function fun_date($data){
    return date("Y-m-d H:i:s");
}

//用户默认头像
function default_head_img($data){
    return "/static/head_img/default_head_img.jpg";
}

//默认资源图片
function default_resource_img(){
    return "/static/head_img/default_resource.png";

}



/**
 * @param $group_id
 * @return string无限分类所有上级
 */
function superior_group($group_id,$tab_suffix)
{
    static $data = array();
    $group_id = db('resource_categories'.$tab_suffix)->where('id',$group_id)->select();
    for($i=0;$i<count($group_id);$i++)
    {
        $data[] = $group_id[$i]['id'];

        superior_group($group_id[$i]['pid'],$tab_suffix);
    }
    return $data;
}


	/**
	 * @param $group_id
	 * @return string无限分类所有上级
	 */
	function uplevel_department($id){
		static $data = array();
		$group_id = db('base_resource_type')->where('id',$id)->select();
		for($i=0;$i<count($group_id);$i++)
		{
			$data[] = $group_id[$i]['id'];

			uplevel_department($group_id[$i]['pid']);
		}
		return $data;
	}




/**
 * @param $group_id
 * @return string无限分类所有下级
 */
function department($pid){
    static $data = array();
    $group_id = Db('base_resource_type')->where('pid',$pid)->select();
    for($i=0;$i<count($group_id);$i++)
    {
        $data[] = $group_id[$i]['id'];
        department($group_id[$i]['id']);
    }
    return $data;
}

/**
 * @param $data
 * @param $pId
 * 设备列表树形查询
 */
function getresourceTree($data, $pId)
{
    $tree = '';
    foreach($data as $k => $v)
    {
        if($v['resource_pid'] == $pId) //注：fr_path字段相当于pid,在上面数组中就是fr_path
        {        //父亲找到儿子
            $v['resource_downlevel'] =getresourceTree($data, $v['id']);//注：fr_id字段相当于id，在上数组中就是fr_id
            $tree[] = $v;
            //unset($data[$k]);
        }
    }
    return $tree;
}


/**
 *无限循环下级资源列表
 */
function down_resource_list($pid){
    static $data = array();
    $group_id = Db('resource_infomation')->where('resource_pid',$pid)->select();
    for($i=0;$i<count($group_id);$i++)
    {
        $data[] = $group_id[$i]['id'];
        down_resource_list($group_id[$i]['id']);
    }
    return $data;
}


/**
 * @param $pid
 * @return array
 * 查询模块所有下级
 */
function module_department($pid){
    static $data = array();
    $group_id = db('modules')->where('module_pid',$pid)->select();
    for($i=0;$i<count($group_id);$i++)
    {
        $data[] = $group_id[$i]['id'];
        module_department($group_id[$i]['id']);
    }
    return $data;
}

/**
 * @param $group_id
 * @return string无限分类所有下级
 */
function tower_floor_room_mess_downlevel($pid,$tab_fixed){
    static $data = array();
    $group_id = db('place'.$tab_fixed)->where('place_pid',$pid)->select();
    for($i=0;$i<count($group_id);$i++)
    {
        $data[] = $group_id[$i]['id'];
        tower_floor_room_mess_downlevel($group_id[$i]['id'],$tab_fixed);
    }
    return $data;
}




//楼层房树形数组获取
function getTree($data, $pId)
{
    $tree = '';
    foreach($data as $k => $v)
    {
        if($v['pid'] == $pId) //注：fr_path字段相当于pid,在上面数组中就是fr_path
        {        //父亲找到儿子

            $v['down_level'] = getTree($data, $v['id']);//注：fr_id字段相当于id，在上数组中就是fr_id
            $tree[] = $v;
            //unset($data[$k]);
        }
    }
    return $tree;
}




//秒数换算成"年天时分秒"
function get_time($time){

    if(is_numeric($time)){
        $value = array(
            "years" => 0, "days" => 0, "hours" => 0,
            "minutes" => 0, "seconds" => 0,
        );
        if($time >= 31556926){
            $value["years"] = floor($time/31556926);
            $time = ($time%31556926);
        }
        if($time >= 86400){
            $value["days"] = floor($time/86400);
            $time = ($time%86400);
        }
        if($time >= 3600){
            $value["hours"] = floor($time/3600);
            $time = ($time%3600);
        }
        if($time >= 60){
            $value["minutes"] = floor($time/60);
            $time = ($time%60);
        }
        $value["seconds"] = floor($time);

        //  dump($value);

        if($value['years']!=0){
            $a=$value['years']."年";
        }else{
            $a="";
        }
        if($value['days']!=0){
            $b=$value['days']."天";
        }else{
            if($a==""){
                $b="";
            }else{
                $b="零";
            }

        }
        if($value['hours']!=0){
            $c=$value['hours']."小时";
        }else{
            if($b==""){
                $c="";
            }else{
                $c="零";
            }

        }
        if($value['minutes']!=0){
            $d=$value['minutes']."分钟";
        }else{
            if($c==""){
                $d="";
            }else{
                $d="零";
            }
        }
        if($value['seconds']!=0){
            $e=$value['seconds']."秒";
        }else{
            $e="";
        }



        $t=$a.$b.$c.$d.$e;

        return $t;

    }else{
        return (bool) FALSE;
    }




}


/**
 * 手机短信发送
 */
/**
 * 短信通知发送
 * @param $accountSid  主账号
 * @param $accountToken  主账号token
 * @param $appId 应用id
 * @param $tempId 短信模板编号
 * @param $datas (array)模板参数
 * @param $to 要接受短信通知的信息
 * @return array
 */
function sms_mobile_send($tempId,$datas,$to){//短信模板编号,(array)参数,接收短信的手机号,参数编号
    //引入容联核心代码
    //主帐号
    //---以下测试容联-
   /*  $accountSid= "8aaf070860c426710160c51cf87e00a7";
      //主帐号Token
      $accountToken= "867ad2f87ed843c18b3928440a58d568";
      //应用Id
      $appId="8aaf070860c426710160c51cf8e400ad";*/

    //---以下公司容联--
    $accountSid= "8aaf070868747811016879e7f8a80234";
    //主帐号Token
    $accountToken= "2614b00f9fb94015b3460c7685d7323b";
    //应用Id
    $appId="8aaf070868747811016879e7f8ff023a";

    //请求地址，格式如下，不需要写https://
    $serverIP='app.cloopen.com';
    //请求端口
    $serverPort='8883';
    //REST版本号
    $softVersion='2013-12-26';
    // 初始化REST SDK
    $rest = new \think\CCPRestSDK($serverIP,$serverPort,$softVersion);
    $rest->setAccount($accountSid,$accountToken);
    $rest->setAppId($appId);
    // 发送模板短信
    //echo "Sending TemplateSMS to $to <br/>";
    $result = $rest->sendTemplateSMS($to,$datas,$tempId);

    if($result == NULL ) {
        // return ['code'=>200204,'message'=>'验证码发送失败','data'=>array()];
        return "0";
    }

    if($result->statusCode!=0) {
        //
         //dump($result->statusCode);//160038短信验证码发送过频繁
         return "1";
        //return ['code'=>200204,'message'=>'验证码发送失败','data'=>array()];
        //TODO 添加错误处理逻辑
    }else{
        return "2";

        //TODO 添加成功处理逻辑
    }

}

/**
 * 生成唯一单号
 */
function create_theonly_num($word=""){

    $osn = $word.date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);

    return $osn;
}



	/**
	 * 信息模板参数替换
	 * @$content   例如:用户您好,您关注的设备:{1},监控参数:{2}报警,请及时处理.详情:{3}
	 * @$replace_content  要替换的数组   array(设备名称1,温度,报警颜色......)
	 */
	function module_replace($content,$replace_content)
	{
		foreach ($replace_content as $k => $v )
		{
			$content = preg_replace("/{.*}/iU",$v,$content,1);

		}
		return  $content;
	}










