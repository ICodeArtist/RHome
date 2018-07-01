<?php
namespace Admin\Controller;
use Think\Controller;
class EunityController extends Controller {
  //金点子
  public function getIdeaList(){
    $Dynamic = D('Dynamic');
    $DynamicComment = D('DynamicComment');
    $DynamicBrowse = D('DynamicBrowse');
    $DynamicCollect = D('DynamicCollect');
    $Users = D('Users');
    $ImageUrl = C('ImageUrl');
    $dlist = $Dynamic->where('cateid=1')->select();
    $res = array();
    foreach ($dlist as $key => $value) {
      $res[$key]['id'] = $value['id'];
      $user = $Users->where('uid='.$value['uid'])->field('nickname,headpic')->find();
      $res[$key]['realname'] = $user['nickname'];//昵称
      $res[$key]['title'] = $value['title'];
      $res[$key]['img'] = array();
      $img_arr = json_decode($value['img'],true);
      if(!empty($img_arr)){
        foreach ($img_arr as $k => $v) {
          $res[$key]['img'][$k]['imgurl'] = $ImageUrl.$v;
        }
      }
      $res[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
      $res[$key]['commentnum'] = $DynamicComment->where('dyid='.$value['id'])->count();
      $res[$key]['browsenum'] = $DynamicBrowse->where('dyid='.$value['id'])->count();
      $res[$key]['collectnum'] = $DynamicCollect->where('dyid='.$value['id'])->count();
      $res[$key]['aut'] = $value['ifshow'];
      if($value['ifshow']){
        $res[$key]['status'] = '已审核';
      }else{
        $res[$key]['status'] = '未审核';
      }
    }
    $this->assign('dlist',$res);
    $this->display('idealist');
  }
  /**
  *心里话
  */
  public function getHeartList(){
    $Dynamic = D('Dynamic');
    $DynamicComment = D('DynamicComment');
    $DynamicBrowse = D('DynamicBrowse');
    $DynamicCollect = D('DynamicCollect');
    $Users = D('Users');
    $ImageUrl = C('ImageUrl');
    $dlist = $Dynamic->where('cateid=2')->select();
    $res = array();
    foreach ($dlist as $key => $value) {
      $res[$key]['id'] = $value['id'];
      $user = $Users->where('uid='.$value['uid'])->field('nickname,headpic')->find();
      $res[$key]['realname'] = $user['nickname'];//昵称
      $res[$key]['title'] = $value['title'];
      $res[$key]['img'] = array();
      $img_arr = json_decode($value['img'],true);
      if(!empty($img_arr)){
        foreach ($img_arr as $k => $v) {
          $res[$key]['img'][$k]['imgurl'] = $ImageUrl.$v;
        }
      }
      $res[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
      $res[$key]['commentnum'] = $DynamicComment->where('dyid='.$value['id'])->count();
      $res[$key]['browsenum'] = $DynamicBrowse->where('dyid='.$value['id'])->count();
      $res[$key]['collectnum'] = $DynamicCollect->where('dyid='.$value['id'])->count();
      $res[$key]['aut'] = $value['ifshow'];
      if($value['ifshow']){
        $res[$key]['status'] = '已审核';
      }else{
        $res[$key]['status'] = '未审核';
      }
    }
    $this->assign('dlist',$res);
    $this->display('idealist');
  }
  /**
  *美哉越城
  */
  public function getBeautyList(){
    $Dynamic = D('Dynamic');
    $DynamicComment = D('DynamicComment');
    $DynamicBrowse = D('DynamicBrowse');
    $DynamicCollect = D('DynamicCollect');
    $Users = D('Users');
    $ImageUrl = C('ImageUrl');
    $dlist = $Dynamic->where('cateid=3')->select();
    $res = array();
    foreach ($dlist as $key => $value) {
      $res[$key]['id'] = $value['id'];
      $user = $Users->where('uid='.$value['uid'])->field('nickname,headpic')->find();
      $res[$key]['realname'] = $user['nickname'];//昵称
      $res[$key]['title'] = $value['title'];
      $res[$key]['img'] = array();
      $img_arr = json_decode($value['img'],true);
      if(!empty($img_arr)){
        foreach ($img_arr as $k => $v) {
          $res[$key]['img'][$k]['imgurl'] = $ImageUrl.$v;
        }
      }
      $res[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
      $res[$key]['commentnum'] = $DynamicComment->where('dyid='.$value['id'])->count();
      $res[$key]['browsenum'] = $DynamicBrowse->where('dyid='.$value['id'])->count();
      $res[$key]['collectnum'] = $DynamicCollect->where('dyid='.$value['id'])->count();
      $res[$key]['aut'] = $value['ifshow'];
      if($value['ifshow']){
        $res[$key]['status'] = '已审核';
      }else{
        $res[$key]['status'] = '未审核';
      }
    }
    $this->assign('dlist',$res);
    $this->display('idealist');
  }
  /**
  *审核
  */
  public function pass(){
    $id = $_POST['id'];
    $Dynamic = D('Dynamic');
    $Dynamic->ifshow = 1;
    $re = $Dynamic->where('id='.$id)->save();
    if($re){
      $dinfo = $Dynamic->where('id='.$id)->field('cateid,uid')->find();
      $Point = D('Point');
      $uid = $dinfo['uid'];
      if($dinfo['cateid'] == '1'){//金点子+8分
        $msg = '金点子(id='.$id.')审核通过增加';
        $point = 8;
      }else if($dinfo['cateid'] == '2'){//心里话+5分
        $msg = '心里话(id='.$id.')审核通过增加';
        $point = 5;
      }else{//美哉越城+5分
        $msg = '美哉越城(id='.$id.')审核通过增加';
        $point = 5;
      }
      setPointLog($uid,$msg,$point);
      $Point->where('uid='.$uid)->setInc('sum',$point); // 用户的积分增加
    }
    $data['code'] = '200';
    $this->ajaxReturn($data);
  }
  /**
  *删除
  */
  public function delDynamic(){
    $id = $_POST['id'];
    $Dynamic = D('Dynamic');
    $re = $Dynamic->where('id='.$id)->delete();
    if($re){
      $data['msg'] = 'success';
    }else{
      $data['msg'] = 'fail';
    }
    $this->ajaxReturn($data);
  }
  /**
  *评论列表
  */
  public function getCommentList(){
    if(isset($_GET['id']) && is_numeric($_GET['id']) && $_GET['id']>0){
      $Users = D('Users');
      $DynamicComment = D('DynamicComment');
      $clist = $DynamicComment->where('dyid='.$_GET['id'])->select();
      foreach ($clist as $key => $value) {
        $clist[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
        $clist[$key]['realname'] = $Users->where('uid='.$value['uid'])->getField('realname');
      }
      $this->assign('clist',$clist);
      $this->display('commentlist');
    }else{
      $this->error('获取评论失败');
    }
  }
  /**
  *删除评论
  */
  public function delComment(){
    $id = $_POST['id'];
    $DynamicComment = D('DynamicComment');
    $DynamicComment->where('id='.$id)->delete();
    $date['code'] = '200';
    $this->ajaxReturn($date);
  }
  /**
  *编辑
  */
  public function EditDynamic(){
    $id = $_POST['id'];
    $cateid = $_POST['cateid'];
    $Dynamic = D('Dynamic');
    $old = $Dynamic->where('id='.$id)->field('cateid,uid')->find();
    $oldname = getDynamicCate($old['cateid']);
    $oldnew = getDynamicCate($cateid);
    $Dynamic->cateid = $cateid;
    $Dynamic->where('id='.$id)->save();
    $mobile = D('Users')->where('uid='.$old['uid'])->getField('phone');
    $message = "您分享的“".$oldname."”文章，经管理员审核，调整到“".$oldnew."”页面，请查看！";
    $data = SmsMessage($mobile,22,$message);
    $this->ajaxReturn($data);
  }
  /**
   * e分享
   */
   public function eShare(){
     $Eshare = D('Eshare');
     $ImageUrl = C('ImageUrl');
     $elist = $Eshare->where('1=1')->field('id,name,surl,img,addtime')->select();
     foreach ($elist as $key => $value) {
       $elist[$key]['img'] = $ImageUrl.$value['img'];
       $elist[$key]['addtime'] = date('Y-m-d',$value['addtime']);
     }
     $this->assign('elist',$elist);
     $this->display('elist');
   }
   /**
    * 发布e分享
    */
    public function addEshare(){
      $Eshare = D('Eshare');
      $name = $_POST['name'];
      $surl = $_POST['surl'];
      //投票介绍缩略图
      $thumbimage_url = "";
      $file = $_FILES["photo"];
      if(!empty($file["name"])) {
        $path = 'eshare';
        $thumbimage_url = UploadImageByMySelf($path,$file);
      }
      $Eshare->name = $name;
      $Eshare->surl = $surl;
      $Eshare->img = $thumbimage_url;
      $Eshare->addtime = time();
      $Eshare->add();
      $this->success('新增成功', __CONTROLLER__.'/eShare');
    }
    /**
     * 删除e分享
     */
     public function delEshare(){
       $eid = $_POST['eid'];
       $Eshare = D('Eshare');
       $re = $Eshare->where('id='.$eid)->delete();
       if($re){
   			$data['msg'] = 'success';
   		 }else{
   			$data['msg'] = 'fail';
   		 }
   		 $this->ajaxReturn($data);
     }
     /**
      * 编辑e分享
      */
      public function EditEshare(){
        $Eshare = D('Eshare');
        $name = $_POST['name'];
        $surl = $_POST['surl'];
        $eid = $_POST['eid'];

        $file = $_FILES["photo"];
        if(!empty($file["name"])) {
          $path = 'eshare';
          $thumbimage_url = UploadImageByMySelf($path,$file);
          $Eshare->img = $thumbimage_url;
        }
        $Eshare->name = $name;
        $Eshare->surl = $surl;
        $Eshare->where('id='.$eid)->save();
        $date['code'] = '200';
    		$this->ajaxReturn($date);
      }
}
