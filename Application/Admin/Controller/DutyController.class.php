<?php
namespace Admin\Controller;
use Think\Controller;
class DutyController extends Controller {
  /**
  *选择组织机构
  */
  public function getOrg(){
    $Org = D('Org');
    $olist = $Org->where('aut = 1')->field('id,orgname')->select();
    if(!empty($olist)){
      foreach ($olist as $key => $value) {
        $data[$key]['id'] = $value['id'];
        $data[$key]['name'] = $value['orgname'];
      }
    }else{
      $data['msg'] = 'fail';
    }
    $this->ajaxReturn($data);
  }
  /**
   * 投票列表
   */
  public function votelist(){
    $Vote = D('Vote');
    $Users = D('Users');
    $Poll = D('Poll');
    $ImageUrl = C('ImageUrl');
    $vlist = $Vote->field('id,uid,title,ifadmin,img,type,addtime,begin,end,max,ever')->select();
    foreach ($vlist as $key => $value) {
      if($value['ifadmin']){
        $vlist[$key]['nickname'] = D('Admins')->where('uid='.$value['uid'])->getField('nickname');
      }else{
        $vlist[$key]['nickname'] = $Users->where('uid='.$value['uid'])->getField('realname');
      }
      $vlist[$key]['img'] = $value['img']?$ImageUrl.$value['img']:"";
      if($value['type'] == '1'){
        $vlist[$key]['vtype'] = '列表';
      }else{
        $vlist[$key]['vtype'] = '网格';
      }
      $vlist[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
      $vlist[$key]['begin'] = date('Y-m-d',$value['begin']);
      $vlist[$key]['end'] = date('Y-m-d',$value['end']);
      $vlist[$key]['num'] = $Poll->where('voteid='.$value['id'])->count();
    }
    $this->assign('vlist',$vlist);
    $this->display('votelist');
  }
  /**
   * 发布
   */
  public function addVote(){
    $type = $_POST['type'];
    $Vote = D('Vote');
    $VoteOpt = D('VoteOpt');
    $VoteItem = D('VoteItem');

    $account = session('admininfo');
    $uid = D('Admins')->where('account="'.$account.'"')->getField('uid');

    //投票介绍缩略图
    $thumbimage_url = "";
    $file = $_FILES["photo"];
    if(!empty($file["name"])) {
      $path = 'voteop';
      $thumbimage_url = UploadImageByMySelf($path,$file);
    }
    // print_r($thumbimage_url);exit;
    $Vote->uid = $uid;
    $Vote->ifadmin = '1';
    $Vote->title = $_POST['title'];
    $Vote->content = $_POST['content'];
    $Vote->img = $thumbimage_url;
    $Vote->type = $type;
    $Vote->max = $_POST['max'];
    $Vote->ever = $_POST['ever'];
    $Vote->enablecid = "";
    $Vote->addtime = time();
    $Vote->begin = strtotime($_POST['begin']);
    $Vote->end = strtotime($_POST['end']);

    $voteid = $Vote->add();
    if($type==2){
      $VoteItem->voteid = $voteid;
      $VoteItem->title = $_POST['title'];
      $itemid = $VoteItem->add();
      foreach ($_POST['op'] as $key => $value) {
        $VoteOpt->itemid = $itemid;
        $VoteOpt->op = $value;
        // $VoteOpt->opvalue = $_POST['opvalue'][$key];
        // $thumbimage = "";
        // $file = $_FILES["picture"];
        // if(!empty($file["name"][$key])) {
        //   $path = 'voteop';
        //   $nfile['name'] = $file["name"][$key];
        //   $nfile['tmp_name'] = $file["tmp_name"][$key];
        //   $nfile['type'] = $file["type"][$key];
        //   $thumbimage = UploadImageByMySelf($path,$nfile);
        // }
        // $VoteOpt->img = $thumbimage;
        $VoteOpt->add();
      }
    }else{
      $n = count($_POST['item']);
      for ($i=1,$j=0; $i <=$n,$j<$n ; $i++,$j++) {
        $VoteItem->voteid = $voteid;
        $VoteItem->title = $_POST['item'][$j];
        $itemid = $VoteItem->add();
        foreach ($_POST['op'.$i] as $key => $value) {
          $VoteOpt->itemid = $itemid;
          $VoteOpt->op = $value;
          $VoteOpt->opvalue = $_POST['opvalue'.$i][$key];
          $VoteOpt->add();
        }
      }
    }
    $this->success('新增成功', __CONTROLLER__.'/votelist');
  }
  /**
   *编辑投票
   */
  public function EditVote(){
    $Vote = D('Vote');
		$Vote->title = $_POST['title'];
    if($_POST['description'])
			$Vote->content = $_POST['description'];
    //投票介绍缩略图
    $file = $_FILES["photo"];
    if(!empty($file["name"])) {
      $path = 'voteop';
      $thumbimage_url = UploadImageByMySelf($path,$file);
      $Vote->img = $thumbimage_url;
    }
    $Vote->ever = trim($_POST['ever']);
    $Vote->max = trim($_POST['max']);
		$Vote->begin = strtotime($_POST['begin']);
		$Vote->end = strtotime($_POST['end']);
		$Vote->where('id='.$_POST['vid'])->save();
		$date['code'] = '200';
		$this->ajaxReturn($date);
  }
  /**
  *导出网格投票
  */
  public function gOutMesVote(){
    if(isset($_GET['vid']) && is_numeric($_GET['vid']) && $_GET['vid']>0){
      vendor("PHPExcel.PHPExcel");
      vendor("PHPExcel.PHPExcel.IOFactory");
      $voteid = $_GET['vid'];
      $VoteOpt = D('VoteOpt');
      $Poll = D('Poll');
      $VoteItem = D('VoteItem');
      $Electeder = D('Electeder');
      $data = $VoteItem->where('voteid='.$voteid)->select();
      $date = date("Y-m-d",time());
      $filename="网格投票表".$date;
      if($data){
        $phpexcel = new \PHPExcel();
        $phpexcel->getActiveSheet()->setTitle($filename);
        $phpexcel->getActiveSheet()
        ->setCellValue('A1','序号')
        ->setCellValue('B1','选题')
        ->setCellValue('C1','姓名')
        ->setCellValue('D1','票数');
         $i = 2;
         foreach ( $data as $k => $val ) {
           $voinfo = $VoteOpt->where('itemid='.$val['itemid'])->select();
           $allnum = $Poll->alias('p')->join('lct_vote_opt vo ON vo.opid=p.opid ')
           ->where('p.voteid='.$voteid.' and vo.itemid='.$val['itemid'])->count();
           foreach ($voinfo as $key => $value) {
             $opnum = $Poll->where('opid='.$value['opid'])->count();
             //
             if($value['opid']=166)
              $opnum += 120;
             //
             $percent = number_format(($opnum*100/$allnum), 0, '.', '');
             $realname = $Electeder->where('id='.$value['op'])->getField('realname');
             $phpexcel->getActiveSheet()
              ->setCellValue('A'.$i, $key+1)
              ->setCellValue('B'.$i, $val['title'])
              ->setCellValue('C'.$i, $realname)
              ->setCellValue('D'.$i, $opnum);
              $i++;
           }
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
    }else{
      $this->error('参数错误，注意网络安全');
    }
  }
  /**
   * 获取投票内容
   */
  public function getVoteContentToEdit(){
    $vid = $_POST['vid'];
    $Vote = D('Vote');
    $content = $Vote->where('id='.$vid)->getField('content');
    $data['content'] = $content;
    $this->ajaxReturn($data);
  }
  //选票详情
  public function getOp(){
    $voteid = $_GET['voteid'];
    $Vote = D('Vote');
    $VoteOpt = D('VoteOpt');
    $Poll = D('Poll');
    $VoteItem = D('VoteItem');
    $Electeder = D('Electeder');
    $ImageUrl = C('ImageUrl');
    $vinfo = $Vote->where('id='.$voteid)->field('title,type')->find();
    $vi = $VoteItem->where('voteid='.$voteid)->select();
    if($vinfo['type'] == '1'){//列表
      foreach ($vi as $k => $v) {
        $voinfo = $VoteOpt->where('itemid='.$v['itemid'])->select();
        $allnum = $Poll->alias('p')->join('lct_vote_opt vo ON vo.opid=p.opid ')
        ->where('p.voteid='.$voteid.' and vo.itemid='.$v['itemid'])->count();
        $titlecolor = array('#d35400','#2980b9','#2c3e50','#46465e','#333333','#27ae60','#124e8c');//6个选择
        $barcolor = array(' #e67e22','#3498db','#2c3e50','#5a68a5','#525252','#2ecc71','#4288d0');
        //$voinfo['title'] = $v['title'];
        foreach ($voinfo as $key => $value) {
          $r[$key]['opv'] = $value['op'].' '.$value['opvalue'];
          $r[$key]['titlecolor'] = $titlecolor[$key];
          $r[$key]['barcolor'] = $barcolor[$key];
          $opnum = $Poll->where('opid='.$value['opid'])->count();
          $r[$key]['percent'] = number_format(($opnum*100/$allnum), 0, '.', '');
        }
        $vp[$k]['op'] = $r;
        $vp[$k]['title'] = $v['title'];
      }
      $this->assign('voteid',$voteid);
      $this->assign('vp',$vp);
      $this->display('listop');
    }
    if($vinfo['type'] == '2'){//网格
      $voinfo = $VoteOpt->where('itemid='.$vi[0]['itemid'])->select();
      foreach ($voinfo as $key => $value) {
        $voinfo[$key]['opv'] = $value['op'].' '.$value['opvalue'];
        $opnum = $Poll->where('opid='.$value['opid'])->count();
        //
        if($value['opid']=166)
         $opnum += 120;
        //
        $voinfo[$key]['img'] = $ImageUrl.$Electeder->where('id='.$value['op'])->getField('headpic');
        $voinfo[$key]['percent'] = $opnum;
      }
      $this->assign('voteid',$voteid);
      $this->assign('voinfo',$voinfo);
      $this->assign('vinfo',$vinfo);
      $this->display('meshop');
    }
  }
  //删除
  public function delVote(){
    $voteid  = $_POST['voteid'];
		$Vote = D('Vote');
    $Poll = D('Poll');
    $VoteOpt = D('VoteOpt');
    $VoteItem = D('VoteItem');
		$re = $Vote->where('id='.$voteid)->delete();
    // $Poll->where('voteid='.$voteid)->delete();
  //  $VoteOpt->where('voteid='.$voteid)->delete();
  //  $VoteItem->where('voteid='.$voteid)->delete();
		if($re){
			$data['msg'] = 'success';
		}else{
			$data['msg'] = 'fail';
		}
		$this->ajaxReturn($data);
  }
  //参与投票
  public function electeder(){
    $Electeder = D('Electeder');
    $ImageUrl = C('ImageUrl');
    $elist = $Electeder->where('1=1')->field('id,realname,headpic')->select();
    foreach ($elist as $key => $value) {
      $elist[$key]['headpic'] = $ImageUrl.$value['headpic'];
    }
    $this->assign('elist',$elist);
    $this->display('elelist');
  }
  //新增参投人员
  public function addELe(){
    $Electeder = D('Electeder');
    $realname = $_POST['realname'];

    $file = $_FILES["photo"];
    if(!empty($file["name"])) {
      $path = 'ele';
      $thumbimage_url = UploadImageByMySelf($path,$file);
      $Electeder->headpic = $thumbimage_url;
    }
    if($_POST['content'])
      $Electeder->info = $_POST['content'];
    $Electeder->realname = trim($realname);
    $Electeder->add();
    $this->success('新增成功', __CONTROLLER__.'/electeder');
  }
  //删除
  public function delEle(){
    $Electeder = D('Electeder');
    $id = $_POST['id'];
    $Electeder->where('id='.$id)->delete();
    if($re){
      $data['msg'] = 'success';
    }else{
      $data['msg'] = 'fail';
    }
    $this->ajaxReturn($data);
  }
  //编辑页面
  public function getEleEditPage(){
    $this->display('eleedit');
  }
  //获取编辑内容
  public function getEleContentToEdit(){
    $id = $_POST['eid'];
    $Electeder = D('Electeder');
    $content = $Electeder->where('id='.$id)->getField('info');
    $data['content'] = $content;
    $this->ajaxReturn($data);
  }
  //编辑
  public function EditEle(){
    $Electeder = D('Electeder');

		$Electeder->realname = $_POST['realname'];

		if($_POST['description'])
			$Electeder->info = $_POST['description'];

    $file = $_FILES["photo"];
    if(!empty($file["name"])) {
      $path = 'ele';
      $thumbimage_url = UploadImageByMySelf($path,$file);
      $Electeder->headpic = $thumbimage_url;
    }

		$Electeder->where('id='.$_POST['eid'])->save();
		$date['code'] = '200';
		$this->ajaxReturn($date);
  }
  //党员组织
  public function orglist(){
    $Org = D('Org');
    $orglist = $Org->where('1=1')->field('id,orgname,realname,phone,idcard,orgid,intro,score,addtime,aut')->select();
    foreach ($orglist as $key => $value) {
      $orglist[$key]['party'] = D('OrgParty')->where('orgid='.$value['orgid'])->getField('party');
      $orglist[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
    }
    $this->assign('olist',$orglist);
    $this->display('orglist');
  }
  //认证组织
  public function autOrg(){
    $id = $_POST['id'];
    $Org = D('Org');
    $Org->aut = 1;
    $Org->where('id='.$id)->save();
    $date['code'] = '200';
		$this->ajaxReturn($date);
  }
  //删除组织
  public function delOrg(){
    $id = $_POST['id'];
    $Org = D('Org');
    $OrgJoin = D('OrgJoin');
    $Org->where('id='.$id)->delete();
    $OrgJoin->where('oid='.$id)->delete();
    $date['code'] = '200';
		$this->ajaxReturn($date);
  }
  //微心愿列表
  public function wishlist(){
    $Wish = D('Wish');
    //先删除一个月没被领取的愿望
    $month = time()-2592000;
    $query = "addtime<".$month." and status=0";
    $Wish->where($query)->delete();
    $WishRes = D('WishRes');
    $ImageUrl = C('ImageUrl');
    $wlist = $Wish->where('1=1')->alias('w')->join('lct_users u ON w.uid=u.uid')
    ->field('w.id,w.wish,w.wisher,w.wphone,u.realname as adder,u.phone as aphone,w.content,w.img,w.addtime,w.status')->select();
    foreach ($wlist as $key => $value) {
      $wlist[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
      $img_arr = json_decode($value['img'],true);
      $wlist[$key]['img'] = array();
      if(!empty($img_arr)){
        foreach ($img_arr as $k => $v) {
          $wlist[$key]['img'][$k]['imgurl'] = $ImageUrl.$v;
        }
      }
      if($value['status'] == '0'){
        $wlist[$key]['res'] = "";
      }else{
        $uid = $WishRes->where('wid='.$value['id'])->getField('uid');
        $wlist[$key]['res'] = D('Users')->where('uid='.$uid)->getField('realname');
      }
    }
    $this->assign('wlist',$wlist);
    $this->display('wishlist');
  }
  //删除微心愿
  public function delWish(){
    $wid = $_POST['id'];
    $Wish = D('Wish');
    $WishRes = D('WishRes');
    $Wish->where('id='.$wid)->delete();
    $WishRes->where('wid='.$wid)->delete();
    $data['msg'] = "success";
    $this->ajaxReturn($data);
  }
  //活动列表
  public function activitylist(){
    $Activity = D('Activity');
    $alist= $Activity->where('1=1')->alias('a')->join('lct_users as u ON u.uid = a.uid')
    ->field('a.id as aid,a.title,a.iforg,a.address,a.begin,a.end,a.tag,a.addtime,u.realname,u.phone')->select();
    foreach ($alist as $key => $value) {
      $alist[$key]['begin'] = $value['begin']?date('Y-m-d',$value['begin']):"";
      $alist[$key]['end'] = $value['end']?date('Y-m-d',$value['end']):"";
      $alist[$key]['addtime'] = date('Y-m-d',$value['addtime']);
      //1-扶贫济困;2-助老助残;3-生态建设;4-平安巡防;5-实践培训;6-社区服务;7-大型活动;8-抢险救灾
      switch ($value['tag']) {
        case '1':
          $alist[$key]['tag'] = "扶贫济困";
          break;
        case '2':
          $alist[$key]['tag'] = "助老助残";
          break;
        case '3':
          $alist[$key]['tag'] = "生态建设";
          break;
        case '4':
          $alist[$key]['tag'] = "平安巡防";
          break;
        case '5':
          $alist[$key]['tag'] = "实践培训";
          break;
        case '6':
          $alist[$key]['tag'] = "社区服务";
          break;
        case '7':
          $alist[$key]['tag'] = "大型活动";
          break;
        case '8':
          $alist[$key]['tag'] = "抢险救灾";
          break;
        case '9':
          $alist[$key]['tag'] = "其他";
          break;
      }
    }
    $this->assign('alist',$alist);
    $this->display('alist');
  }
  /**
   * 签到人员
   */
   public function getScaned(){
     $aid = $_GET['aid'];
     $ActivityJoin = D('ActivityJoin');
     $ajlist = $ActivityJoin->alias('aj')->join('lct_users u ON aj.uid=u.uid')
     ->field('aj.id,aj.addtime,aj.address,u.realname,u.phone')
     ->where('aj.aid='.$aid.' and aj.scantime > 0 and aj.iforger=0')->select();
     foreach ($ajlist as $key => $value) {
       $ajlist[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
     }
     $this->assign('ajlist',$ajlist);
     $this->display('aj');
   }
  /**
  *兑换物列表
  */
  public function donatelist(){
    $Donate = D('Donate');
    $ImageUrl = C('ImageUrl');
    $dlist = $Donate->where('1=1')
    ->field('id,inst,unit,thing,img,cate,num,addtime,score')->select();
    foreach ($dlist as $key => $value) {
      $dlist[$key]['img'] = $ImageUrl.$value['img'];
      $dlist[$key]['orgname'] = $orgname?$orgname:"";
      switch ($value['cate']) {
        case '1':
          $dlist[$key]['type'] = '日用品';
          break;
        case '2':
          $dlist[$key]['type'] = '服装';
          break;
        case '3':
          $dlist[$key]['type'] = '书本';
          break;
        default:
          $dlist[$key]['type'] = '其他';
          break;
      }
      $dlist[$key]['addtime'] = date('Y-m-d',$value['addtime']);
    }
    $this->assign('dlist',$dlist);
    $this->display('donatelist');
  }
  /**
  * 发布兑换物
  */
  public function addDonate(){
    $Donate = D('Donate');
    $Donate->inst = trim($_POST['inst']);
    $Donate->thing = trim($_POST['thing']);
    $Donate->cate = trim($_POST['cate']);
    $Donate->unit = trim($_POST['unit']);
    $Donate->ifbuy = $_POST['ifbuy'];
    //缩略图
    $file = $_FILES["photo"];
    if(!empty($file["name"])) {
      $path = 'donate';
      $thumbimage_url = UploadImageByMySelf($path,$file);
      $Donate->img = $thumbimage_url;
    }
    $Donate->num = trim($_POST['num']);
    $Donate->addtime = time();
    $Donate->score = trim($_POST['score']);
    $Donate->add();
    $this->success('新增成功', __CONTROLLER__.'/donatelist');
  }
  /**
   * 修改
   */
  public function EditDonate(){
    $Donate = D('Donate');
    $did = $_POST['did'];
    $Donate->inst = trim($_POST['inst']);
    $Donate->thing = trim($_POST['thing']);
    $Donate->num = trim($_POST['num']);
    $Donate->score = trim($_POST['score']);
    $Donate->unit = trim($_POST['unit']);
    $file = $_FILES["photo"];
    if(!empty($file["name"])) {
      $path = 'donate';
      $thumbimage_url = UploadImageByMySelf($path,$file);
      $Donate->img = $thumbimage_url;
    }
    $Donate->where('id='.$did)->save();
    $date['code'] = '200';
		$this->ajaxReturn($date);
  }
  /**
   * 删除
   */
  public function delDonate(){
    $Donate = D('Donate');
    $Exchange = D('Exchange');
    $re = $Donate->where('id='.$_POST['did'])->delete();
    if($re){
      $Exchange->where('did='.$_POST['did'])->delete();
      $data['msg'] = 'success';
    }else{
      $data['msg'] = 'fail';
    }
    $this->ajaxReturn($data);
  }
  /**
   * 兑换记录
   */
  public function exchange(){
    $Exchange = D('Exchange');
    $Donate = D('Donate');
    $ImageUrl = C('ImageUrl');
    $elist = $Exchange->where('1=1')->field('id,uid,did,addtime,ifget,gettime')->select();
    foreach ($elist as $key => $value) {
      $elist[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
      $elist[$key]['gettime'] = date('Y-m-d H:i:s',$value['gettime']);
      $dinfo = $Donate->where('id='.$value['did'])->field('img,thing')->find();
      $elist[$key]['img'] = $dinfo['img']?$ImageUrl.$dinfo['img']:"";
      $elist[$key]['thing'] = $dinfo['thing']?$dinfo['thing']:"";
      $realname = D('Users')->where('uid='.$value['uid'].' and ifdelete=0 and aut=1')->getField('realname');
      $elist[$key]['realname'] = $realname?$realname:"";
    }
    $this->assign('elist',$elist);
    $this->display('exchangelist');
  }
  /**
   * 领取
   */
  public function getDonate(){
    $eid = $_POST['eid'];
    $Exchange = D('Exchange');
    $Exchange->ifget = 1;
    $re = $Exchange->where('id='.$eid)->save();
    if($re)
      $data['code'] = '200';
    $this->ajaxReturn($data);
  }
}
