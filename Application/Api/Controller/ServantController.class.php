<?php
namespace Api\Controller;
use Think\Controller;
class ServantController extends Controller {
  //服务列表
  public function svslist(){
    $uid = I('uid');
    $option = I('option');
    $iPageItem = I('iPageItem');
    $iPageIndex = I('iPageIndex');
    $Svs = D('Svs');
    $ImageUrl = C('ImageUrl');
    if($option == 1){
      $query = ' 1=1 ';
    }else if($option == 2){
      $query =' uid='.$uid;
      $account = D('Users')->where('uid='.$uid)->getField('account');//得到用户账号
      $party = D('OrgParty')->where('telephone="'.$account.'"')->getField('orgid');
      if($party && is_numeric($party) && $party>0)
        $query .=' or party='.$party;
      $branchid = D('OrgBranch')->where('telephone="'.$account.'"')->getField('id');
      if($branchid && is_numeric($branchid) && $branchid>0)
        $query .=' or branchid='.$branchid;
    }else{
      ApiResult("202",array(),"option错了");
    }
    $slist = $Svs->where($query)->page($iPageIndex+1,$iPageItem)
    ->field('name,phone,title,img,id,address,score,addtime,status,party,branchid,uid,lev')
    ->order('addtime desc')->select();
    if(empty($slist))
      ApiResult("201",array(),"没有");
    $res = array();
    foreach ($slist as $key => $value) {
      $res[$key]['sid'] = $value['id'];
      $res[$key]['name'] = $value['name'];
      $res[$key]['phone'] = $value['phone'];
      $res[$key]['title'] = $value['title'];
      $res[$key]['addtime'] = date('Y-m-d',$value['addtime']);
      $res[$key]['address'] = $value['address'];
      $img_arr = json_decode($value['img'],true);
      $res[$key]['img'] = array();
      if(!empty($img_arr)){
        foreach ($img_arr as $k => $va) {
          $res[$key]['img'][$k]['imgurl'] = $ImageUrl.$va;
        }
      }
      $res[$key]['score'] = $value['score'];
      $res[$key]['status'] = $value['status'];
      $res[$key]['lev'] = $value['lev'];
      if($value['party'] && is_numeric($value['party']) && $value['party']>0){
        $res[$key]['party'] = D('OrgParty')->where('orgid='.$value['party'])->getField('party');
      }else{
        $res[$key]['party'] = "异常";
      }
      $res[$key]['barnch'] = "";
      $res[$key]['person'] = "";
      switch ($value['lev']) {
        // case '2':
        // if($value['branchid'] && is_numeric($value['branchid']) && $value['branchid']>0){
        //   $res[$key]['barnch'] = D('OrgBranch')->where('id='.$value['branchid'])->getField('branch');
        // }else{
        //   $res[$key]['barnch'] = "异常";
        // }
          //break;
        case '3':
        // if($value['branchid'] && is_numeric($value['branchid']) && $value['branchid']>0){
        //   $res[$key]['barnch'] = D('OrgBranch')->where('id='.$value['branchid'])->getField('branch');
        // }else{
        //   $res[$key]['barnch'] = "";
        // }
        if($value['uid'] && is_numeric($value['uid']) && $value['uid']>0){
          $res[$key]['person'] = D('Users')->where('uid='.$value['uid'])->getField('realname');
          $res[$key]['barnch'] = D('Users')->where('uid='.$value['uid'])->getField('branch');
        }else{
          $res[$key]['person'] = "";
          $res[$key]['barnch'] = "";
        }
          break;
      }
    }
    ApiResult('200',$res,'');
  }
  //发布需求
  public function addsvs(){
    $uid = I('uid');
    $title = I('title');
    $content = I('content');
    $imgurl_json = I('imgurl_json');
    $address = I('address');

    $Svs = D('Svs');
    $Svs->uid = $uid;
    $Svs->title = $title;
    $Svs->content = $content;
    if($imgurl_json){
      $img = str_replace('&quot;','"',$imgurl_json);//格式化&quot;->"
      $Svs->img = $img;
    }
    $Svs->address = $address;
    $Svs->addtime = time();
    $Svs->add();
    ApiResult('200',array(),'发布成功');
  }

  /**
   * 需求服务详情
   */
   public function svs(){
     $sid = I('sid');
     $Svs = D('Svs');
     $ImageUrl = C('ImageUrl');
     $sinfo = $Svs->where('id='.$sid)->field('id,name,phone,title,content,img,address,score,addtime,status,party,branchid,uid,lev,evaluate')->find();
     if(empty($sinfo))
       ApiResult("201",array(),"该需求已被删除");
      $res = array();
      $res['sid'] = $sinfo['id'];
      $res['name'] = $sinfo['name'];
      $res['phone'] = $sinfo['phone'];
      $res['title'] = $sinfo['title'];
      $res['content'] = $sinfo['content'];
      $res['addtime'] = date('Y-m-d',$sinfo['addtime']);
      $res['address'] = $sinfo['address'];
      $img_arr = json_decode($sinfo['img'],true);
      $res['img'] = array();
      if(!empty($img_arr)){
        foreach ($img_arr as $k => $va) {
          $res['img'][$k]['imgurl'] = $ImageUrl.$va;
        }
      }
      $res['score'] = $sinfo['score'];
      $res['status'] = $sinfo['status'];
      $res['lev'] = $sinfo['lev'];
      $party = $sinfo['party'];
      if($party && is_numeric($party) && $party>0){
        $res['orgid'] = $party;
        $res['party'] = D('OrgParty')->where('orgid='.$party)->getField('party');
        $branchid = D('OrgBranch')->where('branch="'.$res['party'].'"')->getField('id');
        $res['bid'] = $branchid?$branchid:"";
        $telephone = D('OrgParty')->where('orgid='.$party)->getField('telephone');
      }else{
        $res['orgid'] = "";
        $res['party'] = "";
        $res['bid'] = "";
        $res['handle'] = "";
      }
      $res['branchid'] = $sinfo['branchid']?$sinfo['branchid']:"";
      $res['barnch'] = "";
      $res['uid'] = $sinfo['uid']?$sinfo['uid']:"";
      $res['person'] = "";
      switch ($sinfo['lev']) {
        case '2':
        if($sinfo['branchid'] && is_numeric($sinfo['branchid']) && $sinfo['branchid']>0){
          $res['barnch'] = D('OrgBranch')->where('id='.$sinfo['branchid'])->getField('branch');
        }else{
          $res['barnch'] = "";
        }
        $telephone = D('OrgBranch')->where('id='.$sinfo['branchid'])->getField('telephone');
          break;
        case '3':
        // if($sinfo['branchid'] && is_numeric($sinfo['branchid']) && $sinfo['branchid']>0){
        //   $res['barnch'] = D('OrgBranch')->where('id='.$sinfo['branchid'])->getField('branch');
        // }else{
        //   $res['barnch'] = "";
        // }
        if($sinfo['uid'] && is_numeric($sinfo['uid']) && $sinfo['uid']>0){
          $res['person'] = D('Users')->where('uid='.$sinfo['uid'])->getField('realname');
          $res['barnch'] = D('Users')->where('uid='.$sinfo['uid'])->getField('branch');
        }else{
          $res['person'] = "";
          $res['barnch'] = "";
        }
        $telephone = D('Users')->where('uid='.$sinfo['uid'])->getField('phone');
          break;
      }
      if($sinfo['status'] == '2'){
        $telephone = D('OrgParty')->where('orgid='.$party)->getField('telephone');
      }
      $uid = D('Users')->where('phone="'.$telephone.'"')->getField('uid');
      $res['handle'] = $uid?$uid:"";

      $res['evaluate'] = $sinfo['evaluate']?$sinfo['evaluate']:"";
      ApiResult('200',$res,'');
   }
   /**
    * 分配
    */
   public function allotsvs(){
     $sid = I('sid');

     $branchid = I('branchid');
     $uid = I('uid');

     $Svs = D('Svs');
     $svsinfo = $Svs->where('id='.$sid)->field('lev,party')->find();
     $lev = $svsinfo['lev'];
     $orgid = $svsinfo['party'];
     if($branchid && is_numeric($branchid) && $branchid>0){
       if($lev != '1'){
         ApiResult('201','','分配过程错误');
       }
       $oid = D('OrgBranch')->where('id='.$branchid)->getField('orgid');
       if($oid != $orgid ){
         ApiResult('202','','组织关系错误');
       }
       $Svs->branchid = $branchid;
       $Svs->lev = 2;
       $Svs->updatetime = time();
       $re = $Svs->where('id='.$sid)->save();
       if($re){
         ApiResult('200','','分配成功');
       }else{
         ApiResult('203','','分配失败');
       }
     }
     if($uid && is_numeric($uid) && $uid>0){
       if($lev == '3'){
         ApiResult('201','','分配过程错误');
       }
       $oid = D('Users')->where('uid='.$uid)->getField('orgid');
       if($oid != $orgid ){
         ApiResult('202','','组织关系错误');
       }
       $Svs->uid = $uid;
       $Svs->lev = 3;
       $Svs->updatetime = time();
       $re = $Svs->where('id='.$sid)->save();
       if($re){
         $Users = D('Users');
         $mobile = $Users->where('uid='.$uid)->getField('phone');
         if($mobile>0){
           $message = '你有一条96345需要处理';
           SmsMessage($mobile,'23',$message);
         }
         ApiResult('200','','分配成功');
       }else{
         ApiResult('203','','分配失败');
       }
     }
   }
   /**
    * 完成需求
    */
   public function finishsvs(){
     $uid = I('uid');
     $sid = I('sid');
     $Svs = D('Svs');

     $sinfo = $Svs->where('id='.$sid)->field('uid,lev,status,party')->find();
     //判断状态是否正确
     if($sinfo['status'] != '1'){
       ApiResult('201','','不是处理中状态');
     }
     //判断分配
     if($sinfo['lev'] != '3'){
       ApiResult('202','','还没分配到个人');
     }
     //判断是否是当前用户来完成
     if($uid != $sinfo['uid']){
       ApiResult('203','','不是分配给你的');
     }
     $party = $sinfo['party'];
     $Svs->status = 2;
     $Svs->updatetime = time();
     $re = $Svs->where('id='.$sid)->save();
     if($re){
       $OrgParty = D('OrgParty');
       $mobile = $OrgParty->where('orgid='.$party)->getField('telephone');
       if($mobile>0){
         $message = '你有一条96345需要处理';
         SmsMessage($mobile,'23',$message);
       }
       ApiResult('200','','处理成功');
     }else{
       ApiResult('204','','处理失败');
     }
   }

   /**
    * 评价需求
    */
   public function evasvs(){
     $uid = I('uid');
     $sid = I('sid');
     $content = I('content');
     $Svs = D('Svs');
     $sinfo = $Svs->where('id='.$sid)->field('party,status,score,uid')->find();
     //判断状态是否正确
     if($sinfo['status'] != '2'){
       ApiResult('201','','不是待评价状态');
     }
     //判断是否是当前用户来完成
     $cuid = D('OrgParty')->alias('op')->join('lct_users u ON u.account=op.telephone')
     ->where('op.orgid='.$sinfo['party'])->getField('uid');
     if($uid != $cuid){
       ApiResult('202','','你不能评价');
     }
     $Svs->status = 3;
     $Svs->evaluate = trim($content);
     $Svs->updatetime = time();
     $re = $Svs->where('id='.$sid)->save();
     if($re){
       //增加积分
       addScore($sinfo['uid'],$sinfo['score']);
       ApiResult('200','','评价成功');
     }else{
       ApiResult('204','','评价失败');
     }
   }
}
