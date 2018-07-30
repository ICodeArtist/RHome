<?php
namespace Api\Controller;
use Think\Controller;
class ServiceController extends Controller {

	/**
	*短信发送
	*/
	public function sms(){

		$mobile = I('mobile');
		$iclass = I('iclass');
		$smslog = D('Smslog');
		$ttime = $smslog->where('tomobile="'.$mobile.'"')->order('time desc')->getField('time');
		if((time()-$ttime)<60)
			ApiResult('205','','请在一分钟之后再接收短信');
		if($iclass == '0'){
			if(IsAccountExist($mobile,'2')){
	      ApiResult('202','','用户已存在');
	    }
		}

		if($iclass == '0' || $iclass == '1' || $iclass == '2' || $iclass == '3'){
			$res = $this->smsTool($mobile,$iclass);
			ApiResult($res['code'],$res['date'],$res['msg']);
		}else{
			ApiResult('400','','该功能暂不支持');
		}
	}
  /**
  *找组织
  */
  public function organization(){

    $party = trim(I('party'));

    $OrgParty = D('OrgParty');
		$OrgBranch = D('OrgBranch');
    $res['party'] = array();
    $res['branch'] = array();

    if(!$party){
      $OrgP = $OrgParty->field('party')->select();
      foreach ($OrgP as $key => $value) {
        $res['party'][] = $value['party'];
      }
    }else{
      $map['party'] = $party;
			$res['party'][] = $party;
      $OrgB = $OrgBranch->field('branch')->where($map)->select();
      foreach ($OrgB as $key => $value) {
        $res['branch'][] = $value['branch'];
      }
    }
  //  $res['party'] = array_unique($res['party']);
    //$res['branch'] = array_unique($res['branch']);

    ApiResult('200',$res,'');
  }
	/**
	*注册短信发送功能
	*@param $moblie 接收短信的手机号
	*/
	private function smsTool($mobile,$iclass,$message=''){
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
		switch ($iclass) {
			case '0':
				$content = '您注册的短信验证码是:';
				break;
			case '1':
				$content = '您重置密码的短信验证码是:';
				break;
			case '2':
				$content = '您设置密码的短信验证码是:';
				break;
			case '3':
				$content = '您验证手机号的短信验证码是:';
				break;
			case '4':
				$content = $message.'请打开红色e家APP，零距离会场中正在进行的会议点击参加';
				$code = '';
				break;
			default:
				# code...
				break;
		}
		$r['content'] = $content.$code;

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
	*上传图片
	*@param $imagedate -图片数据   $usefor: 0-发帖;1-用户头像;2-举报
	*/
	public function uploadimage(){
		$uid = I('uid');
		$imagedata = 	I('imagedata');
		$isbase64 = I('isbase64');
		$usefor = I('usefor');
		$basepath = C('public_path');//文件保存根目录
		$ImageUrl = C('ImageUrl');
		$Upload = D('Upload');
		//ApiResult('200',$basepath,'');
		//base64格式处理方式
		if($isbase64){
			$image = $this->_uploadBase64Image($basepath, $imagedata,$usefor);
			$d['uid'] = $uid;
			$d['filetype'] = $image['type'];
      $d['filename'] = $image['name'];
      $d['filepath'] = $image['path'];
      $d['filesize'] = $image['size']/1024;
      $d['domain'] = $ImageUrl;
      $d['addtime'] = time();
			$id = $Upload->add($d);
			$res['imageid'] = (string)$id;
      $res['imageurl'] = $ImageUrl.$image['full_path'];
			//压缩
			$ImageUrl = C('ImageUrl');
			$imgurl = $res['imageurl'];
			$im = imagecreatefromjpeg($imgurl);
			if($d['filesize']>1000){
				$percent = 0.2;
			}else{
				$percent = 1;
			}
			ResizeImageReplace($im,$image['path'],$image['name'],$percent);
			ImageDestroy ($im);
			//end
			$res['imagepath'] = $image['path'].$image['name'];
		}else{

		}
		ApiResult('200',$res,'');
	}

	private function _uploadBase64Image($basepath,$data,$usefor){
		switch ($usefor) {
			case '0':
				$rs['path'] = '/Upload/images/dynamic/' . date('Ymd') . '/';
				break;
			case '1':
				$rs['path'] = '/Upload/images/headpic/' . date('Ymd') . '/';
				break;
			case '2':
				$rs['path'] = '/Upload/images/activity/' . date('Ymd') . '/';
				break;
			case '3':
				$rs['path'] = '/Upload/images/wish/' . date('Ymd') . '/';
				break;
			case '4'://96345
				$rs['path'] = '/Upload/images/servant/' . date('Ymd') . '/';
				break;
			case '5':
				$rs['path'] = '/Upload/images/orghead/' . date('Ymd') . '/';
				break;
			case '6':
				$rs['path'] = '/Upload/images/donate/' . date('Ymd') . '/';
				break;
		}

		$dir = $basepath . $rs['path'];
		createDir($dir);

		$type = 'jpg';
    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $data, $result)) {
        $data = str_replace($result[1], '', $data);
        if ($result[2] && strlen($result[2])>1) {
            $type = $result[2];
        }
    }
    $fname = date('YmdHms', time()).'_'.rand(1000, 9999).'.'.$type;
    $fpath = $dir . $fname;
    $fsize = file_put_contents($fpath, base64_decode($data));
    if ($fsize > 0) {
        $rs['size'] = $fsize;
        $rs['name'] = $fname;
        $rs['type'] = $type;
        $rs['full_path'] = $rs['path'] . $fname;
        $rs['server_path'] = $fpath;
        return $rs;
    } else {
        return false;
    }
	}

	/**
	*常用电话（附近）
	*@param communityid-小区id
	*/
	public function commontel(){
		$communityid = I('communityid');
		$TelInfo = D('UsefulTelephone')->where('communityid='.$communityid)->select();
		$UsftelCate = D('UsftelCategroy')->select();
		foreach ($UsftelCate as $key => $value) {
			$field = $value['field'];
			$classify = $value['id'];
			$res[$field] = array();
			foreach ($TelInfo as $k => $v) {
				if($classify == $v['classify']){
					$res[$field][$k]['telname'] = $v['telname'];
					$res[$field][$k]['telephone'] = $v['telephone'];
				}
			}
			$res[$field] = array_values($res[$field]);//数组重排
		}
		ApiResult('200',$res,'');
	}

	/**
	*验证手机号
	*/
	public function verify(){
		$phone = I('phone');
		$code = I('code');
		$iclass = I('iclass');
		$rc = IsValidCode($phone,$code,$iclass,1800);
		if($rc['code'] != '200'){
			ApiResult($rc['code'],'',$rc['msg']);
		}else{
			ApiResult('200','','验证成功');
		}
	}

	/**
	*版本更新
	*/
	public function version(){
		ApiResult('200','','wait');
	}

	/**
	*融云获取Token
	*/
	public function GetTokenFromRC(){
		$uid = I('uid');
		$username = I('username');
		$portraitUri = I('portraitUri');
		vendor("RC.rongcloud");
		$appKey = C('appKey');
		$appSecret = C('appSecret');
		$RongCloud = new \RongCloud($appKey,$appSecret);
		$result = $RongCloud->user()->getToken($uid, $username, $portraitUri);
		$re = json_decode($result,true);
		if($re['code'] == 200){
			$res['Token'] = $re['token'];
			ApiResult('200',$res,'RC');
		}
	}
	/**
	*创建群组会议
	*/
	public function CreateGroup(){
		$uid = I('uid');
		$uids = I('uids');
		$mname = I('groupname');
		$begin = I('begin');
		$end = I('end');
		$ifsms = I('ifsms');
		$uidArr = explode('|',rtrim($uids,'|'));

		//加入数据库
		$b = strtotime($begin);
		$e = strtotime($end);
		$Meeting = D('Meeting');
		$Meeting->uid = $uid;
		$Meeting->uids = $uids;
		$Meeting->mname = $mname;
		$Meeting->begin = $b;
		$Meeting->end = $e;
		$Meeting->addtime = time();
		$mtid = $Meeting->add();
		//融云上创建群组
		if($mtid){
			//发送短信，返回用户信息
			$Users = D('Users');
			$MeetingJoin = D('MeetingJoin');
			$ImageUrl = C('ImageUrl');
			$map['uid']  = array('IN',$uidArr);
			$userinfo = $Users->where($map)->field('uid,realname,phone,headpic')->select();
			$res['users'] = array();
			foreach ($userinfo as $key => $value) {
				$res['users'][$key]['uid'] = $value['uid'];
				$res['users'][$key]['realname'] = $value['realname'];
				$res['users'][$key]['phone'] = $value['phone'];
				$res['users'][$key]['headpic'] = $ImageUrl.$value['headpic'];
				if($ifsms){
					$re = $this->smsTool($value['phone'],'4','您有会议名称:'.$mname.',会议时间:'.$begin.'的会议需要参加。');
					if($re['code'] != '200')
						ApiResult('202','','通知失败');
				}
				$MeetingJoin->mtid = $mtid;
				$MeetingJoin->uid = $value['uid'];
				$MeetingJoin->add();
			}
			$res['mtid'] = $mtid;
			vendor("RC.rongcloud");
			$appKey = C('appKey');
  		$appSecret = C('appSecret');
			$RongCloud = new \RongCloud($appKey,$appSecret);
			$result = $RongCloud->group()->create($uidArr, $mtid, $mname);
			$re = json_decode($result,true);
			if($re['code'] != 200)
				ApiResult('201','','建群出错');
		}

		$res['meeting'] = $mname;
		$res['begin'] = $begin;
		$res['end'] = $end;
		if(time()<$b){
			$res['status'] = '未开始';
		}else if(time()>$e){
			$res['status'] = '已结束';
		}else{
			$res['status'] = '进行中';
		}
		ApiResult('200',$res,'');
	}
	/**
	*参加会议
	*/
	public function JoinMeeting(){
		$mtid = I('mtid');
		$uid = I('uid');
		$MeetingJoin = D('MeetingJoin');
		$joinuser = $MeetingJoin->where('mtid='.$mtid.' and uid='.$uid)->find();
		if(empty($joinuser))
			ApiResult('201','','你不在这个会议里');
		$res = $MeetingJoin->where('mtid='.$mtid.' and uid='.$uid)->setField('ifsign',1);
		ApiResult('200','','成功参加');
	}
	/**
	*查询群成员
	*/
	public function GetUserByGroupID(){
		$groupid = I('groupid');
		vendor("RC.rongcloud");
		$appKey = C('appKey');
		$appSecret = C('appSecret');
		$RongCloud = new \RongCloud($appKey,$appSecret);
		$result = $RongCloud->group()->queryUser($groupid);
		$re = json_decode($result,true);
		if($re['code'] != 200)
			ApiResult('201',$re['users'],'没有成员');
		ApiResult('200',$re['users'],'');
	}
	/**
	*版本更新   1-Android;2-iOS
	*/
	public function versions(){
		$iDevice = I('iDevice');
		$Configs = D('Configs');
		$config = $Configs->where('1=1')->select();
// 		$res[1] = array(
// 			'id' => '1',
// 			'version' => '2.1.3',
//       'info' => '为热烈庆祝中国共产党成立97周年，进一步增强党组织的凝聚力和战斗力，不断提高党员干部深入学习贯彻党的十九大精神的自觉性，“七一”系列庆祝党的生日。
// 建党节内容更新！
// 建党节内容更新！
// 建党节内容更新！
// 建党节内容更新！
// 建党节内容更新！',
//       'lowest' => '1',	// 低于这个版本必须升级，为空或者高于这个版本不处理
//       'url' => 'http://122.237.102.36:8000/RHome/index.php/api/service/download'
// 		);

		// $res[2] = array(
		// 	'id' => '1',
    //   'version' => '13',
    //   'info' => 'iOS',
    //   'lowest' => '1',	// 低于这个版本必须升级，为空或者高于这个版本不处理
    //   'url' => 'http://122.237.102.36:8000/RHome/index.php/api/service/download'
		// );
		$res[1]['id'] = '1';
		$res[1]['version'] = $config[0]['value'];
		$res[1]['info'] = $config[2]['value'];
		$res[1]['lowest'] = $config[4]['value'];
		$res[1]['url'] = 'http://122.237.102.36:8000/RHome/index.php/api/service/download';

		$res[2]['id'] = '1';
		$res[2]['version'] = $config[1]['value'];
		$res[2]['info'] = $config[3]['value'];
		$res[2]['lowest'] = $config[5]['value'];
		$res[2]['url'] = 'http://122.237.102.36:8000/RHome/index.php/api/service/download';
		ApiResult('200',$res[$iDevice],'');
	}

	public function download(){
		// print_r('暂停下载');exit;
		$client = clientType();//ios  android
		if ($client == 'ios'){
			//header('Location: http://a.app.qq.com/o/simple.jsp?pkgname=com.hbersmember.main');
		}else{
			// header('Location: http://122.237.102.36:8000/redhome.apk');
			$Configs = D('Configs');
			$downurl = $Configs->where('id=7')->getField('value');
			header('Location: http://122.237.102.36:8000/RHome/Public/'.$downurl);
		}
	}
	/**
	 * 镇街级组织和id
	 */
	public function getorgid(){
		$OrgParty = D('OrgParty');
		$OrgP = $OrgParty->field('orgid,party')->select();
		if(empty($OrgP))
			ApiResult('201','','没有');
		// foreach ($OrgP as $key => $value) {
		// 	$res[$key]['party'] = $value['party'];
		// 	$res[$key]['orgid'] = $value['orgid'];
		// }
		$res = array();
		foreach ($OrgP as $key => $value) {
			if(strpos($value['party'],'街')===false)
				$res[] = $OrgP[$key];
		}
		foreach ($OrgP as $key => $value) {
			if(strpos($value['party'],'街'))
				$res[] = $OrgP[$key];
		}
		ApiResult('200',$res,'');
	}
	/**
	 *村社区组织id
	 */
	public function getbranchid(){
		$OrgBranch = D('OrgBranch');
		$OrgParty = D('OrgParty');
		$orgid = I('orgid');
		$party = $OrgParty->where('orgid='.$orgid)->getField('party');
		$OrgB = $OrgBranch->where('orgid='.$orgid.' and branch != "'.$party.'"')->field('id as branchid,branch')->select();
		if(empty($OrgB))
			ApiResult('201','','没有');
		foreach ($OrgB as $key => $value) {
			$res[$key]['branch'] = $value['branch'];
			$res[$key]['branchid'] = $value['branchid'];
		}
		ApiResult('200',$res,'');
	}
	/**
	 * 村社区下的人员
	 */
	public function getuser(){
		$OrgBranch = D('OrgBranch');
		$Users = D('Users');
		$branchid = I('branchid');
		$iPageItem = I('iPageItem');
		$iPageIndex = I('iPageIndex');
		$branch = $OrgBranch->where('id='.$branchid)->getField('branch');
		$uinfo = $Users->where('branch="'.$branch.'"')->field('uid,realname')
		//->page($iPageIndex+1,$iPageItem)
		->select();
		if(empty($uinfo))
			ApiResult('201','','没有');
		foreach ($uinfo as $key => $value) {
			$res[$key]['realname'] = $value['realname'];
			$res[$key]['uid'] = $value['uid'];
		}
		ApiResult('200',$res,'');
	}
	/**
	*获取镇级人员积分
	*/
	public function getPartyPoint(){
		$orgid = I('orgid');
		$iPageItem = I('iPageItem');
		$iPageIndex = I('iPageIndex');
		$phone = I('phone');
		$Users = D('Users');
		$query = 'u.orgid='.$orgid;
		if($phone){
			$query .= ' and phone="'.$phone.'"';
		}
		$pArr = $Users->alias('u')->join('lct_point p ON p.uid=u.uid')->where($query)
		->field('p.sum,u.realname,u.nickname')->page($iPageIndex+1,$iPageItem)->select();
		if(empty($pArr)){
			ApiResult('201','','没有');
		}else{
			ApiResult('200',$pArr,'');
		}
	}
	public function exthingonoff(){
		$ifon = I('ifon');
		$Onoff = D('Onoff');
		//ifon不填，获得的是状态;   ifon填，改变状态
		if($ifon !="" && is_numeric($ifon) && $ifon !=null){
			$Onoff->where('id=1')->setField('ifon',$ifon);
		}
		$info = $Onoff->where('id=1')->field('name,ifon')->find();
		$res['name'] = $info['name'];
		$res['ifon'] = $info['ifon'];
		ApiResult('200',$res,'');
	}
}
