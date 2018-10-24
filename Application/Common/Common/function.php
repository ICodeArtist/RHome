<?php
/**
*账户登录
*@param $type
*@return $res
*/
function MemberLogin($account,$password,$type=''){

  $query['account'] = $account;
  if($type == '1'){
    $user = D('Admins')->where($query)->find();
  }else if($type == '2'){
    $query['ifdelete'] = 0;
    $user = D('Users')->where($query)->find();
  }
  $ImageUrl = C('ImageUrl');
  if(empty($user)){
    $res['code'] = '201';
    $res['date'] = '账号不存在';
  }else if($user['password'] != md5(md5($password).$user['salt'])){
    $res['code'] = '202';
    $res['date'] = '密码错误';
  }else{
    $res['code'] = '200';
    $date['uid'] = $user['uid'];
    $date['account'] = $user['account'];
    $date['orgid'] = $user['orgid'];
    $date['permission'] = $user['permission'];
    if($type == '1')
      $date['role'] = $user['role'];
    if($type == '2'){
      $date['realname'] = $user['realname'];
      $date['nickname'] = $user['nickname']?$user['nickname']:"";
      $date['identity'] = $user['identity'];
      $date['gender'] = $user['gender'];
      $date['headpic'] = $ImageUrl.$user['headpic'];
      $date['birthday'] = $user['birthday']?$user['birthday']:"0000-00-00";
      $date['organization'] = $user['party'].' '.$user['branch'];
      $date['party'] = $user['party'];
      $date['branch'] = $user['branch'];
      $date['permission'] = $user['permission'];
      $date['join'] = $user['join']?$user['join']:"0000-00-00";
      $date['workunit'] = $user['workunit']?$user['workunit']:"";
      $date['aut'] = $user['aut'];
      $date['volunt'] = $user['volunt'];
      $date['motto'] = $user['motto']?$user['motto']:"";
      $uid = $date['uid'];
  		$username = $date['realname'];
  		$portraitUri = $date['headpic'];
  		vendor("RC.rongcloud");
      $appKey = C('appKey');
  		$appSecret = C('appSecret');
  		$RongCloud = new \RongCloud($appKey,$appSecret);
  		$result = $RongCloud->user()->getToken($uid, $username, $portraitUri);
  		$re = json_decode($result,true);
  		if($re['code'] == 200){
  			$date['Token'] = $re['token'];
  		}else{
        $date['Token'] = "";
      }
    }
    $res['date'] = $date;
  }
  return $res;
}
/**
*账户注册
*/
function MemberRegister($account,$password,$info=array(),$type=''){
	if(IsAccountExist($account,$type)){
		return false;
	}
	$salt = substr(uniqid(rand()), -6);
  if($type == '1'){
    $Admins = D('Admins');
  	$Admins->account = $account;
  	$Admins->password = md5(md5($password).$salt);
  	$Admins->regdate = time();
  	$Admins->salt = $salt;
    $Admins->nickname = $info['party'];
    $Admins->orgid = $info['orgid'];
    $Admins->bid = $info['bid'];
    $Admins->permission = 2;
  	$uid = $Admins->add();
  }else{
    $Users = D('Users');
    //$MC = D('MyCommunity');
    $Users->account = $account;
  	$Users->password = md5(md5($password).$salt);
  	$Users->regdate = time();
  	$Users->salt = $salt;
    $Users->realname = $info['realname'];
    $Users->nickname = $info['nickname'];
    $Users->phone = $account;
    $Users->identity = $info['identity'];
    $Users->gender = $info['gender'];
    $Users->headpic = $info['headpic'];
    $Users->birthday = $info['birthday'];
    $Users->join = $info['join'];
    $Users->orgid = $info['orgid'];
    $Users->party = $info['party'];
    $Users->branch = $info['branch'];
    $Users->workunit = $info['workunit'];
    $Users->aut = $info['aut'];
  	$uid = $Users->add();
/*
    $MC->communityid = $info['communityid'];
    $MC->flat = $info['flat'];
    $MC->room = $info['room'];
    $MC->uid = $uid;
    $MC->identity = $info['identity'];
    $MC->aut = $info['aut'];
  	$r = $MC->add();*/
  }

	if($uid){
		return $uid;
	}else{
		return false;
	}

}

/**
*管理账户是否已存在
*@return 已存在-true.不存在-false
*/
function IsAccountExist($account,$type){
	$query['account'] = $account;
  $query['ifdelete'] = 0;
  if($type == '1'){
    $user = D('Admins')->where($query)->count();
  }else if($type == '2'){
    $user = D('Users')->where($query)->count();
  }
	if($user>0){
  	return true;
	}else{
		return false;
	}
}

/**
*工具-----接口json格式返回
*@param $status-响应状态码;$date-响应数据;$message-信息
*/
function ApiResult($status=200,$date=array(),$message=''){
	$res['code'] = $status;
	$res['value'] = $date;
	$res['message'] = $message;
	header('Content-Type:application/json; charset=utf-8');
  exit(json_encode($res,0));
}

/**
*检测验证码是否有效
*/
function IsValidCode($account,$code,$type=0,$timeout=180){
  $smslog = D('Smslog');
  $map['tomobile'] = $account;
  $map['type'] = $type;
  $codeinfo = $smslog->field('code,time')->where($map)->order('time desc')->find();
  $rc = array();
  $t = time()-intval($codeinfo['time']);
  if(empty($codeinfo)){
    $rc['code'] = '201';
    $rc['msg'] = '请重新获取验证码';
  }else if($code != $codeinfo['code']){
    $rc['code'] = '202';
    $rc['msg'] = '验证码错误';
  }else if ($t > $timeout){
    $rc['code'] = '203';
    $rc['msg'] = '验证码已过期,请重新获取！';
  }else{
    $rc['code'] = '200';
    $rc['msg'] = 'right';
  }
  return $rc;
}

/**
*post请求json
*/
function http_post_data($url, $data_string){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charset=utf-8',
        'Content-Length: ' . strlen($data_string))
    );
    ob_start();
    curl_exec($ch);
    $return_content = ob_get_contents();
    ob_end_clean();

    $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return array($return_code, $return_content);
}

function GetRandStr($len, $type = 0){
    $chars = array(
        "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
        "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
        "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
        "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
        "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
        "3", "4", "5", "6", "7", "8", "9"
    );
    $charsLen = count($chars) - 1;
    shuffle($chars);
    $output = "";
    if($type == 0)
    {
        for ($i=0; $i<$len; $i++)
        {
            $output .= $chars[mt_rand(0, $charsLen)];
        }
    }
    else
    {
        $output = mt_rand(100000, 999999);
    }
    return $output;
}
/**
*新建文件夹
*/
function createDir($path, $pri=0777){
  if (!file_exists($path)) {
    createDir(dirname($path));
    mkdir($path,$pri);
  }
}


/**
*根据2点经纬度算计距离
* @param $lat1
* @param $lng1
* @param $lat2
* @param $lng2
* @return int
*/
function getDistance($lat1, $lng1, $lat2, $lng2){

    //将角度转为狐度

    $radLat1=deg2rad($lat1);//deg2rad()函数将角度转换为弧度

    $radLat2=deg2rad($lat2);

    $radLng1=deg2rad($lng1);

    $radLng2=deg2rad($lng2);

    $a=$radLat1-$radLat2;

    $b=$radLng1-$radLng2;

    $s=2*asin(sqrt(pow(sin($a/2),2)+cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)))*6378.137/1000;

    return (string)$s;

}

/**
*根据订单状态id获取订单状态
*20-待处理;30-处理中;40-代付款;50-已完成;60-已取消
*/
function getOrderStatusById($sid){
  switch ($sid) {
    case '20':
      $status['name'] = '待处理';
      $status['operate'] = '取消报修';
      break;
    case '30':
      $status['name'] = '处理中';
      $status['operate'] = '等待';
      break;
    case '40':
      $status['name'] = '待付款';
      $status['operate'] = '去付款';
      break;
    case '50':
      $status['name'] = '已完成';
      $status['operate'] = '待评价';
      break;
    case '51':
      $status['name'] = '已完成';
      $status['operate'] = '已评价';
      break;
    case '60':
      $status['name'] = '已取消';
      $status['operate'] = '';
      break;
    default:
      # code...
      break;
  }
  return $status;
}

/**
*根据身份id获取身份  1-业主;2-家属;3-租客;
*/
function getIdentityNameByid($identity){
  switch ($identity) {
    case '1':
      $iden = '业主';
      break;
    case '2':
      $iden = '家属';
      break;
    case '3':
      $iden = '租客';
      break;
    default:
      # code...
      break;
  }
  return $iden;
}

/**
*获取当前星期‘几’
*/
function getNowWeekDay($we){
  switch($we){
	   case '0':
		   $w = '日';
	      break;
	   case '1':
		   $w = '一';
	      break;
	   case '2':
		   $w = '二';
	      break;
	   case '3':
		    $w = '三';
	       break;
	   case '4':
		   $w = '四';
	      break;
	   case '5':
		   $w = '五';
	      break;
	   case '6':
		   $w = '六';
	   break;
   }
   return $w;
}

/**
*生成随机数for ordersn
*/
function get_order_sn()
{
    /* 选择一个随机的方案 */
    mt_srand((double) microtime() * 1000000);
    $timestamp = time();
    $y = date('y', $timestamp);
    $z = date('z', $timestamp);
    $order_sn = $y . str_pad($z, 3, '0', STR_PAD_LEFT) . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

    $info = D('OrderProperty')->where('ordersn='.$order_sn)->find();
    if (empty($info))
    {
        /* 否则就使用这个订单号 */
        return $order_sn;
    }
    get_order_sn();
}

/**
*二维数组按第二维度数组中的键值排序
*SORT_DESC-降序,SORT_ASC-升序
*/
function multi_array_sort($multi_array,$sort_key,$sort=SORT_DESC){
  if(is_array($multi_array)){
    foreach ($multi_array as $row_array){
      if(is_array($row_array)){
        $key_array[] = $row_array[$sort_key];
      }else{
        return false;
      }
    }
  }else{
    return false;
  }
  array_multisort($key_array,$sort,$multi_array);
  return $multi_array;
}

/**
*数组循环左移$p位
*/
function ArrLeftRoundRobin($R_array,$p){
  $Res_Array = array();
  $l = count($R_array);
  for($i=0;$i<$p;$i++){
    $x[$i]=$R_array[$i];
  }
  for($j=$p;$j<$l;$j++){
    $y[]=$R_array[$j];
  }
  $Res_Array = array_merge($y,$x);
  return $Res_Array;
}

/**
*积分记录
*
*/
function setPointLog($uid,$way,$point){
  $PointLog = D('PointLog');
  $PointLog->uid = $uid;
  $PointLog->way = $way;
  $PointLog->point = $point;
  $PointLog->addtime = date('Y-m-d h:i:sa',time());
  $PointLog->add();
}
/**
*
*/
/**
*自定义上传图片方法
*/
function UploadImageByMySelf($dname,$file){
  $basepath = C('public_path');//文件保存根目录
  $ImageUrl = C('ImageUrl');
  $folder = $basepath.'/Upload/images/'.$dname.'/';
  if(!file_exists($folder))
      mkdir($folder);
  $filename=$file["tmp_name"];
  $pinfo=pathinfo($file["name"]);
  $ftype=$pinfo['extension'];
  $destination = $folder.time().".".$ftype;
  $imgurl = $ImageUrl.'/Upload/images/'.$dname.'/'.time().".".$ftype;
  if(!move_uploaded_file ($filename, $destination))
  {
    echo "<font color='red'>移动文件出错！";
    exit;
  }
    /*缩略图*/
  if($file['type'] == "image/pjpeg" || $file['type'] == "image/jpeg"){
    $im = imagecreatefromjpeg($imgurl);
  }elseif($file['type'] == "image/x-png"  || $file['type'] == "image/png"){
    $im = imagecreatefrompng($imgurl);
  }elseif($file['type'] == "image/gif"){
    $im = imagecreatefromgif($imgurl);
  }
  if($im){
    //生成新的文件名
    $basename = time().rand(10,99);
    $newname = 'slt_'.$basename;

    if(file_exists($newname.".jpg")){
      unlink($newname.".jpg");
    }
    $RESIZEWIDTH='500';//定义最大宽
    $thumbimage_url = '/Upload/images/thumb/'.$newname.'.jpg';
    ResizeImage($im,$RESIZEWIDTH,$newname);
    ImageDestroy ($im);
  }
  ossupload($thumbimage_url);
  return $thumbimage_url;
}
/*
*缩略图方法
*压缩到thumb文件夹，新生成一个图文件
*$im-图片,$maxwidth-最大宽度,$name-文件名
*/
function ResizeImage($im,$maxwidth,$name){
  $basepath = C('public_path');//文件保存根目录
	$thumbpath=$basepath.'/Upload/images/thumb/';
	if(!file_exists($thumbpath))
    	mkdir($thumbpath);
	$width = imagesx($im);
	$height = imagesy($im);
	// if($maxwidth && $width > $maxwidth){
  // if(filesize($im)>200){
		// $newwidth = $maxwidth;
		// $newheight = $maxwidth * $height / $width;//高等比变化
    $newwidth = $width;
		$newheight = $height;
		if(function_exists("imagecopyresampled")){
			$newim = imagecreatetruecolor($newwidth, $newheight);
			imagecopyresampled($newim, $im, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
		}else{
			$newim = imagecreate($newwidth, $newheight);
			imagecopyresized($newim, $im, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
		}
		ImageJpeg ($newim,$thumbpath.$name.".jpg");
		ImageDestroy ($newim);
	// }else{
	// 	ImageJpeg ($im,$thumbpath.$name.".jpg");
	// }
}
/*
*缩略图方法2
*原文件压缩，替换原文件
*$im-图片,$imagepath-图片路径,$name-文件名
*/
function ResizeImageReplace($im,$imagepath,$name,$percent=1){
  $basepath = C('public_path');//文件保存根目录
	$thumbpath=$basepath.$imagepath;
	if(!file_exists($thumbpath))
    	mkdir($thumbpath);
	$width = imagesx($im);
	$height = imagesy($im);
  $newwidth = $width * $percent;
	$newheight = $height * $percent;
	if(function_exists("imagecopyresampled")){
		$newim = imagecreatetruecolor($newwidth, $newheight);
		imagecopyresampled($newim, $im, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
	}else{
		$newim = imagecreate($newwidth, $newheight);
		imagecopyresized($newim, $im, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
	}
	ImageJpeg ($newim,$thumbpath.$name);
	ImageDestroy ($newim);
}
/**
*上传excel文件
*/
function UploadExcelFile(){
  $public_path = C('public_path');
  $upload = new \Think\Upload();// 实例化上传类
  $upload->maxSize   =     0 ;// 设置附件上传大小
  $upload->exts      =     array('xlsx', 'xls');// 设置附件上传类型
  $upload->rootPath  =     $public_path; // 设置附件上传根目录
  $upload->savePath  =     'Upload/xlsfile/'; // 设置附件上传（子）目录
  // 上传文件
  $info   =   $upload->upload();
  return $info;
}

/**
*长传图片文件
*@param $savePath->'Upload/xlsfile/'
*/
function UploadImageFile($savePath){
  $public_path = C('public_path');
  $upload = new \Think\Upload();// 实例化上传类
  $upload->maxSize   =     0 ;// 设置附件上传大小
  $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
  $upload->rootPath  =     $public_path; // 设置附件上传根目录
  $upload->savePath  =     $savePath; // 设置附件上传（子）目录
  // 上传文件
  $info   =   $upload->upload();
  ossupload($info['file']['savepath'].$info['file']['savename']);
  return $info;
}
/**
*长传视频文件
*@param $savePath->'Upload/videofile/'
*/
function UploadVideoFile($savePath){
  $public_path = C('public_path');
  $upload = new \Think\Upload();// 实例化上传类
  $upload->maxSize   =     0 ;// 设置附件上传大小
  $upload->exts      =     array('mp4', 'wmv');// 设置附件上传类型
  $upload->rootPath  =     $public_path; // 设置附件上传根目录
  $upload->savePath  =     $savePath; // 设置附件上传（子）目录
  // 上传文件
  $info   =   $upload->upload();
  if(!$info) {// 上传错误提示错误信息
    return $upload->getError();
  }else{
    ossupload($info['file']['savepath'].$info['file']['savename']);
    return $info;
  }
}

 /**
 *下载融云聊天记录zip
 */
 function GetRCMessageHistory($url){
   $url=urldecode($url);
   $fname=basename("$url"); //返回路径中的文件名部分  fetion_sms.zip
   $str_name=pathinfo($fname);  //以数组的形式返回文件路径的信息
   $extname=strtolower($str_name['extension']); //把扩展名转换成小写
   $time=date("Ymd",time());
   $upload_dir="./Public/Upload/MessageHistory/";//上传的路径
   $file_name=$fname;
   $dir=$upload_dir.$file_name;//创建上传目录
   //判断目录是否存在 不存在则创建
   if(!file_exists($upload_dir)){
     mkdir($upload_dir,0777,true);
   }
   $contents=curl_download($url,$dir);

   if($contents){
     $filename = GetArrayFromUnZip($fname);
     return $filename;
   }else{
     return false;
   }
 }

 function curl_download($url, $dir) {
   $ch = curl_init($url);
   $fp = fopen($dir, "wb");
   curl_setopt($ch, CURLOPT_FILE, $fp);
   curl_setopt($ch, CURLOPT_HEADER, 0);
   $res=curl_exec($ch);
   curl_close($ch);
   fclose($fp);
   return $res;
 }

 /**
 *解压后获取文件中的json格式数据
 */
 function GetArrayFromUnZip($flag){
   Vendor('Zip');
   $archive   = new \PHPZip();
   $zipfile   = './Public/Upload/MessageHistory/'.$flag;
   $savepath  = './Public/Upload/MessageHistory/unzip/';
   $array     = $archive->GetZipInnerFilesInfo($zipfile);
   $filecount = 0;
   $dircount  = 0;
   $failfiles = array();
   set_time_limit(0);  // 修改为不限制超时时间(默认为30秒)

   for($i=0; $i<count($array); $i++) {
       if($array[$i][folder] == 0){
           if($archive->unZip($zipfile, $savepath, $i) > 0){
             unlink($zipfile);//删除压缩包
             $filecount++;
           }else{
             $failfiles[] = $array[$i][filename];
           }
       }else{
           $dircount++;
       }
   }
   set_time_limit(30);
   $filename = $array[0]['filename'];
   if(count($failfiles) > 0){
      foreach($failfiles as $file){
          printf("&middot;%s<br>\r\n", $file);
      }
   }
   return $filename;
 }
/**
*改后缀名为xls
*/
  function foreachDir($dirname){
    if(!is_dir($dirname)){
      echo "{$dirname} not effective dir";
      exit();
    }
    $handle=opendir($dirname); //打开目录

    while (($file = readdir($handle))!==false){ //读取目录
      if($file!="." && $file!='..'){
        if(is_dir($dirname.$file)){
          echo $dirname.$file."<br/>";
            //foreachDir($dirname.$file);  //如果注释号去掉，将会递归修改文件夹内的文件夹文件
        }else{
          //echo "--".$dirname."/".$file."<br/>";
          $temp = substr($file, strrpos($file, '.')+1);  //获取后缀格式
          //echo $temp;
          if($temp != "xlsx"){
            $pos = strripos($file,'.');  //获取到文件名的位置
            $filename = substr($file,0,$pos);  //获取文件名
            rename($dirname.'/'.$file,$dirname.'/'.$file.'.xlsx'); //替换为php后缀格式。
          }
        }
      }
    }
  }

  function json_encode_ex($msg)
  {
      return json_encode($msg, JSON_UNESCAPED_UNICODE);
  }
//日志
  function RH_log($msg, $type='site')
  {
      if (is_array($msg)){
          $msg = json_encode_ex($msg);
      }
      $time = date('Y-m-d H:i:s');
      $mtime = microtime(true);
      $str = "[".$time.$mtime."]:". $msg ."\r\n";
      $logtime = date('Y-m-d',time());
      $file = ROOT_PATH."/Public/logs/rh_".$logtime."_".$type.".log";
      @file_put_contents($file, $str, FILE_APPEND);
  }

  function clientType(){
    $agent = $_SERVER['HTTP_USER_AGENT'];
//        print_r($_SERVER);
    $agent = strtolower($agent);
//        echo $agent;exit;
    if(strpos($agent, 'iphone')||strpos($agent, 'ipad')){
        return 'ios';
    }else if(strpos($agent, 'android')){
        return 'android';
    }else{
        return $agent;
//            return false;
    }
  }
  /**
  *发送自定义短信
  */
  function SmsMessage($mobile,$iclass,$message=''){
		$code = GetRandStr(6,1);

		$timeStamp = date("YmdHis");//yyyyMMddhhmmsszzz
		$transactionID = $timeStamp;
		$streamingNo = '90000011'.$timeStamp;
		$string = $timeStamp.$transactionID.$streamingNo.'Sxzx7&8*';
		$authenticator = base64_encode(pack('H*',md5($string)));
		$r['siid'] = C('siid');
		$r['user'] = C('user');
		$r['streamingNo'] = $streamingNo;
		$r['timeStamp'] = $timeStamp;
		$r['transactionID'] = $transactionID;
		$r['authenticator'] = $authenticator;
		$r['mobile'] = trim($mobile);
		$r['content'] = $message;

		$url  = C('smsurl');
		$data = json_encode($r);

		list($return_code, $return_content) = http_post_data($url, $data);
		$re_content = json_decode($return_content,true);
		if(($return_code == 200) && ($re_content['retCode'] == '0000')){
			$smslog = D('Smslog');
			$smslog->tomobile = $mobile;
			$smslog->type = $iclass;
			$smslog->content = $r['content'];
			$smslog->code = $code;
			$smslog->time = time();
			$smslog->add();
      $res['code'] = '200';
			$res['date'] = (string)$code;
			$res['msg'] = '';
		}else{
      $res['code'] = '201';
			$res['date'] = '';
			$res['msg'] = $re_content['retMsg'];
    }
    return $res;
	}

  /**
  *@param $cateid
  *@return 0-党建动态;1-金点子;2-心里话;3-美哉越城
  */
  function getDynamicCate($cateid){
    switch ($cateid) {
      case '0':
        $name = "党建动态";
        break;
      case '1':
        $name = "金点子";
        break;
      case '2':
        $name = "心里话";
        break;
      case '3':
        $name = "美哉越城";
        break;
      default:
        $name = "未知类型";
        break;
    }
    return $name;
  }
  /**
  * 积分增加
  */
  function addScore($uid,$score,$type='0'){
    $Score = D('Score');
    $Org = D('Org');
    if($type == '0'){
      $Score->where('uid='.$uid)->setInc('sum',$score);
    }
    //判断是否加入了组织
    $phone = D('Users')->where('uid='.$uid)->getField('account');
    $oidArr = D('OrgJoin')->where('phone="'.$phone.'"')->field('oid')->select();
    if(!empty($oidArr)){
      foreach ($oidArr as $key => $value) {
        $ifexist = $Org->where('id='.$value['oid'])->count();
        if($ifexist>0){
          $Org->where('id='.$value['oid'])->setInc('score',$score);
        }
      }
    }
  }
  /**
     * @creator Jimmy
     * @data 2018/1/05
     * @desc 数据导出到excel(csv文件)
     * @param $filename 导出的csv文件名称 如date("Y年m月j日").'-test.csv'
     * @param array $tileArray 所有列名称
     * @param array $dataArray 所有列数据
     */
    function exportToExcel($filename, $tileArray=[], $dataArray=[]){
        ini_set('memory_limit','512M');
        ini_set('max_execution_time',0);
        ob_end_clean();
        ob_start();
        header("Content-Type: text/csv");
        header("Content-Disposition:filename=".$filename);
        $fp=fopen('php://output','w');
        fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));//转码 防止乱码(比如微信昵称(乱七八糟的))
        fputcsv($fp,$tileArray);
        $index = 0;
        foreach ($dataArray as $item) {
            if($index==1000){
                $index=0;
                ob_flush();
                flush();
            }
            $index++;
            fputcsv($fp,$item);
        }

        ob_flush();
        flush();
        ob_end_clean();
    }
    function ossupload($file){
    	$rootPath = dirname(THINK_PATH).'/Public';
    	vendor('aliyunoss.autoload');
    	$accessKeyId = "LTAIXljSxJ6HsklJ";//去阿里云后台获取秘钥
    	$accessKeySecret = "H6OI9YJ18I4sEmNQ6JKWZTNNs5kp4c";//去阿里云后台获取秘钥
    	$endpoint = "oss-cn-hangzhou.aliyuncs.com";//你的阿里云OSS地址
    	$ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
    	$bucket= "goodcreekvanguardbackup";//oss中的文件上传空间
    	$savename = explode("/",$file);
    	$object = $savename[4];//想要保存文件的名称
    	try{
    		$getOssInfo  = $ossClient->uploadFile($bucket,$object,$rootPath.$file);
    		$getOssPdfUrl = $getOssInfo['info']['url'];
    		// unlink($rootPath.$file);
    		return str_replace("http","https",$getOssPdfUrl);
    	}catch(OssException $e) {
        // return false;
      }
    }
?>
