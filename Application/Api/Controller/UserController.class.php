<?php
namespace Api\Controller;
use Think\Controller;
class UserController extends Controller {
	/**
	*用户登录接口  1->管理员；2->党员
	*/
    public function login(){
			$account = I('account');
			$password = I('password');
			$usertype = I('usertype');
			$res = MemberLogin($account,$password,$usertype);
			if($res['code'] != '200'){
				ApiResult($res['code'],'',$res['date']);
			}else{
				ApiResult($res['code'],$res['date'],'');
			}
    }

	/**
	*用户注册接口
	*/
	public function register(){
		$account = I('account');
		// $code = I('code');
		if(IsAccountExist($account,'2')){
			ApiResult('204','','用户已存在');
		}
		$nickname = I('nickname');
		$user = D('Users')->where('nickname='.'"'.$nickname.'"')->find();
		if(!empty($user)){
			ApiResult('205','','该昵称已经存在');
		}else{
			$info['nickname'] = $nickname;
		}
		// $rc = IsValidCode($account,$code,'0',1800);
		// if($rc['code'] != '200'){
		// 	ApiResult($rc['code'],'',$rc['msg']);
		// }
		$password = trim(I('password'));
    $identity = I('identity');
    $info['identity'] = $identity;
    if($identity=='流动'){
      $info['identity'] = '1';
    }
    if($identity=='不流动'){
      $info['identity'] = '2';
    }
		$info['realname'] = trim(I('realname'));
    $gender = I('gender');
    $info['gender'] = $gender;
    if($gender=='男'){
      $info['gender'] = '1';
    }
    if($gender=='女'){
      $info['gender'] = '2';
    }
		if($info['gender'] == '1'){
			$info['headpic'] = '/static/default/man.jpg';
		}else{
			$info['headpic'] = '/static/default/woman.jpg';
		}
		$party = trim(I('party'));
		$OrgParty = D('OrgParty');
		$orgid = $OrgParty->getOrgidByParty($party);
    $info['nickname'] = $nickname;
		$info['orgid'] = $orgid;
    $info['join'] = I('join');
    $info['birthday'] = I('birthday');
    $info['workunit'] = I('workunit');
		$info['party'] = $party;
    $info['branch'] = I('branch');
    $info['aut'] = 0;//默认认证

		$uid = MemberRegister($account,$password,$info,'2');
		if($uid){
			$P = D('Point');
			$P->uid = $uid;
			$P->add();
      $res = MemberLogin($account,$password,'2');//注册成功，去登录
			ApiResult($res['code'],$res['date'],'');
		}else{
			ApiResult('206','','注册失败');
		}
	}

	/**
	*重置密码
	*/
  public function resetpassword(){
    $account = trim(I('account'));
    if(!IsAccountExist($account,'2')){
      ApiResult('204','','用户不存在');
    }
    $newpassword= I('newpassword');
    $Users = D('Users');
    $uinfo = $Users->where('account='.$account)->find();
    $data['password'] = md5(md5($newpassword).$uinfo['salt']);//salt不重置
    if($Users->where('account='.$account)->save($data)){
      $res = MemberLogin($account,$newpassword,'2');//
      ApiResult($res['code'],$res['date'],'');
    }else{
      ApiResult('205','','重置密码失败');
    }
  }

	/**
	*获取用户信息
	*/
	public function info(){
		$uid = I('uid');
		$userinfo = $this->getInfoByUid($uid);
		ApiResult('200',$userinfo,'');
	}

	/**
	*用户编辑资料
	*/
	public function updateinfo(){
		$uid = I('uid');
    $headpic = I('headpic');
		$nickname = trim(I('nickname'));
		$gender = I('gender');
		$birthday = I('birthday');
    $party = trim(I('party'));
		$branch = trim(I('branch'));
    $join = I('join');
		$identity = I('identity');
		$workunit = I('workunit');
    $volunt = I('volunt');
    $motto = I('motto');
		$UserModel = D('Users');
		$aut = $UserModel->where('uid='.$uid)->getField('aut');
		if(!$aut)
			ApiResult('201','','未认证用户不能编辑');
		if($nickname){
			$UserInfo = $UserModel->where('nickname='.'"'.$nickname.'"')->find();
			if(!empty($UserInfo)){
				ApiResult('202','','该昵称已经存在');
			}
			$UserModel->nickname = $nickname;
		}
		if($gender){
      $g = $gender;
      if($gender=='男')
        $g = '1';
      if($gender=='女')
        $g = '2';
			$UserModel->gender = $g;
		}
		if($birthday){
			$UserModel->birthday = $birthday;
		}
		if($headpic){
	    $img = str_replace('&quot;','"',$headpic);//格式化&quot;->"
			$UserModel->headpic = $img;
		}
    if($party){
      $OrgParty = D('OrgParty');
  		$orgid = $OrgParty->getOrgidByParty($party);
      $UserModel->orgid = $orgid;
			$UserModel->party = $party;
		}
		if($branch){
			$UserModel->branch = $branch;
		}
    if($join){
			$UserModel->join = $join;
		}
    if($identity){
      $ident = $identity;
      if($identity=='流动')
        $ident = '1';
      if($identity=='不流动')
        $ident = '2';
			$UserModel->identity = $ident;
		}
		if($workunit){
			$UserModel->workunit = $workunit;
		}
    if($volunt){
      $UserModel->volunt = $volunt;
      $Score = D('Score');//注册志愿者，50积分
      $ifexist = $Score->where('uid='.$uid)->count();
      if($ifexist == 0){
        $Score->uid = $uid;
        $Score->sum = 50;
        $Score->add();
      }
    }
    if($motto){
      $UserModel->motto = $motto;
    }
		$re = $UserModel->where('uid='.$uid)->save();
		if($re){
			$userinfo = $this->getInfoByUid($uid);
			ApiResult('200',$userinfo,'');
		}else{
			ApiResult('203','','更新失败或没有改变任何信息');
		}
		ApiResult('204','','输入更改信息有缺失');
	}


	private function getInfoByUid($uid){
		$ImageUrl = C('ImageUrl');
		$userinfo = D('Users')->where('uid='.$uid)
		->field('uid,account,headpic,realname,nickname,gender,birthday,orgid,party,branch,join,identity,workunit,aut,permission,volunt,motto')
		->find();
		$userinfo['headpic'] = $ImageUrl.$userinfo['headpic'];
		$userinfo['nickname'] = $userinfo['nickname']?$userinfo['nickname']:"";
		$userinfo['birthday'] = $userinfo['birthday']?$userinfo['birthday']:"";
    $userinfo['branch'] = $userinfo['branch']?$userinfo['branch']:"";
    $userinfo['organization'] = $userinfo['party'].' '.$userinfo['branch'];
    $userinfo['motto'] = $userinfo['motto']?$userinfo['motto']:"";
		return $userinfo;
	}
}
