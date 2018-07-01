<?php
namespace Api\Controller;
use Think\Controller;
class RemouldController extends Controller {
    /**
    *党建百宝箱 cateid  1-党务制度;2-党章党规;3-应知应会
    */
    public function boxlist(){
      $cateid = I('cateid');
      $uid = I('get.uid','');
      $iPageItem = I('iPageItem');
      $iPageIndex = I('iPageIndex');
      $Box = D('Box');
      $BoxBrowse = D('BoxBrowse');
      $BoxCollect = D('BoxCollect');
      if($iPageItem){
        $binfo = $Box->where('cateid='.$cateid)->page($iPageIndex+1,$iPageItem)->order('addtime desc')->select();
      }else{
        $binfo = $Box->where('cateid='.$cateid)->order('addtime desc')->select();
      }
      $res = array();
      if(empty($binfo))
        ApiResult('201',$res,'没有该内容');
      foreach ($binfo as $key => $value) {
        $res[$key]['bid'] = $value['id'];
        $res[$key]['title'] = $value['title'];
        $res[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
        // $res[$key]['browsenum'] = $BoxBrowse->where('bid='.$value['id'])->count();
        // $res[$key]['collectnum'] = $BoxCollect->where('bid='.$value['id'])->count();
        $res[$key]['browsenum'] = $value['browse'];
        $res[$key]['collectnum'] = $value['collect'];
        $res[$key]['ifcollect'] = '0';
        $res[$key]['ifbrowse'] = '0';
        if($uid && is_numeric($uid) && $uid>0){
          $t = $BoxCollect->where('bid='.$value['id'].' and uid='.$uid)->count();
          if($t>0){
            $res[$key]['ifcollect'] = '1';
          }
          $bnum = $BoxBrowse->where('bid='.$value['id'].' and uid='.$uid)->count();
          if($bnum>0){
            $res[$key]['ifbrowse'] = '1';
          }
        }
      }
      ApiResult('200',$res,'');
    }
    /**
    *党建百宝箱 cateid  1-党务制度;2-党章党规;3-应知应会
    */
    public function box(){
      $bid = I('bid');
      $uid = I('get.uid','');
      $Box = D('Box');
      $BoxBrowse = D('BoxBrowse');
      $BoxCollect = D('BoxCollect');
      $binfo = $Box->where('id='.$bid)->find();
      $res = array();
      if(empty($binfo))
        ApiResult('201',$res,'该内容已经消失了');
      $res['title'] = $binfo['title'];
      $res['content'] = $binfo['content'];
      $res['contentUrl'] = APP_URL."/Public/api/share/detail.html?type=box&id=".$bid;
      $res['addtime'] = date('Y-m-d H:i:s',$binfo['addtime']);
      $res['ifbrowse'] = '1';
      $res['ifcollect'] = '0';
      if($uid && is_numeric($uid) && $uid>0){
        $bnum = $BoxBrowse->where('bid='.$bid.' and uid='.$uid)->count();
        if(!$bnum){
          $BoxBrowse->bid = $bid;
          $BoxBrowse->uid = $uid;
          $BoxBrowse->addtime = time();
          $BoxBrowse->add();
        }
        $t = $BoxCollect->where('bid='.$bid.' and uid='.$uid)->count();
        if($t>0){
          $res['ifcollect'] = '1';
        }
      }
      $Box->where('id='.$bid)->setInc('browse',1);
      // $res['collectnum'] = $BoxCollect->where('bid='.$bid)->count();
      // $res['browsenum'] = $BoxBrowse->where('bid='.$bid)->count();
      $res['collectnum'] = $binfo['collect'];
      $res['browsenum'] = $binfo['browse'];
      ApiResult('200',$res,'');
    }
    /**
    *收藏/取消收藏百宝箱
    */
    public function boxcollect(){
      $uid = I('uid');
      $bid = I('bid');
      $type = I('type');
      $BoxCollect = D('BoxCollect');
      $Box = D('Box');
      $bo = $Box->where('id='.$bid)->find();
      if(empty($bo))
        ApiResult('202','','该内容去火星了~');
      $t = $BoxCollect->where('bid='.$bid.' and uid='.$uid)->count();
      if($type == '1'){
        if($t>0)
          ApiResult('201','','已经收藏了你心里没数吗？');
        $BoxCollect->bid = $bid;
        $BoxCollect->uid = $uid;
        $BoxCollect->addtime = time();
        $BoxCollect->add();
        $Box->where('id='.$bid)->setInc('collect',1);
        ApiResult('200','','收藏成功');
      }else{
        if($t==0)
          ApiResult('203','','没有收藏过');
        $BoxCollect->where('bid='.$bid.' and uid='.$uid)->delete();
        ApiResult('200','','取消成功');
      }
    }
    /**
    *零距离会场
    */
    public function zero(){
      $uid = I('uid');
      $MeetingJoin = D('MeetingJoin');
      $Users = D('Users');
      $ImageUrl = C('ImageUrl');
      $meetings = $MeetingJoin->alias('mj')->join('lct_meeting m ON mj.mtid = m.id')
      ->where('mj.uid='.$uid)
      ->field('m.id,m.mname,m.begin,m.end,m.uids')->order('m.addtime desc')->select();
      if(empty($meetings))
        ApiResult('201',$meetings,'没有会议');
      $res = array();
      foreach ($meetings as $key => $value) {
        $begin = $value['begin'];
    		$end = $value['end'];
    		if(time()<$begin){
    			$res[$key]['status'] = '未开始';
    		}else if(time()>$end){
    			$res[$key]['status'] = '已结束';
    		}else{
    			$res[$key]['status'] = '进行中';
    		}
        $res[$key]['mtid'] = $value['id'];
        $res[$key]['mname'] = $value['mname'];
        $res[$key]['begin'] = date('Y-m-d H:i',$value['begin']);
        $res[$key]['end'] = date('Y-m-d H:i',$value['end']);
        $uidArr = explode('|',rtrim($value['uids'],'|'));
        $map['uid']  = array('IN',$uidArr);
    		$userinfo = $Users->where($map)->field('uid,realname,phone,headpic')->select();
        foreach ($userinfo as $k => $v) {
    			$res[$key]['users'][$k]['uid'] = $v['uid'];
    			$res[$key]['users'][$k]['realname'] = $v['realname'];
    			$res[$key]['users'][$k]['phone'] = $v['phone'];
    			$res[$key]['users'][$k]['headpic'] = $ImageUrl.$v['headpic'];
    		}
      }
      ApiResult('200',$res,'');
    }
    /**
    *随身微课堂列表
    */
    public function microclasslist(){
      // ApiResult('201',array(),'没有该内容');
      $type = I('type');
      $search = I('post.search','');
      $uid = I('post.uid','');
      $iPageItem = I('iPageItem');
      $iPageIndex = I('iPageIndex');
      $query = '1=1';
      if($search){
        $query .= " and title like '%".$search."%' ";
      }else if($type != '1'){
        $query .= ' and cateid='.$type;
      }
      $Class = D('Class');
      $ClassBrowse = D('ClassBrowse');
      $ClassCollect = D('ClassCollect');
      $ClassComment = D('ClassComment');
      $ImageUrl = C('ImageUrl');
      if($iPageItem){
        if($type == '1' && !$search){
          $cinfo = $Class->where('cateid>4')->field('id,title,videopath,addtime,img,browse,collect')->order('addtime desc')->limit(4)->select();
        }else{
          $cinfo = $Class->where($query)->page($iPageIndex+1,$iPageItem)->field('id,title,videopath,addtime,img,browse,collect')->order('addtime desc')->select();
        }
      }else{
        if($type == '1' && !$search){
          $cinfo = $Class->where('cateid>4')->field('id,title,videopath,addtime,img,browse,collect')->order('addtime desc')->limit(4)->select();
        }else{
          $cinfo = $Class->where($query)->field('id,title,videopath,addtime,img,browse,collect')->order('addtime desc')->select();
        }
      }
      $res = array();
      foreach ($cinfo as $key => $value) {
        $res[$key]['mcid'] = $value['id'];
        $res[$key]['title'] = $value['title'];
        $res[$key]['video'] = $value['videopath']?$ImageUrl.$value['videopath']:"";
        $res[$key]['img'] = $value['img']?$ImageUrl.$value['img']:"";
        $res[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
        // $res[$key]['browsenum'] = $ClassBrowse->where('mcid='.$value['id'])->count();
        // $res[$key]['collectnum'] = $ClassCollect->where('mcid='.$value['id'])->count();
        $res[$key]['browsenum'] = $value['browse'];
        $res[$key]['collectnum'] = $value['collect'];
        $res[$key]['commentnum'] = $ClassComment->where('mcid='.$value['id'])->count();
        $res[$key]['ifbrowse'] = '0';
        $res[$key]['ifcollect'] = '0';
        if($uid && is_numeric($uid) && $uid>0){
          $t = $ClassCollect->where('mcid='.$value['id'].' and uid='.$uid)->count();
          if($t>0){
            $res[$key]['ifcollect'] = '1';
          }
          $bnum = $ClassBrowse->where('mcid='.$value['id'].' and uid='.$uid)->count();
          if($bnum>0){
            $res[$key]['ifbrowse'] = '1';
          }
        }
      }
      if(empty($res)){
        ApiResult('201',$res,'没有该内容');
      }else{
        ApiResult('200',$res,'');
      }
    }
    /**
    *随身微课堂详情
    */
    public function microclass(){
      $uid = I('get.uid','');
      $mcid = I('mcid');
      $iPageItem = I('iPageItem');
      $iPageIndex = I('iPageIndex');
      $Class = D('Class');
      $ClassBrowse = D('ClassBrowse');
      $ClassCollect = D('ClassCollect');
      $ClassComment = D('ClassComment');
      $ImageUrl = C('ImageUrl');

      $cinfo = $Class->where('id='.$mcid)->find();
      $res = array();
      if(empty($cinfo))
        ApiResult('201','','该内容去火星了~');
      $res['author'] = $cinfo['author'];
      $res['mcid'] = $cinfo['id'];
      $res['title'] = $cinfo['title'];
      $res['content'] = $cinfo['content'];
      $res['contentUrl'] = APP_URL."/Public/api/share/detail.html?type=microclass&id=".$mcid;
      $res['video'] = $cinfo['videopath']?$ImageUrl.$cinfo['videopath']:"";
      $res['img'] = $cinfo['img']?$ImageUrl.$cinfo['img']:"";
      $res['addtime'] = date('Y-m-d H:i:s',$cinfo['addtime']);
      $res['ifbrowse'] = '1';
      $res['ifcollect'] = '0';
      //如果不是游客，改变浏览记录
      if($uid && is_numeric($uid) && $uid>0){
        $t = $ClassCollect->where('mcid='.$cinfo['id'].' and uid='.$uid)->count();
        if($t>0){
          $res['ifcollect'] = '1';
        }
        $bnum = $ClassBrowse->where('mcid='.$cinfo['id'].' and uid='.$uid)->count();
        if(!$bnum){
          $ClassBrowse->mcid = $mcid;
          $ClassBrowse->uid = $uid;
          $ClassBrowse->addtime = time();
          $ClassBrowse->add();
          if($cinfo['point']>0){
            $Point = D('Point');
            $msg = '学习课程(id='.$mcid.')增加';
            $point = $cinfo['point'];
            setPointLog($uid,$msg,$point);
            $Point->where('uid='.$uid)->setInc('sum',$point); // 用户的积分增加
          }
        }
      }
      $Class->where('id='.$mcid)->setInc('browse',1);
      // $res['browsenum'] = $ClassBrowse->where('mcid='.$cinfo['id'])->count();
      // $res['collectnum'] = $ClassCollect->where('mcid='.$cinfo['id'])->count();
      $res['browsenum'] = $cinfo['browse'];
      $res['collectnum'] = $cinfo['collect'];
      $res['commentnum'] = $ClassComment->where('mcid='.$cinfo['id'])->count();
      $res['comment'] = array();
      if($iPageItem){
        $cc = $ClassComment->where('mcid='.$cinfo['id'])->page($iPageIndex+1,$iPageItem)->select();
      }else{
        $cc = $ClassComment->where('mcid='.$cinfo['id'])->select();
      }
      $Users = D('Users');
      foreach ($cc as $key => $value) {
        $res['comment'][$key]['commentid'] = $value['id'];
        $res['comment'][$key]['uid'] = $value['uid'];
        $user = $Users->where('uid='.$value['uid'])->field('nickname,headpic')->find();
        $res['comment'][$key]['realname'] = $user['nickname'];
        $res['comment'][$key]['headpic'] = $ImageUrl.$user['headpic'];
        $res['comment'][$key]['content'] = $value['content'];
        $res['comment'][$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
        if($value['touid']){
          $nickname = $Users->where('uid='.$value['touid'])->getField('nickname');
          $res['comment'][$key]['torealname'] = $nickname?$nickname:"";
        }else{
          $res['comment'][$key]['torealname'] = "";
        }
      }
      ApiResult('200',$res,'');
    }
    /**
    *收藏/取消收藏微课堂
    */
    public function microclasscollect(){
      $uid = I('uid');
      $mcid = I('mcid');
      $type = I('type');
      $ClassCollect = D('ClassCollect');
      $Class = D('Class');
      $bo = $Class->where('id='.$mcid)->find();
      if(empty($bo))
        ApiResult('202','','该内容去火星了~');
      $t = $ClassCollect->where('mcid='.$mcid.' and uid='.$uid)->count();
      if($type == '1'){
        if($t>0)
          ApiResult('201','','已经收藏了你心里没数吗？');
        $ClassCollect->mcid = $mcid;
        $ClassCollect->uid = $uid;
        $ClassCollect->addtime = time();
        $ClassCollect->add();
        $Class->where('id='.$mcid)->setInc('collect',1);
        ApiResult('200','','收藏成功');
      }else{
        if($t==0)
          ApiResult('203','','没有收藏过');
        $ClassCollect->where('mcid='.$mcid.' and uid='.$uid)->delete();
        ApiResult('200','','取消成功');
      }
    }
    /**
    *评论微课堂
    */
    public function microclasscomment(){
      $uid = I('uid');
      $mcid = I('mcid');
      $content = I('content');
      $touid = I('post.touid','');
      $Class = D('Class');
      $bo = $Class->where('id='.$mcid)->find();
      if(empty($bo))
        ApiResult('202','','该内容去火星了~');
      $ClassComment = D('ClassComment');
      $ClassComment->mcid = $mcid;
      $ClassComment->uid = $uid;
      $ClassComment->content = $content;
      $ClassComment->addtime = time();
      if($touid)
        $ClassComment->touid = $touid;
      $res = $ClassComment->add();
      if($res){
        ApiResult('200','','评论成功');
      }else{
        ApiResult('201','','评论失败');
      }
    }

    public function addPointDataBase(){
      $uinfo = D('Users')->where('1=1')->field('uid')->select();
      foreach ($uinfo as $key => $value) {
        $arr = D('Point')->where('uid='.$value['uid'])->find();
        if(empty($arr)){
          $P = D('Point');
    			 $P->uid = $value['uid'];
    			 $P->add();
        }
      }
    }
}
