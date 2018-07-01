<?php
namespace Admin\Controller;
use Think\Controller;
class AdminController extends Controller {

  public function mlist(){
    $Admins = D('Admins');
    $ainfo = $Admins->where('1=1')->field('uid,account,regdate,permission')->select();
    foreach ($ainfo as $key => $value) {
      $ainfo[$key]['regdate'] = date('Y-m-d H:i:s',$value['regdate']);
    }
    $this->assign('ainfo',$ainfo);
    $this->display('adminlist');
  }

  public function addAdmin(){
    $account = trim($_POST['account']);
    $password = trim($_POST['password']);
    $orgid = $_POST['orgid'];
    $branch = $_POST['branch'];
    if(IsAccountExist($account,'1')){
      $this->error('该账号已存在');
    }else{
      $party = D('OrgParty')->where('orgid='.$orgid)->getField('party');
      $query['orgid'] = $orgid;
      $query['party'] = $party;
      $query['branch'] = $branch;
      $bid = D('OrgBranch')->where($query)->getField('id');
      $info['party'] = $party;
      $info['orgid'] = $orgid;
      $info['bid'] = $bid;
      $uid = MemberRegister($account,$password,$info,'1');
      if($uid){
        $this->success('新增成功', __CONTROLLER__.'/mlist');
      }else{
        $this->error('注册失败');
      }
    }
  }

  public function SavePassWord(){
    $uid = $_POST['uid'];
    $newpassword = $_POST['newpassword'];
    $Admins = D('Admins');

    $UserInfo = $Admins->where('uid='.$uid)->find();
    $Admins->password = md5(md5($newpassword).$UserInfo['salt']);
    $re = $Admins->where('uid='.$uid)->save();

    $data['code'] = '200';
    $this->ajaxReturn($data);
  }

  public function delAdmin(){
    $uid = $_POST['uid'];
		$Admins = D('Admins');
		$re = $Admins->where('uid='.$uid)->delete();
		if($re){
			$data['msg'] = 'success';
		}else{
			$data['msg'] = 'fail';
		}
		$this->ajaxReturn($data);
  }

  /**
  *当前积分规则
  */
  public function score(){
    $ScoreRule = D('ScoreRule');
    $slist = $ScoreRule->where('1=1')->field('id,cate,score')->select();
    foreach ($slist as $key => $value) {
      switch ($value['cate']) {
        case '1':
          $slist[$key]['name'] = '发布活动';
          break;
        case '2':
          $slist[$key]['name'] = '签到活动';
          break;
        case '3':
          $slist[$key]['name'] = '96345派单';
          break;
        case '4':
          $slist[$key]['name'] = '公益捐赠';
          break;
      }
      unset($value['cate']);
    }
    $this->assign('slist',$slist);
    $this->display('score');
  }
  //修改积分
  public function SaveScore(){
    $id = $_POST['id'];
    $score = $_POST['score'];
    $ScoreRule = D('ScoreRule');
    $ScoreRule->score = $score;
    $ScoreRule->where('id='.$id)->save();
    $data['code'] = '200';
    $this->ajaxReturn($data);
  }
  //权限表
  public function ToEditPriv(){
    if(isset($_GET['uid']) && is_numeric($_GET['uid']) && $_GET['uid']>0){
      $uid = $_GET['uid'];
      $Admins = D('Admins');
      $admin = $Admins->where('uid='.$uid)->field('account,permission,role,lev1,lev2,lev')->find();
      $account = $admin['account'];
      $permission = $admin['permission'];
      $role = explode(',',$admin['role']);
      //一级权限表
      $RoleMenu = D('RoleMenu');
      $rm = $RoleMenu->where('1=1')->field('id,name')->select();
      $rm['account'] = $account;
      $rm['uid'] = $uid;
      //二级权限表
      $RoleList = D('RoleList');
      $rl = $RoleList->where('1=1')->field('id,parent,name')->select();
      $priv = array();
      foreach ($rl as $key => $value) {
        $priv[$value['parent']][$key]['id'] = $value['id'];
        $priv[$value['parent']][$key]['name'] = $value['name'];
        $priv[$value['parent']][$key]['enable'] = in_array($value['id'],$role);
      }
      //区级，镇级权限
      $this->assign('lev1',$admin['lev1']);
      $this->assign('lev2',$admin['lev2']);
      //动态分类
      $lev = array(
        array("name"=>"廉政清风","val"=>"1","enable"=>"0"),
        array("name"=>"文化宣传","val"=>"2","enable"=>"0"),
        array("name"=>"统一战线","val"=>"3","enable"=>"0"),
        array("name"=>"职工之家","val"=>"4","enable"=>"0"),
        array("name"=>"飞扬青春","val"=>"5","enable"=>"0"),
        array("name"=>"铿锵玫瑰","val"=>"6","enable"=>"0"),
        array("name"=>"组工堡垒","val"=>"7","enable"=>"0"),
        array("name"=>"其他","val"=>"8","enable"=>"0")
      );
      foreach ($lev as $key => $value) {
        if( in_array($value['val'],explode(',',$admin['lev']))){
          $lev[$key]['enable'] = "1";
        }
      }
      $this->assign('lev',$lev);
      $this->assign('rm',$rm);
      $this->assign('priv',$priv);
  		$this->display('privfrom');
    }else{
      $this->error('参数错误');
    }
  }
  /**
   * 编辑权限
   */
  public function editAdminPriv(){
    $uid = $_POST['uid'];
    $Admins = D('Admins');
    $role = is_array($_POST['right']) ? implode(',', $_POST['right']) : '';
    $lev= is_array($_POST['lev']) ? implode(',', $_POST['lev']) : '';
    $lev1 = $_POST['lev1']?$_POST['lev1']:"0";
    $lev2 = $_POST['lev2']?$_POST['lev2']:"0";
    $Admins->lev1 = $lev1;
    $Admins->lev2 = $lev2;
    $Admins->lev = $lev;
    $Admins->role = $role;
    $Admins->where('uid='.$uid)->save();
    $data['code'] = '200';
    $this->ajaxReturn($data);
  }
}
