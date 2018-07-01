<?php
namespace Admin\Controller;
use Think\Controller;
class UserController extends Controller {

  /**
  *手机注册
  */
  public function mobileRegist(){
    $permission = session('permission');
    $Users = D('Users');
    $Signin = D('Signin');
    $Party = D('OrgParty');
    $query = 'ifdelete=0 ';
    if($permission == 2){
      $adminorgid = cookie('adminorgid');
      $orgid = $adminorgid;
    }else if(isset($_POST['orgid']) && is_numeric($_POST['orgid']) && $_POST['orgid']>0){
      $orgid = $_POST['orgid'];
      cookie('orgid',$orgid);
    }else if(cookie('orgid')){
      $orgid = cookie('orgid');
    }else{
      $orgid = 16;
    }
    if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>0){
      $p = $_GET['p'];
    }else{
      $p = 1;
    }
    $party['name'] = $Party->where('orgid='.$orgid)->getField('party');
    $party['orgid'] = $orgid;
    $query .= ' and orgid='.$orgid;
    if($_POST['phone'] != null && $_POST['phone'] != ""){
      $phone = trim($_POST['phone']);
      $query .=' and account like "%'.$phone.'%"';
      $p = 1;
    }
    if($_POST['realname'] != null && $_POST['realname'] != ""){
      $realname = trim($_POST['realname']);
      $query .=' and realname like "%'.$realname.'%"';
      $p = 1;
    }
    $ulist = $Users->where($query)
    ->field('uid,realname,gender,identity,join,phone,nickname,party,branch,permission,aut,volunt')
    ->page($p.',10')->select();
    $count = $Users->where($query)->count();
    //志愿者人数
    $query .= ' and volunt=1';
    $voluntnum = $Users->where($query)->count();
    $Page = new \Think\PagePlus($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
    $show = $Page->show();// 分页显示输出
    // print_r($count);exit;
    $this->assign('page',$show);// 赋值分页输出
    foreach ($ulist as $key => $value) {
      if($ulist[$key]['gender'] == '1'){
        $ulist[$key]['gender'] = '男';
      }else{
        $ulist[$key]['gender'] = '女';
      }
      if($ulist[$key]['identity'] == '1'){
        $ulist[$key]['identity'] = '流动';
      }else{
        $ulist[$key]['identity'] = '非流动';
      }
      if($ulist[$key]['join'] == 'null' || $ulist[$key]['join'] == '' || $ulist[$key]['join'] == "0000-00-00"){
        $ulist[$key]['join'] = "2017-01-01";
      }
      $s = $Signin->where('uid='.$value['uid'])->order('addtime desc')->find();
      if(!empty($s)){
        $ulist[$key]['sign'] = date('Y-m-d',$s['addtime']);
      }else{
        $ulist[$key]['sign'] = '';
      }
    }
    $allnum = $Users->where('1=1')->count();//总人数
    $this->assign('party',$party);
    $this->assign('allnum',$allnum);
    $this->assign('voluntnum',$voluntnum);
    $this->assign('ulist',$ulist);
    $this->display('mrlist');
  }
  //认证手机注册用户
  public function autUser(){
    $uid = $_POST['uid'];
    $Users = D('Users');
    $Users->aut = 1;
    $re = $Users->where('uid='.$uid)->save();
    if($re)
      $data['code'] = '200';
    $this->ajaxReturn($data);
  }
  /**
  *删除，ifdelete = 1
  */
  public function delUser(){
    $UserModel = D('Users');
    $UserModel->where('uid='.$_POST['uid'])->setField('ifdelete',1);
    $date['msg'] = 'success';
    $this->ajaxReturn($date);
  }
  //改密码
  public function SavePassWord(){
    $uid = $_POST['uid'];
    $newpassword = $_POST['newpassword'];
    $UserModel = D('Users');

    $UserInfo = $UserModel->where('uid='.$uid)->find();
    $UserModel->password = md5(md5($newpassword).$UserInfo['salt']);
    $re = $UserModel->where('uid='.$uid)->save();

    $data['code'] = '200';
    $this->ajaxReturn($data);
  }
  /**
  *修改用户信息
  */
  public function EditUser(){
    $UserModel = D('Users');
    $uid = $_POST['uid'];
    $phone = trim($_POST['phone']);
    $flag = $UserModel->where('account='.$phone.' and uid !='.$uid)->find();
    if(!empty($flag)){
      $data['code'] = '201';
      $this->ajaxReturn($data);
    }else{
      $orgid = $_POST['party'];
      if(isset($orgid) && is_numeric($orgid) && $orgid > 0){
        $party = D('OrgParty')->where('orgid='.$orgid)->getField('party');
        $UserModel->orgid = $orgid;
        $UserModel->party = $party;
        $UserModel->branch = $_POST['branch'];
      }
      $UserModel->account = $phone;
      $UserModel->realname = $_POST['realname'];
      // $UserModel->permission = $_POST['permission'];
      $UserModel->phone = $phone;
      $UserModel->join = $_POST['join'];
      $UserModel->where('uid='.$uid)->save();
      $data['code'] = '200';
      $this->ajaxReturn($data);
    }
  }
  /**
  *积分
  */
  public function getPointList(){
    $Users = D('Users');
    $Point = D('Point');
    if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>0){
      $p = $_GET['p'];
    }else{
      $p = 1;
    }
    $plist = $Users->alias('s')->join('lct_point p ON s.uid=p.uid')
    ->field('s.uid,s.realname,s.phone,s.party,s.branch,p.sum')->page($p.',10')->order('sum desc')->select();
    $count = $Users->where('1=1')->count();
    $Page = new \Think\PagePlus($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
    $show = $Page->show();// 分页显示输出
    // print_r($count);exit;
    $this->assign('page',$show);// 赋值分页输出
    foreach ($plist as $key => $value) {
      if($value['sum'] < 60){
        $plist[$key]['level'] = '警示型党员';
      }else if($value['sum']<80){
        $plist[$key]['level'] = '学习积极分子';
      }else if($value['sum']<100){
        $plist[$key]['level'] = '学习标兵';
      }else{
        $plist[$key]['level'] = '学习之星';
      }
      $plist[$key]['ranking'] = $key+1;
    }
    $this->assign('plist',$plist);
    $this->display('pointlist');
  }
  /**
  *积分记录
  */
  public function getPointLog(){
    if(isset($_GET['uid']) && is_numeric($_GET['uid']) && $_GET['uid']>0){
      $PointLog = D('PointLog');
      $log = $PointLog->where('uid='.$_GET['uid'])->select();
      $this->assign('log',$log);
      $this->display('pointlog');
    }else{
      $this->error('积分记录获取失败');
    }
  }
  /**
  *导出手机注册人员
  */
  public function gOutMobileRegist(){
    vendor("PHPExcel.PHPExcel");
    vendor("PHPExcel.PHPExcel.IOFactory");
    $Users = D('Users');
    $Signin = D('Signin');
    if(isset($_GET['orgid']) && is_numeric($_GET['orgid']) && $_GET['orgid']>0){
      $orgid = $_GET['orgid'];
    }else{
      $this->error('参数错误,检查网络安全');
    }
    $data = $Users->where('orgid='.$orgid.' and ifdelete=0')->field('uid,realname,account,party,branch,volunt')->select();
    $date = date("Y-m-d",time());
    $filename="手机注册人员表".$date;
    /*$CellValue = "序号,姓名,手机号(账号),镇街级组织,村社区组织,最近签到,是否志愿者";
    $CellValue = explode(',',$CellValue);
    $re = array();
    foreach ( $data as $k => $val ) {
      $re[$k]['uid'] = $k+1;
      $re[$k]['realname'] = $val['realname'];
      $re[$k]['account'] = $val['account'];
      $re[$k]['party'] = $val['party'];
      $re[$k]['branch'] = $val['branch'];
      $s = $Signin->where('uid='.$val['uid'])->order('addtime desc')->find();
      if(!empty($s)){
        $sign = date('Y-m-d',$s['addtime']);
      }else{
        $sign = '';
      }
      if($val['volunt'] == '1'){
        $ifvolunt = '是';
      }else{
        $ifvolunt = '否';
      }
      $re[$k]['sign'] = $sign;
      $re[$k]['ifvolunt'] = $ifvolunt;
    }
    exportToExcel($filename.'.csv',$CellValue,$re);*/
    if($data){
      $phpexcel = new \PHPExcel();
      $phpexcel->getActiveSheet()->setTitle($filename);
      $phpexcel->getActiveSheet()
      ->setCellValue('A1','序号')
      ->setCellValue('B1','姓名')
      ->setCellValue('C1','手机号(账号)')
      ->setCellValue('D1','镇街级组织')
      ->setCellValue('E1','村社区组织')
      ->setCellValue('F1','最近签到')
      ->setCellValue('G1','是否志愿者');
       $i = 2;
       foreach ( $data as $k => $val ) {
         $s = $Signin->where('uid='.$val['uid'])->order('addtime desc')->find();
         if(!empty($s)){
           $sign = date('Y-m-d',$s['addtime']);
         }else{
           $sign = '';
         }
         if($val['volunt'] == '1'){
           $ifvolunt = '是';
         }else{
           $ifvolunt = '否';
         }
         $phpexcel->getActiveSheet()
          ->setCellValue('A'.$i, $k+1)
          ->setCellValue('B'.$i, $val['realname'])
          ->setCellValue('c'.$i, $val['account'])
          ->setCellValue('D'.$i, $val['party'])
          ->setCellValue('E'.$i, $val['branch'])
          ->setCellValue('F'.$i, $sign)
          ->setCellValue('G'.$i, $ifvolunt);
          $i++;
       }
       $obj_Writer = \PHPExcel_IOFactory::createWriter($phpexcel,'Excel5');
       //设置header
       header("Content-Type: application/force-download");
       header("Content-Type: application/octet-stream");
       header("Content-Type: application/download");
       header('Content-Disposition:inline;filename="'.$filename.'.xls"');
       header("Content-Transfer-Encoding: binary");
       header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
       header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
       header("Pragma: no-cache");
       $obj_Writer->save('php://output');//输出
    }else{
       $this -> error('没有数据可以导出');
    }
  }

  /**
  *导出按组织统计人数
  */
  public function gOutRegistStat(){
    vendor("PHPExcel.PHPExcel");
    vendor("PHPExcel.PHPExcel.IOFactory");
    $Users = D('Users');
    $OrgBranch = D('OrgBranch');
    $data = $OrgBranch->where('1=1')->field('orgid,party,branch')->select();
    $date = date("Y-m-d",time());
    $filename="各组织注册人数统计表".$date;
    if($data){
      $phpexcel = new \PHPExcel();
      $phpexcel->getActiveSheet()->setTitle($filename);
      $phpexcel->getActiveSheet()
      ->setCellValue('A1','序号')
      ->setCellValue('B1','一级组织')
      ->setCellValue('C1','二级组织')
      ->setCellValue('D1','日期时间')
      ->setCellValue('E1','注册人数');
       $i = 2;
       foreach ( $data as $k => $val ) {
        $nowdate = date('Y-m-d H:i:s',time());
        //  $query['orgid'] = $val
        $val['ifdelete'] = 0;
        $num = $Users->where($val)->count();
        $phpexcel->getActiveSheet()
        ->setCellValue('A'.$i, $k+1)
        ->setCellValue('B'.$i, $val['party'])
        ->setCellValue('C'.$i, $val['branch'])
        ->setCellValue('D'.$i, $nowdate)
        ->setCellValue('E'.$i, $num);
        $i++;
       }
       $obj_Writer = \PHPExcel_IOFactory::createWriter($phpexcel,'Excel5');
       //设置header
       header("Content-Type: application/force-download");
       header("Content-Type: application/octet-stream");
       header("Content-Type: application/download");
       header('Content-Disposition:inline;filename="'.$filename.'.xls"');
       header("Content-Transfer-Encoding: binary");
       header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
       header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
       header("Pragma: no-cache");
       $obj_Writer->save('php://output');//输出
    }else{
       $this -> error('没有数据可以导出');
    }
  }

  /**
  *添加党员
  */
  public function addUsers(){
    $account = trim($_POST['phone']);
		//$code = I('code');
		if(IsAccountExist($account,'2')){
      $this->error('用户已存在');
		}
		$info['nickname'] = trim($_POST['realname']);
		$password = 123456;
    $info['identity'] = $_POST['identity'];
		$info['realname'] = trim($_POST['realname']);
    $info['gender'] = $_POST['gender'];
		if($info['gender'] == '1'){
			$info['headpic'] = '/static/default/man.jpg';
		}else{
			$info['headpic'] = '/static/default/woman.jpg';
		}
		$party = trim(I('party'));
		$OrgParty = D('OrgParty');
		$party = $OrgParty->where('orgid='.$_POST['orgid'])->getField('party');
		$info['orgid'] = $_POST['orgid'];
    $info['join'] = '';
    $info['birthday'] = '';
    $info['workunit'] = '';
		$info['party'] = $party;
    $info['branch'] = $_POST['branch'];
    $info['aut'] = 1;//默认认证

		$uid = MemberRegister($account,$password,$info,'2');
		if($uid){
			$P = D('Point');
			$P->uid = $uid;
			$P->add();
      $this->success('注册成功', __CONTROLLER__.'/mobileRegist');
		}else{
      $this->error('注册失败');
		}
  }
  public function getUserEditPage(){
    $this->display('user_edit');
  }
  /**
  *签到记录
  */
  // public function getSignLog(){
  //   $Users = D('Users');
  //   $Signin = D('Signin');
  //   $query = '1=1 ';
  //   if(isset($_POST['orgid']) && is_numeric($_POST['orgid']) && $_POST['orgid']>0){
  //     $query .= ' and orgid='.$_POST['orgid'];
  //   }else {
  //     $query .= ' and orgid=16';
  //   }
  //   $uidArr = $Users->where($query)->field('uid')->select();
  //   $query['uid'] = array('in',$uidArr);
  //   $signinfo = $Signin->where($query)->group('uid')->select();
  //   $this->assign('signinfo',$signinfo);
  //   $this->display('signlog');
  // }
}
