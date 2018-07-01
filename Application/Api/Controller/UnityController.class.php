<?php
namespace Api\Controller;
use Think\Controller;
class UnityController extends Controller {
  //e联心列表
  public function idealist(){
    $uid = I('post.uid','');
    $cateid = I('cateid');
    $iPageItem = I('iPageItem');
    $iPageIndex = I('iPageIndex');
    $Dynamic = D('Dynamic');
    $DynamicComment = D('DynamicComment');
    $DynamicBrowse = D('DynamicBrowse');
    $DynamicCollect = D('DynamicCollect');
    $Users = D('Users');
    $ImageUrl = C('ImageUrl');
    if($cateid == '4'){
      $query = '(cateid='.$cateid.')';
    }else{
      $query = '(cateid='.$cateid.' and ifshow=1)';
    }
    if($uid && is_numeric($uid) && $uid>0){
      $query .= ' or(cateid='.$cateid.' and uid='.$uid.')';
    }
    if($iPageItem){
      $dlist = $Dynamic->where($query)->page($iPageIndex+1,$iPageItem)->order('addtime desc')->select();
    }else{
      $dlist = $Dynamic->where($query)->order('addtime desc')->select();
    }
    $res = array();
    foreach ($dlist as $key => $value) {
      $res[$key]['dyid'] = $value['id'];
      $user = $Users->where('uid='.$value['uid'])->field('nickname,headpic')->find();
      $res[$key]['realname'] = $user['nickname'];//昵称
      $res[$key]['headpic'] = $ImageUrl.$user['headpic'];
      $res[$key]['title'] = $value['title'];
      $res[$key]['content'] = $value['content'];
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
      $res[$key]['ifbrowse'] = '0';
      $res[$key]['ifcollect'] = '0';
      $res[$key]['status'] = '';
      if($uid && is_numeric($uid) && $uid>0){
        $c = $DynamicBrowse->where('dyid='.$value['id'].' and uid='.$uid)->count();
        if($c>0){
          $res[$key]['ifbrowse'] = '1';
        }
        $t = $DynamicCollect->where('dyid='.$value['id'].' and uid='.$uid)->count();
        if($t>0){
          $res[$key]['ifcollect'] = '1';
        }
        if($value['ifshow'] == 0)
          $res[$key]['status'] = '在审核';
        if($value['cateid'] == '4')
          $res[$key]['status'] = '';
      }
    }
    if(!empty($res)){
      ApiResult('200',$res,'');
    }else{
      ApiResult('201',$res,'还没有哦~');
    }
  }
  //发布e联心
  public function ideaadd(){
    $Dynamic = D('Dynamic');
    $uid = I('uid');
    $cateid = I('cateid');
    $title = I('title');
    $content = I('content');
    $imgurl_json = I('imgurl_json');
    $Dynamic->uid = $uid;
    $Dynamic->cateid = $cateid;
    $Dynamic->title = $title;
    $Dynamic->content = $content;
    $Dynamic->ifshow = 1;//审核通过
    $Dynamic->addtime = time();
    if($imgurl_json){
      $img = str_replace('&quot;','"',$imgurl_json);//格式化&quot;->"
      $Dynamic->img = $img;
    }
    $dyid = $Dynamic->add();
    if($dyid){
      $res['dyid'] = $dyid;
      ApiResult('200',$res,'发布成功');
    }
    ApiResult('201','','发布失败');
  }
  //e联心详情
  public function idea(){
    $uid = I('get.uid'.'');
    $dyid = I('dyid');
    $iPageItem = I('iPageItem');
    $iPageIndex = I('iPageIndex');
    $Users = D('Users');
    $Dynamic = D('Dynamic');
    $DynamicComment = D('DynamicComment');
    $DynamicBrowse = D('DynamicBrowse');
    $DynamicCollect = D('DynamicCollect');
    $ImageUrl = C('ImageUrl');
    $dy = $Dynamic->where('id='.$dyid)->find();
    if(empty($dy))
      ApiResult('201',array(),'动态去火星了~');
    $res = array();
    $res['dyid'] = $dy['id'];
    $res['title'] = $dy['title'];
    $author = $Users->where('uid='.$dy['uid'])->field('nickname,headpic')->find();
    $res['author'] = $author['nickname'];
    $res['headpic'] = $ImageUrl.$author['headpic'];
    $res['content'] = $dy['content'];
    $res['img'] = array();
    $img_arr = json_decode($dy['img'],true);
    if(!empty($img_arr)){
      foreach ($img_arr as $key => $value) {
        $res['img'][$key]['imgurl'] = $ImageUrl.$value;
      }
    }
    $res['addtime'] = date('Y-m-d',$dy['addtime']);
    if($uid && is_numeric($uid) && $uid>0){
      //不是游客，检查是否已经浏览，是否已经点赞
      $c = $DynamicBrowse->where('dyid='.$dy['id'].' and uid='.$uid)->count();
      if($c==0){
        //没有浏览过，添加记录
        $DynamicBrowse->dyid = $dyid;
        $DynamicBrowse->uid = $uid;
        $DynamicBrowse->addtime = time();
        $DynamicBrowse->add();
      }
      $res['ifbrowse'] = '1';
      $t = $DynamicCollect->where('dyid='.$dy['id'].' and uid='.$uid)->count();
      if($t>0){
        $res['ifcollect'] = '1';
      }else{
        $res['ifcollect'] = '0';
      }
    }else{
      $res['ifbrowse'] = '0';
      $res['ifcollect'] = '0';
    }
    $res['browsenum'] = $DynamicBrowse->where('dyid='.$dy['id'])->count();
    $res['collectnum'] = $DynamicCollect->where('dyid='.$dy['id'])->count();
    if($iPageItem){
      $dc = $DynamicComment->where('dyid='.$dy['id'])->page($iPageIndex+1,$iPageItem)->select();
    }else{
      $dc = $DynamicComment->where('dyid='.$dy['id'])->select();
    }
    $res['comment'] = array();
    foreach ($dc as $key => $value) {
      $res['comment'][$key]['commentid'] = $value['id'];
      $res['comment'][$key]['uid'] = $value['uid'];
      $user = $Users->where('uid='.$value['uid'])->field('nickname,headpic')->find();
      $res['comment'][$key]['realname'] = $user['nickname'];
      $res['comment'][$key]['headpic'] = $ImageUrl.$user['headpic'];
      $res['comment'][$key]['content'] = $value['content'];
      $res['comment'][$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
      if($value['touid']){
        $res['comment'][$key]['torealname'] = $Users->where('uid='.$value['touid'])->getField('nickname');
      }else{
        $res['comment'][$key]['torealname'] = "";
      }
    }
    ApiResult('200',$res,'');
  }
  //评论e联心
  public function ideacomment(){
    $uid = I('uid');
    $dyid = I('dyid');
    $content = I('content');
    $touid = I('touid');
    $DynamicComment = D('DynamicComment');
    $DynamicComment->uid = $uid;
    $DynamicComment->dyid = $dyid;
    $DynamicComment->content = $content;
    $DynamicComment->touid = $touid?$touid:"";
    $DynamicComment->addtime = time();
    $commentid = $DynamicComment->add();
    if($commentid)
      ApiResult('200','','评论成功');
  }
  //收藏e联心
  public function ideacollect(){
    $uid = I('uid');
    $dyid = I('dyid');
    $type = I('type');
    $DynamicCollect = D('DynamicCollect');
    $Dynamic = D('Dynamic');
    $dy = $Dynamic->where('id='.$dyid)->find();
    if(empty($dy))
      ApiResult('202','','动态去火星了~');
    if($type == '1'){
      $t = $DynamicCollect->where('dyid='.$dyid.' and uid='.$uid)->count();
      if($t>0)
        ApiResult('201','','已经收藏了你心里没数吗？');
      $DynamicCollect->dyid = $dyid;
      $DynamicCollect->uid = $uid;
      $DynamicCollect->addtime = time();
      $DynamicCollect->add();
      ApiResult('200','','收藏成功');
    }else{
      $t = $DynamicCollect->where('dyid='.$dyid.' and uid='.$uid)->count();
      if($t==0)
        ApiResult('203','','没有收藏过');
      $DynamicCollect->where('dyid='.$dyid.' and uid='.$uid)->delete();
      ApiResult('200','','取消成功');
    }
  }
  /**
   * e分享
   */
   public function eshare(){
     $Eshare = D('Eshare');
     $ImageUrl = C('ImageUrl');
     $elist = $Eshare->where('1=1')->field('id,name,surl,img')->select();
     if(empty($elist))
       ApiResult('201','','没有');
      foreach ($elist as $key => $value) {
        $elist[$key]['img'] = $value['img']?$ImageUrl.$value['img']:"";
      }
      ApiResult('200',$elist,'');
   }
}
