<?php
namespace Api\Controller;
use Think\Controller;
class DutyController extends Controller {
  //投票列表
  public function votelist(){
    $uid = I('uid');
    $status = I('status');
    $iPageItem = I('iPageItem');
    $iPageIndex = I('iPageIndex');
    $Vote = D('Vote');
    $Poll = D('Poll');
    $ImageUrl = C('ImageUrl');
    $res = array();
    $nowtime = time();
    switch ($status) {
      case '0'://正在进行
        $query['begin'] = array('LT',$nowtime);
        $query['end'] = array('GT',$nowtime);
        break;
      case '1'://已结束
        $query['end'] = array('LT',$nowtime);
        break;
      default://我参与的
        $up = $Poll->where('uid='.$uid)->field('voteid')->select();
        if(!empty($up)){
          $r = array_column($up,'voteid');
          $query['id'] = array('IN',$r);
        }else{
          $query = '1=0';
        }
        break;
    }
    $vlist = $Vote->where($query)->page($iPageIndex+1,$iPageItem)->field('id,title,begin,end,img')->order('addtime desc')->select();
    foreach ($vlist as $key => $value) {
      $res[$key]['vid'] = $value['id'];
      $res[$key]['title'] = $value['title'];
      $res[$key]['begin'] = date('Y-m-d',$value['begin']);
      $res[$key]['end'] = date('Y-m-d',$value['end']);
      $res[$key]['img'] = $ImageUrl.$value['img'];
    }
    if(!empty($res)){
      ApiResult('200',$res,'');
    }else{
      ApiResult('201',$res,'还没有哦~');
    }
  }
  //投票详情
  public function vote(){

    $uid = I('uid');
    $vid = I('vid');
    $iPageItem = I('iPageItem');
    $iPageIndex = I('iPageIndex');
    $Vote = D('Vote');
    $VoteItem = D('VoteItem');
    $VoteOpt = D('VoteOpt');
    $Poll = D('Poll');
    $Electeder = D('Electeder');
    $ImageUrl = C('ImageUrl');
    $res = array();
    $vinfo = $Vote->where('id='.$vid)->field('id,title,content,type,max,ever,begin,end')->find();
    if(empty($vinfo)){
      ApiResult('201',array(),'该投票已被删除');
    }
    $res['vid'] = $vinfo['id'];
    $res['title'] = $vinfo['title'];
    $res['content'] = $vinfo['content'];
    $res['max'] = $vinfo['max'];
    $res['begin'] = date('Y-m-d',$vinfo['begin']);
    $res['end'] = date('Y-m-d',$vinfo['end']);
    $res['hasPoll'] = '0';
    if($uid){

      $query['voteid'] = $vid;
      $query['uid'] = $uid;
      if($vinfo['ever']){//是否每天可投票
        $btoday = strtotime(date('Y-m-d'));//今天凌晨
        $etoday = $btoday+86400;
        $q[] = $btoday;
        $q[] = $etoday;
        $query['addtime'] = array('BETWEEN',$q);
        $Poll->where('addtime < '.$btoday)->delete();
      }
      $opnum = $Poll->where($query)->count();
      if($opnum >= $vinfo['max']){
        $res['hasPoll'] = '1';
      }

    }
    if(!$iPageItem){
      $iPageItem = 70;
    }
    $Item = $VoteItem->where('voteid='.$vid)->field('title,itemid')->limit(1)->select();
    foreach ($Item as $key => $value) {
      $res['item'][$key]['title'] = $value['title'];
      // $OptInfo = $VoteOpt->where('itemid='.$value['itemid'])->field('op,opid')->page($iPageIndex+1,$iPageItem)->select();
      $OptInfo = $VoteOpt->alias('vo')->join('lct_electeder e ON e.id=vo.op')
      ->where('vo.itemid='.$value['itemid'])->field('vo.op,vo.opid,vo.poll,e.realname,e.headpic')->page($iPageIndex+1,$iPageItem)->select();
      foreach ($OptInfo as $k => $v) {
        // $eleinfo = $Electeder->where('id='.$v['op'])->field('realname,headpic')->find();
        $res['item'][$key]['op'][$k]['opname'] = $v['op'];
        $res['item'][$key]['op'][$k]['opid'] = $v['opid'];
        $res['item'][$key]['op'][$k]['name'] = $v['realname'];
        $res['item'][$key]['op'][$k]['ifchoose'] = '0';
        if($uid){
          $map['opid'] = $v['opid'];
          $map['uid'] = $uid;
          if($vinfo['ever']){//是否每天可投票
            $btoday = strtotime(date('Y-m-d'));//今天凌晨
            $etoday = $btoday+86400;
            $q[] = $btoday;
            $q[] = $etoday;
            $map['addtime'] = array('BETWEEN',$q);
          }
          $P = $Poll->where($map)->count();
          if($P>0)
            $res['item'][$key]['op'][$k]['ifchoose'] = '1';//已投
        }
        $res['item'][$key]['op'][$k]['image'] = $ImageUrl.$v['headpic'];
        $res['item'][$key]['op'][$k]['percent'] = $v['poll'];
      }
    }
    ApiResult('200',$res,'');
  }

  /**
  *投票
  */
  public function dovote(){
    $vid = I('vid');
    $uid = I('uid');
    $opid = I('opid');
    $chArr = explode('|',rtrim($opid,'|'));

    $Vote = D('Vote');
    $Poll = D('Poll');
    $VoteOpt = D('VoteOpt');
    $vinfo = $Vote->where('id='.$vid)->field('max,ever,type,end')->find();
    $endtime = $vinfo['end'] + 86400;
    if(time()>$endtime)
      ApiResult('203','','投票活动已经结束啦');
    $max = $vinfo['max'];
    $query['voteid'] = $vid;
    $query['uid'] = $uid;
    if($vinfo['ever']){//每天都能投的
      $btoday = strtotime(date('Y-m-d'));//今天凌晨
      $etoday = $btoday+86400;
      $q[] = $btoday;
      $q[] = $etoday;
      $query['addtime'] = array('BETWEEN',$q);
    }
    // ApiResult('200',$query,'');
    $opnum = $Poll->where($query)->count();
    if($opnum == $max && $vinfo['type']=='2')
      ApiResult('202','','你超过投票次数啦');
    if($max < count($chArr))
      ApiResult('204','','最多只能投'.$max.'个');
    //记录
    foreach ($chArr as $key => $value) {
      $Poll->voteid = $vid;
      $Poll->opid = $value;
      $Poll->uid = $uid;
      $Poll->addtime = time();
      $re = $Poll->add();
      $VoteOpt->where('opid='.$value)->setInc('poll',1);
    }
    if($re){
      ApiResult('200','','投票成功');
    }else{
      ApiResult('201','','投票失败');
    }
  }

  //参投人详情
  public function electeder(){
    $opname = I('opname');
    $Electeder = D('Electeder');
    $ImageUrl = C('ImageUrl');
    $einfo = $Electeder->where('id='.$opname)->field('realname,headpic,info')->find();
    if(empty($einfo)){
      ApiResult('201',array(),'该参投人已被删除');
    }
    $einfo['headpic'] = $ImageUrl.$einfo['headpic'];
    ApiResult('200',$einfo,'');
  }

  //组织注册
  public function orgreg(){
    $orgname = trim(I('orgname'));
    $realname = trim(I('realname'));
    $img = trim(I('img'));
    $phone = I('phone');
    $idcard = I('idcard');
    $orgid = I('orgid');
    $intro = I('intro');

    $Org = D('Org');
    $Users = D('Users');
    $iforgnameexist = $Org->where("orgname='".$orgname."'")->count();
    if($iforgnameexist)
      ApiResult("201",array(),"组织名已存在");
    $ifuserexist = $Users->where("aut=1 and ifdelete=0 and account='".$phone."'")->count();
    if(!$ifuserexist)
      ApiResult("203",array(),"该账号没注册");
    $ifreg = $Org->where("phone='".$phone."'")->count();
    if($ifreg)
      ApiResult("204",array(),"已经创建了一个组织");
    if(!$img){
      $img_url = "/Upload/images/def/org.png";
    }else{
      $img_url = $img;
    }
    $Org->orgname = $orgname;
    $Org->img = $img_url;
    $Org->realname = $realname;
    $Org->phone = $phone;
    $Org->idcard = $idcard;
    $Org->orgid = $orgid;
    $Org->intro = $intro;
    $Org->addtime = time();
    $oid = $Org->add();
    if($oid){
      $OrgJoin = D('OrgJoin');
      $OrgJoin->oid = $oid;
      $OrgJoin->phone = $phone;
      $OrgJoin->addtime = time();
      $OrgJoin->ifinit = 1;
      $OrgJoin->add();
      ApiResult("200",array(),"注册成功");
    }else{
      ApiResult("202",array(),"注册失败");
    }
  }
  //组织列表
  public function orglist(){
    $iPageItem = I('iPageItem');
    $iPageIndex = I('iPageIndex');
    $Org = D('Org');
    $OrgParty = D('OrgParty');
    $ImageUrl = C('ImageUrl');
    $orginfo = $Org->where('aut=1')->page($iPageIndex+1,$iPageItem)->field('id,orgname,img,phone,score,orgid')->order('addtime desc')->select();
    if(empty($orginfo)){
      ApiResult('201',array(),'没有组织');
    }
    foreach ($orginfo as $key => $value) {
      $OrgJoin = D('OrgJoin');
      $res[$key]['oid'] = $value['id'];
      $res[$key]['orgname'] = $value['orgname'];
      $res[$key]['point'] = $value['score'];
      $res[$key]['num'] = $OrgJoin->where('oid='.$value['id'])->count();
      $res[$key]['img'] = $ImageUrl.$value['img'];
      $res[$key]['party'] = $OrgParty->where('orgid='.$value['orgid'])->getField('party');
    }
    ApiResult("200",$res,"");
  }
  //组织详情
  public function org(){
    $oid = I('oid');
    $uid = I('uid');
    $Org = D('Org');
    $OrgParty = D('OrgParty');
    $OrgJoin = D('OrgJoin');
    $ImageUrl = C('ImageUrl');
    $oinfo = $Org->query('SELECT * FROM (
      SELECT o.id,o.orgname,o.img,o.realname,o.phone,o.score,o.intro,o.orgid,(@rowno:=@rowno+1) as rowno
      FROM lct_org o,(select (@rowno:=0)) o WHERE o.aut=1 ORDER BY o.score DESC) o
      WHERE o.id='.$oid);
    if(empty($oinfo))
      ApiResult('201','','该组织已被删除或者未审核通过');
    $oinfo = $oinfo[0];
    $res = array();
    $res['oid'] = $oinfo['id'];
    $res['img'] = $ImageUrl.$oinfo['img'];
    $res['orgname'] = $oinfo['orgname'];
    $res['intro'] = $oinfo['intro'];
    $res['party'] = $oinfo['orgid']?$OrgParty->where('orgid='.$oinfo['orgid'])->getField('party'):"";
    $res['num'] = $OrgJoin->where('oid='.$oinfo['id'])->count();
    $res['score'] = $oinfo['score'];
    $res['rowno'] = $oinfo['rowno'];
    $res['realname'] = $oinfo['realname'];
    $res['phone'] = $oinfo['phone'];
    $res['ifjoin'] = '0';
    $res['ifinit'] = '0';
    if($uid){
      $phone = D('Users')->where('uid='.$uid)->getField('phone');
      $ifexist = $OrgJoin->where('oid = '.$oid.' and phone="'.$phone.'"')->count();
      if($ifexist>0){
        $res['ifjoin'] = '1';
        $ifinit = $OrgJoin->where('oid = '.$oid.' and phone="'.$phone.'"')->getField('ifinit');
        $res['ifinit'] = $ifinit;
      }
    }
    ApiResult('200',$res,'');
  }
  //加入组织
  public function orgjoin(){
    $oid = I('oid');
    $uid = I('uid');

    $Users = D('Users');
    $Org = D('Org');
    $OrgJoin = D('OrgJoin');
    $phone = $Users->where('uid='.$uid)->getField('account');

    /*$ifreg = $Org->where("phone='".$phone."'")->field('aut')->find();
    if(!empty($ifreg)){
      if($ifreg['aut']){
        ApiResult('201',array(),'已经创建组织');
      }else{
        ApiResult('202',array(),'你已经有一个组织创建，审核中，是否删除原来的组织');
      }
    }*/
    $ifjoin = $OrgJoin->where("oid=".$oid." and phone='".$phone."'")->count();
    if($ifjoin)
      ApiResult('203',array(),'已经加入该组织');
    $OrgJoin->oid = $oid;
    $OrgJoin->phone = $phone;
    $OrgJoin->addtime = time();
    $res = $OrgJoin->add();
    if($res){
      ApiResult("200",array(),"加入成功");
    }else{
      ApiResult("204",array(),"加入失败");
    }
  }
  /**
   * 组织成员
   */
   public function orgmember(){
     $oid = I('oid');
     $OrgJoin = D('OrgJoin');
     $ImageUrl = C('ImageUrl');
     $mArr = $OrgJoin->alias('oj')->join('lct_users u ON u.phone=oj.phone')
     ->field('oj.ifinit,oj.addtime,oj.phone,u.realname,u.headpic')->order('addtime asc')
     ->where('oj.oid='.$oid)->select();
     $res = array();
     foreach ($mArr as $key => $value) {
       $res[$key]['realname'] = $value['realname'];
       $res[$key]['headpic'] = $ImageUrl.$value['headpic'];
       $res[$key]['phone'] = $value['phone'];
       $res[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
       $res[$key]['ifinit'] = $value['ifinit'];
     }
     ApiResult("200",$res,"");
   }
   /**
    * 删除组织成员
    */
    public function orgout(){
      $oid = I('oid');
      $uid = I('uid');
      $phone = I('phone');
      $Users = D('Users');
      $OrgJoin = D('OrgJoin');
      //当前用户手机号，判断是否是组织建立者。
      $uphone = $Users->where('uid='.$uid)->getField('phone');
      if($uphone == $phone){
        ApiResult("201","","不能删自己");
      }
      $ifinit = $OrgJoin->where('oid='.$oid.' and phone="'.$uphone.'"')->getField('ifinit');
      if(!$ifinit){
        ApiResult("202","","你不是该组织创建者，不能删除成员");
      }else{
        $res = $OrgJoin->where('oid='.$oid.' and phone="'.$phone.'"')->delete();
        if($res>0){
          ApiResult("200","","删除成功");
        }else{
          ApiResult("203","","该成员已被删除");
        }
      }
    }
  //删除组织
  public function orgdel(){
    $phone = trim(I('phone'));
    $Org = D('Org');
    $OrgJoin = D('OrgJoin');
    $orginfo = $Org->where("phone='".$phone."'")->field('id,aut')->find();
    if(empty($orginfo))
      ApiResult("202",array(),"没有组织");
    if($orginfo['aut'])
      ApiResult("201",array(),"未认证的才能删除");
    $id = $orginfo['id'];
    $Org->where('id='.$id)->delete();
    $OrgJoin->where('oid='.$id)->delete();
    ApiResult("200",array(),"删除成功");
  }

  //发布心愿
  public function addwish(){
    $Score = D('Score');
    $uid = I('uid');
    $award = I('award');
    $res = $Score->where('uid='.$uid)->getField('sum')-$award;
    if($res<0){//判断奖励积分是否超过
      ApiResult('202',$res,'超过');
    }
    $Wish = D('Wish');
    $wish = I('wish');
    $tag = I('tag');
    $wisher = I('wisher');
    $wphone = I('wphone');
    $content = I('content');
    $imgurl_json = I('imgurl_json');
    $Wish->wish = $wish;
    $Wish->tag = $tag;
    $Wish->wisher = $wisher;
    $Wish->wphone = $wphone;
    $Wish->uid = $uid;
    $Wish->content = $content?$content:"";
    $Wish->award = $award?$award:0;
    if($imgurl_json){
      $img = str_replace('&quot;','"',$imgurl_json);//格式化&quot;->"
    }else{
      $img = '["/Upload/images/def/wish.png"]';
    }
    $Wish->img = $img;
    $Wish->addtime = time();
    $wid = $Wish->add();
    if($wid){
      $Score->where('uid='.$uid)->setDec('sum',$award);
      ApiResult("200",array(),"发布成功");
    }else{
      ApiResult("201",array(),"发布失败");
    }
  }
  /**
   *心愿列表
   *1-全部;2-我领取的;3-我发布的
   */
  public function wishlist(){
    $uid = I('uid');
    $option = I('option');
    $iPageItem = I('iPageItem');
    $iPageIndex = I('iPageIndex');
    $Wish = D('Wish');
    $Users = D('Users');
    $ImageUrl = C('ImageUrl');
    if($option == '2'){
      $query = ' ws.uid='.$uid;
      $wlist = $Wish->alias('w')->join('lct_wish_res ws ON w.id=ws.wid')
      ->where($query)->page($iPageIndex+1,$iPageItem)
      ->field('w.id,w.wisher,w.wish,w.uid,w.addtime,w.status,w.img,w.award')->order('addtime desc')->select();
    }else {
      $query = ' 1=1 ';
      if($option == '3'){
        $query .= ' and uid='.$uid;
      }
      $wlist = $Wish->where($query)->page($iPageIndex+1,$iPageItem)
      ->field('id,wisher,wish,uid,addtime,status,img,award')->order('addtime desc')->select();
    }

    if(empty($wlist))
      ApiResult("201",array(),"没有心愿");
    $res = array();
    foreach ($wlist as $key => $value) {
      $res[$key]['wid'] = $value['id'];
      $res[$key]['wish'] = $value['wish'];
      $res[$key]['wisher'] = $value['wisher'];
      $res[$key]['adder'] = $Users->where('uid='.$value['uid'])->getField('realname');
      $res[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
      $res[$key]['status'] = $value['status'];
      $res[$key]['img'] = "";
      $img_arr = json_decode($value['img'],true);
      if(!empty($img_arr)){
          $res[$key]['img'] = $ImageUrl.$img_arr[0];
      }
      $res[$key]['award'] = $value['award'];
    }
    ApiResult("200",$res,"");
  }
  //心愿详情
  public function wish(){
    $Wish = D('Wish');
    $WishRes = D('WishRes');
    $wid = I('wid');
    $uid = I('uid');
    $ImageUrl = C('ImageUrl');
    $winfo = $Wish->where('id='.$wid)->alias('w')->join('lct_users u ON w.uid=u.uid')
    ->field('w.id,w.wish,w.tag,w.wisher,w.uid,w.wphone,u.realname,u.phone,w.content,w.addtime,w.status,w.img,w.award')->find();
    if(empty($winfo))
      ApiResult("201",array(),"该心愿不存在了");
    $res = array();
    if(!$uid){//游客，就不能操作
      $res['can'] = '0';
    }else{
      $res['can'] = '0';
      //未领取
      if($winfo['status'] == '0'){
        $ifvo = D('Users')->where('uid='.$uid)->getField('volunt');
        if($ifvo=='1'){
          $res['can'] = '1';
        }
      }
      //已领取
      if($winfo['status'] == '1'){
        $ifuid = $WishRes->where('wid='.$wid)->getField('uid');
        if($ifuid==$uid){
          $res['can'] = '1';
        }
      }
      if($winfo['status'] == '2'){
        if($winfo['uid']==$uid){
          $res['can'] = '1';
        }
      }
    }
    $res['wid'] = $winfo['id'];
    $res['wish'] = $winfo['wish'];
    $res['tag'] = $winfo['tag'];
    $res['wisher'] = $winfo['wisher'];
    $res['wphone'] = $winfo['wphone'];
    $res['adder'] = $winfo['realname'];
    $res['aphone'] = $winfo['phone'];
    $res['content'] = $winfo['content'];
    $res['addtime'] = date('Y-m-d H:i:s',$winfo['addtime']);
    $res['status'] = $winfo['status'];
    $res['award'] = $winfo['award'];
    $res['img'] = array();
    $img_arr = json_decode($winfo['img'],true);
    if(!empty($img_arr)){
      foreach ($img_arr as $key => $value) {
        $res['img'][$key]['imgurl'] = $ImageUrl.$value;
      }
    }
    if($winfo['status'] != '0'){
      $wrinfo = $WishRes->alias('wr')->join('lct_users u ON u.uid=wr.uid')->where('wr.wid='.$winfo['id'])
      ->field('wr.addtime,wr.evaluate,u.realname,u.phone')->find();
      $res['rename'] = $wrinfo['realname'];
      $res['rephone'] = $wrinfo['phone'];
      $res['readdtime'] = date('Y-m-d H:i:s',$wrinfo['addtime']);
      $res['evaluate'] = $wrinfo['evaluate']?$wrinfo['evaluate']:"";
    }else{
      $res['rename'] = "";
      $res['rephone'] = "";
      $res['readdtime'] = "";
      $res['evaluate'] = "";
    }
    ApiResult("200",$res,"");
  }
  //领取心愿
  public function catchwish(){
    $wid = I('wid');
    $uid = I('uid');
    $Wish = D('Wish');
    $winfo = $Wish->where('id='.$wid)->field('status,uid,award')->find();
    if(empty($winfo))
      ApiResult("203","","心愿已被删除");
    if($winfo['uid'] == $uid)
      ApiResult("204","","不能领取自己发布的心愿");
    if($winfo['status'])
      ApiResult("201","","心愿已被领取");
    $WishRes = D('WishRes');
    $WishRes->wid = $wid;
    $WishRes->uid = $uid;
    $WishRes->addtime = time();
    $res = $WishRes->add();
    if($res){
      $Wish->where('id='.$wid)->setField('status',1);//1
      addScore($uid,$winfo['award']);//
      ApiResult('200','','领取成功');
    }else{
      ApiResult('202','','领取失败');
    }
  }
  /**
   * 完成心愿->待评价
   */
   public function finishwish(){
     $wid = I('wid');
     $uid = I('uid');
     $Wish = D('Wish');
     $winfo = $Wish->alias('w')->join('lct_wish_res ws ON w.id=ws.wid')
     ->where('w.id='.$wid.' and ws.uid='.$uid)
     ->field('w.status')->find();
     if(empty($winfo))
      ApiResult('201','','这个心愿不是你领取的');
     if($winfo['status'] != '1')
      ApiResult('202','','状态有误');
     $Wish->where('id='.$wid)->setField('status',2);
     ApiResult('200','','已完成，待评价');
   }
   /**
    * 评价心愿->完成
    */
  public function evawish(){
    $wid = I('wid');
    $content = I('content');
    $Wish = D('Wish');
    $winfo = $Wish->where('id='.$wid)->field('status,award')->find();
    if($winfo['status'] != '2')
      ApiResult('201','','状态有误');
    $WishRes = D('WishRes');
    $WishRes->evaluate = $content;
    $WishRes->where('wid='.$wid)->save();
    $Wish->where('id='.$wid)->setField('status',3);
    $uid = $WishRes->where('wid='.$wid)->getField('uid');
    //增加积分
    addScore($uid,$winfo['award']);
    ApiResult('200','','已完成');
  }
  /**
  *活动列表
  */
  public function activitylist(){
    $iPageItem = I('iPageItem');
    $iPageIndex = I('iPageIndex');
    $type = I('type');
    $uid = I('uid');
    $Activity = D('Activity');
    $ActivityJoin = D('ActivityJoin');
    $ImageUrl = C('ImageUrl');
    if($type == '1'){//正在进行
      $query = time().'<end';
    }else if($type == '2'){//已经结束
      $query = time().'>end';
    }else if($type == '3' || $type == '4'){//我参与的||我发布的
      if($type == '3'){
        $iforger = '0';
      }else{
        $iforger = '1';
      }
      $aidArr = $ActivityJoin->where('uid='.$uid.' and iforger='.$iforger)->field('aid')->select();
      if(empty($aidArr)){
        $query = '1=0';
      }else{
        $aidArr = array_column($aidArr,'aid');
        $query['id'] = array('IN',$aidArr);
      }
    }else{
      $query = '1=1';
    }
    $alist = $Activity->where($query)->page($iPageIndex+1,$iPageItem)->field('id,title,img,num,end')->order('addtime desc')->select();
    if(empty($alist))
      ApiResult("201",array(),"没有活动");
    $res = array();
    foreach ($alist as $key => $value) {
      $res[$key]['aid'] = $value['id'];
      $res[$key]['title'] = str_replace("&quot;","'",$value['title']);
      $res[$key]['img'] = $ImageUrl.$value['img'];
      $res[$key]['joinnum'] = $ActivityJoin->where('aid='.$value['id'])->count();
      $res[$key]['num'] = $value['num'];
      if(time()<$value['end']){
        $res[$key]['status'] = "正在进行";
      }else if(time()>$value['end']){
        $res[$key]['status'] = "已结束";
      }
    }
    ApiResult("200",$res,"");
  }
  /**
  *发布活动
  */
  public function addactivity(){
    $uid = I('uid');
    $iforg = I('iforg');
    $title = I('title');
    $img = I('img');
    $address = I('address');
    $begin = I('begin');
    $end = I('end');
    $intro = I('intro');
    $tag = I('tag');
    $num = I('num');

    if($iforg == '1'){
      $phone = D('Users')->where('uid='.$uid.' and aut=1 and ifdelete=0')->getField('phone');
      $ifallow = D('Org')->where("aut=1 and phone='".$phone."'")->find();
      if(empty($ifallow)){
        ApiResult("202","","你没有组织或者组织未审核通过");
      }
      addScore($uid,68,'1');
    }
    if($img){
      $img_url = str_replace('&quot;','',$img);//格式化&quot;->";
    }else{
      $img_url = '/Upload/images/def/activity.png';
    }
    $Activity = D('Activity');
    $Activity->uid = $uid;
    $Activity->iforg = $iforg;
    $Activity->title = $title;
    $Activity->img = $img_url;
    $Activity->address = $address;
    $Activity->begin = $begin?strtotime($begin):"";
    $Activity->end = $end?strtotime($end):"";
    $Activity->intro = $intro;
    $Activity->tag = $tag;
    $Activity->num = $num;
    $Activity->addtime = time();
    $aid = $Activity->add();
    if($aid){
      $ActivityJoin = D('ActivityJoin');//发布者自动加入
      $ActivityJoin->uid = $uid;
      $ActivityJoin->iforger = 1;
      $ActivityJoin->aid = $aid;
      $ActivityJoin->addtime = time();
      $ActivityJoin->add();
      //增加积分
      addScore($uid,12);
      //通知组织成员
      // if($iforg == '1'){
      //
      // }
      ApiResult("200","","发布成功");
    }else{
      ApiResult("201","","发布失败");
    }
  }
  /**
  *活动详情
  */
  public function activity(){
    $aid = I('aid');
    $uid = I('uid');
    $Activity = D('Activity');
    $ActivityJoin = D('ActivityJoin');
    $Users = D('Users');
    $ImageUrl = C('ImageUrl');
    $ainfo = $Activity->where('id='.$aid)->field('id as aid,uid,title,img,address,begin,end,intro,tag,num')->find();
    if(empty($ainfo))
      ApiResult("201","","该活动已被删除");
    $ainfo['ifjoin'] = '0';
    $ainfo['iforger'] = '0';
    if($uid){
      $ifjoin = $ActivityJoin->where('uid='.$uid.' and aid='.$aid)->count();
      if($ifjoin>0)
        $ainfo['ifjoin'] = '1';
      if($uid == $ainfo['uid'])
        $ainfo['iforger'] = '1';
    }
    $uinfo = $Users->where('uid='.$ainfo['uid'])->field('realname,phone')->find();
    unset($ainfo['uid']);
    $ainfo['title'] = str_replace("&quot;","'",$ainfo['title']);
    $ainfo['intro'] = str_replace("&quot;","'",$ainfo['intro']);
    $ainfo['img'] = $ImageUrl.$ainfo['img'];
    $ainfo['realname'] = $uinfo['realname'];
    $ainfo['phone'] = $uinfo['phone'];
    if(time()<$ainfo['end']){
      $ainfo['status'] = "正在进行";
    }else if(time()>$ainfo['end']){
      $ainfo['status'] = "已结束";
    }
    $ainfo['begin'] = $ainfo['begin']?date('Y-m-d H:i',$ainfo['begin']):"";
    $ainfo['end'] = $ainfo['end']?date('Y-m-d H:i',$ainfo['end']):"";
    $ainfo['joinnum'] = $ActivityJoin->where('aid='.$aid)->count();
    switch ($ainfo['tag']) {
      case '1':
        $ainfo['tag'] = "扶贫济困";
        break;
      case '2':
        $ainfo['tag'] = "助老助残";
        break;
      case '3':
        $ainfo['tag'] = "生态建设";
        break;
      case '4':
        $ainfo['tag'] = "平安巡防";
        break;
      case '5':
        $ainfo['tag'] = "实践培训";
        break;
      case '6':
        $ainfo['tag'] = "社区服务";
        break;
      case '7':
        $ainfo['tag'] = "大型活动";
        break;
      case '8':
        $ainfo['tag'] = "抢险救灾";
        break;
      case '9':
        $ainfo['tag'] = "其他";
        break;
    }
    ApiResult("200",$ainfo,"");
  }

  /**
  *活动报名
  */
  public function joinactivity(){
    $uid = I('uid');
    $aid = I('aid');
    //
    $Activity = D('Activity');
    $ifexist = $Activity->where('id='.$aid)->count();
    if(!$ifexist)
      ApiResult("201","","该活动已被删除");
    $ActivityJoin = D('ActivityJoin');
    //判断是否已经加入
    $ifjoin = $ActivityJoin->where('uid='.$uid.' and aid='.$aid)->count();
    if($ifjoin>0)
      ApiResult("202","","你已经报名了");
    //判断是否超过报名人数
    $joinnum = $ActivityJoin->where('aid='.$aid)->count();
    $num = $Activity->where('id='.$aid)->getField('num');
    if($joinnum>=$num)
      ApiResult("203","","报名人数已满");
    $ActivityJoin->uid = $uid;
    $ActivityJoin->aid = $aid;
    $ActivityJoin->addtime = time();
    $re = $ActivityJoin->add();
    if($re)
      ApiResult("200",array(),"报名成功");
  }
  /**
  *取消活动报名
  */
  public function cancelja(){
    $uid = I('uid');
    $aid = I('aid');
    $ActivityJoin = D('ActivityJoin');
    $ifjoin = $ActivityJoin->where('uid='.$uid.' and aid='.$aid)->count();
    if($ifjoin==0)
      ApiResult("201","","没有报名");
    $ifscan = $ActivityJoin->where('uid='.$uid.' and aid='.$aid.' and scantime>0')->count();
    if($ifscan>0)
      ApiResult("203","","签到的活动不能取消");
    $iforger = $ActivityJoin->where('uid='.$uid.' and aid='.$aid)->getField('iforger');
    if($iforger)
      ApiResult("202","","发起者不能取消");
    $re = $ActivityJoin->where('uid='.$uid.' and aid='.$aid)->delete();
    if($re)
      ApiResult("200",array(),"取消成功");
  }
  /**
  *活动成员
  */
  public function activitymember(){
    $aid = I('aid');
    $ActivityJoin = D('ActivityJoin');
    $ImageUrl = C('ImageUrl');
    $ajlist = $ActivityJoin->alias('aj')->join('lct_users u ON u.uid=aj.uid')
    ->where('aid='.$aid)->field('aj.iforger,aj.addtime,u.phone,u.realname,u.headpic')->select();
    $res = array();
    foreach ($ajlist as $key => $value) {
    	$res[$key]['realname'] = $value['realname'];
      $res[$key]['iforger'] = $value['iforger'];
      $res[$key]['phone'] = $value['phone'];
      $res[$key]['headpic'] = $ImageUrl.$value['headpic'];
      $res[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
    }
    ApiResult("200",$res,"");
  }
  /**
  *扫一扫，活动签到
  */
  public function scan(){
    $uid = I('uid');
    $aid = I('aid');
    $lat = I('lat');
    $lng = I('lng');
    $address = I('address');
    $Activity = D('Activity');
    $end = $Activity->where('id='.$aid)->getField('end');
    if(time()>$end){
      ApiResult("202","","活动已经结束");
    }
    $ActivityJoin = D('ActivityJoin');
    $ifjoin = $ActivityJoin->where('uid='.$uid.' and aid='.$aid)->count();
    if($ifjoin==0)
      ApiResult("201","","没有报名");
    $ActivityJoin->lat = $lat;
    $ActivityJoin->lng = $lng;
    $ActivityJoin->address = $address;
    $ActivityJoin->scantime = time();
    $re = $ActivityJoin->where('uid='.$uid.' and aid='.$aid)->save();
    if($re){
      addScore($uid,3);
      ApiResult("200",array(),"签到成功");
    }

  }
  /**
  *已签到活动
  */
  public function scaned(){
    $uid = I('uid');
    $iPageItem = I('iPageItem');
    $iPageIndex = I('iPageIndex');
    $ActivityJoin = D('ActivityJoin');
    $ImageUrl = C('ImageUrl');
    $ajlist = $ActivityJoin->alias('aj')->join('lct_activity a ON aj.aid=a.id')
    ->field('a.id,a.img,a.title,aj.addtime,aj.address')->page($iPageIndex+1,$iPageItem)
    ->where('aj.uid='.$uid.' and aj.scantime > 0 and iforger=0')->select();
    if(empty($ajlist))
      ApiResult("201",array(),"没有");
    $res = array();
    foreach ($ajlist as $key => $value) {
      $res[$key]['aid'] = $value['id'];
      $res[$key]['title'] = str_replace("&quot;","'",$value['title']);
      $res[$key]['img'] = $ImageUrl.$value['img'];
      $res[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
      $res[$key]['address'] = $value['address'];
    }
    ApiResult("200",$res,"");
  }
  /**
  *比一比
  */
  public function than(){
    $type = I('type');
    $iPageItem = I('iPageItem');
    $iPageIndex = I('iPageIndex');
    $ImageUrl = C('ImageUrl');
    $res = array();
    if($type == '1'){//个人
      $Score = D('Score');
      $sinfo = $Score->alias('s')->join('lct_users u ON u.uid = s.uid')
      ->field('u.headpic,u.realname,u.motto,s.sum')->page($iPageIndex+1,$iPageItem)
      ->where('aut=1 and ifdelete=0 and volunt=1')->order('s.sum desc')->select();
      if(empty($sinfo))
        ApiResult("201",$res,"还没有");
      foreach ($sinfo as $key => $value) {
        $res[$key]['realname'] = $value['realname'];
        $res[$key]['headpic'] = $ImageUrl.$value['headpic'];
        $res[$key]['motto'] = $value['motto']?$value['motto']:"";
        $res[$key]['sum'] = $value['sum'];
      }
    }else{//组织
      $Org = D('Org');
      $oinfo = $Org->alias('o')->join('lct_users u ON u.phone = o.phone')
      ->field('o.img,o.orgname,o.score')->page($iPageIndex+1,$iPageItem)
      ->where('o.aut=1')->order('o.score desc')->select();
      if(empty($oinfo))
        ApiResult("201",$res,"还没有");
      foreach ($oinfo as $key => $value) {
        $res[$key]['realname'] = $value['orgname'];
        $res[$key]['headpic'] = $ImageUrl.$value['img'];
        $res[$key]['motto'] = "";
        $res[$key]['sum'] = $value['score'];
      }
    }
    ApiResult("200",$res,"");
  }
  /**捐赠

  public function donate(){
    $uid = I('uid');
    $content = I('content');
    $thing = I('thing');
    $imgurl_json = I('imgurl_json');
    $num = I('num');
    $Donate = D('Donate');

    $Donate->uid = $uid;
    $Donate->uid = $content;
    $Donate->uid = $thing;
    if($imgurl_json){
      $img = str_replace('&quot;','"',$imgurl_json);//格式化&quot;->"
      $Donate->img = $img;
    }
    $Donate->uid = $num;
    $Donate->addtime = time();
    $did = $Donate->add();
    if($did){
      $res['did'] = $did;
      ApiResult('200',$res,'发布成功');
    }
    ApiResult('201','','发布失败');
  }
  */
  /*
  * 捐赠记录
*/
  public function donatelog(){
    $iPageItem = I('iPageItem');
    $iPageIndex = I('iPageIndex');
    $Donate = D('Donate');
    $Exchange = D('Exchange');
    $dlist = $Donate->where('ifbuy=0')->page($iPageIndex+1,$iPageItem)
    ->field('id,score,img,thing,unit,inst,num,addtime')->order('addtime desc')->select();
    if(empty($dlist)){
      ApiResult('201','','没有');
    }
    $res = array();
    $ImageUrl = C('ImageUrl');
    foreach ($dlist as $key => $value) {
      $n = $Exchange->where('did='.$value['id'])->count();
      $res[$key]['did'] = $value['id'];
      $res[$key]['score'] = $value['score'];
      $res[$key]['thing'] = $value['thing'];
      $res[$key]['inst'] = $value['inst'];
      $res[$key]['unit'] = $value['unit'];
      $res[$key]['num'] = $value['num'];
      $res[$key]['allnum'] = (string)($value['num']+$n);
      $res[$key]['img'] = $ImageUrl.$value['img'];
      $res[$key]['addtime'] = date('Y-m-d',$value['addtime']);
    }
    ApiResult('200',$res,'');
  }
  /**
  * 兑换物列表
  */
  public function exthinglist(){
    $iPageItem = I('iPageItem');
    $iPageIndex = I('iPageIndex');
    $Donate = D('Donate');
    $Exchange = D('Exchange');
    $dlist = $Donate->where('num>0')->page($iPageIndex+1,$iPageItem)
    ->field('id,score,img,thing,unit,inst,num,addtime')->order('addtime desc')->select();
    if(empty($dlist)){
      ApiResult('201','','没有');
    }
    $res = array();
    $ImageUrl = C('ImageUrl');
    foreach ($dlist as $key => $value) {
      $n = $Exchange->where('did='.$value['id'])->count();
      $res[$key]['did'] = $value['id'];
      $res[$key]['score'] = $value['score'];
      $res[$key]['thing'] = $value['thing'];
      $res[$key]['inst'] = $value['inst'];
      $res[$key]['unit'] = $value['unit'];
      $res[$key]['num'] = $value['num'];
      $res[$key]['allnum'] = (string)($value['num']+$n);
      $res[$key]['img'] = $ImageUrl.$value['img'];
      $res[$key]['addtime'] = date('Y-m-d',$value['addtime']);
    }
    ApiResult('200',$res,'');
  }
  /**
  * 兑换物详情
  */
  public function exthing(){
    $did = I('did');
    $Donate = D('Donate');
    //是否删除
    $ifexist = $Donate->where('id='.$did)->count();
    if($ifexist==0){
      ApiResult('201','','兑换物不存在');
    }
    //是否已经兑换完
    $num = $Donate->where('id='.$did)->getField('num');
    if($num<=0){
      ApiResult('202','','已经兑换完');
    }
    $dlist = $Donate->where('id='.$did)->field('id,inst,thing,unit,img,cate,num,addtime,score')
    ->order('addtime desc')->find();
    $res = array();
    $Org = D('Org');
    $ImageUrl = C('ImageUrl');
    $res['did'] = $dlist['id'];
    $res['thing'] = $dlist['thing'];
    $res['num'] = $dlist['num'];
    $res['score'] = $dlist['score'];
    $res['img'] = $ImageUrl.$dlist['img'];
    $res['inst'] = $dlist['inst'];
    $res['unit'] = $dlist['unit'];
    switch ($dlist['cate']) {
      case '1':
        $res['type'] = '日用品';
        break;
      case '2':
        $res['type'] = '服装';
        break;
      case '3':
        $res['type'] = '书本';
        break;
      default:
        $res['type'] = '其他';
        break;
    }
    $res['addtime'] = date('Y-m-d',$dlist['addtime']);
    ApiResult('200',$res,'');
  }
  /**
  * 兑换
  */
  public function exchange(){
    $uid = I('uid');
    $did = I('did');
    $Donate = D('Donate');
    $Score = D('Score');
    $sum = $Score->where('uid='.$uid)->getField('sum');//用户积分
    //是否删除
    $ifexist = $Donate->where('id='.$did)->count();
    if($ifexist==0){
      ApiResult('201','','兑换物不存在');
    }
    $dinfo = $Donate->where('id='.$did)->field('num,score')->find();
    //积分是否够
    $score = $dinfo['score'];
    if($score>$sum){
      ApiResult('205','','积分不够');
    }
    //是否已经兑换完
    $num = $dinfo['num'];
    if($num<=0){
      ApiResult('202','','已经兑换完');
    }
    $Exchange = D('Exchange');
    $ifchange = $Exchange->where('did='.$did.' and uid='.$uid)->count();
    if($ifchange>0){
      ApiResult('203','','你已经兑换过了');
    }
    $Exchange->uid = $uid;
    $Exchange->did = $did;
    $Exchange->addtime = time();
    $re = $Exchange->add();
    if($re){
      //数量减少1
      $Donate->where('id='.$did)->setDec('num');
      $Score->where('uid='.$uid)->setDec('sum',$score);//扣除积分
      ApiResult('200','','兑换成功');
    }else{
      ApiResult('204','','兑换失败');
    }
  }
  /**
  * 兑换记录
  */
  public function exchangelog(){
    $uid = I('uid');
    $iPageItem = I('iPageItem');
    $iPageIndex = I('iPageIndex');
    $Exchange = D('Exchange');
    $elist = $Exchange->where('uid='.$uid)->field('id,did,addtime,ifget')
    ->page($iPageIndex+1,$iPageItem)->order('addtime desc')->select();
    if(empty($elist)){
      ApiResult('201','','没有');
    }
    $res = array();
    $Donate = D('Donate');
    $ImageUrl = C('ImageUrl');
    foreach ($elist as $key => $value) {
      $res[$key]['did'] = $value['did'];
      $res[$key]['ifget'] = $value['ifget'];
      $res[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
      $dinfo = $Donate->where('id='.$value['did'])->field('img,thing')->find();
      $res[$key]['img'] = $dinfo['img']?$ImageUrl.$dinfo['img']:"";
      $res[$key]['thing'] = $dinfo['thing']?$dinfo['thing']:"";
    }
    ApiResult('200',$res,'');
  }
  /**
   * 得到志愿者积分
   */
  public function getscore(){
    $uid = I('uid');
    $Users = D('Users');
    $Score = D('Score');
    $ifvolunt = $Users->where('uid='.$uid)->getField('volunt');
    if($ifvolunt != '1')
      ApiResult('201','','你不是志愿者');
    $ifexist = $Score->where('uid='.$uid)->count();
    if($ifexist==0){
      $Score->uid = $uid;
      $Score->sum = 0;
      $Score->add();
    }
    $res['score'] = $Score->where('uid='.$uid)->getField('sum');
    ApiResult('200',$res,'');
  }
}
