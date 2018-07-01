<?php
namespace Admin\Controller;
use Think\Controller;
class RemouldController extends Controller {
    /**
    *党建百宝箱列表
    */
    public function boxlist(){
      $nowuid = session('uid');
      $Box = D('Box');
      $blist = $Box->select();
      foreach ($blist as $key => $value) {
        $blist[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
        switch ($value['cateid']) {
          case '1':
            $blist[$key]['cate'] = '党务制度';
            break;
          case '2':
            $blist[$key]['cate'] = '党章党规';
            break;
          case '3':
            $blist[$key]['cate'] = '应知应会';
            break;
        }
        if($nowuid == $value['uid']){
  				$blist[$key]['candel'] = '1';
  			}else{
  				$blist[$key]['candel'] = '0';
  			}
      }
      $this->assign('blist',$blist);
      $this->display('boxlist');
    }
    /**
    *百宝箱内容
    */
    public function getContent(){
      $id = $_GET['id'];
  		$Box = D('Box');
  		$ninfo = $Box->where('id='.$id)->field('content')->find();
  		$this->assign('ninfo',$ninfo);
  		$this->display('boxcontent');
    }
    /**
    *删除百宝箱
    */
    public function delBox(){
      $id  = $_POST['id'];
  		$Box = D('Box');
      //$BoxBrowse = D('BoxBrowse');
      //$BoxCollect = D('BoxCollect');
  		$re = $Box->where('id='.$id)->delete();
      //$BoxBrowse->where('bid='.$id)->delete();
      //$BoxCollect->where('bid='.$id)->delete();
  		if($re){
  			$data['msg'] = 'success';
  		}else{
  			$data['msg'] = 'fail';
  		}
  		$this->ajaxReturn($data);
    }
    /**
    *发布新Box
    */
    public function addBox(){
      $Box = D('Box');
      $Box->uid = session('uid');
      $Box->author = $_POST['author'];
      $Box->cateid = $_POST['cateid'];
      $Box->title = $_POST['title'];
      $Box->content = $_POST['content'];
      $Box->addtime = time();
      $res = $Box->add();
      if($res)
  			$this->boxlist();
    }
    //
    public function EditBox(){
      $Box = D('Box');
      $Box->author = $_POST['author'];
      $Box->title = $_POST['title'];
      if($_POST['description'])
        $Box->content = $_POST['description'];
      $Box->where('id='.$_POST['bid'])->save();
      $date['code'] = '200';
      $this->ajaxReturn($date);
    }
    /*
    *微课堂列表
    */
    public function classlist(){
      $nowuid = session('uid');
      $Class = D('Class');
      $ClassComment = D('ClassComment');
      $ClassBrowse = D('ClassBrowse');
      $ClassCollect = D('ClassCollect');
      $clist = $Class->select();
      foreach ($clist as $key => $value) {
        $clist[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
        switch ($value['cateid']) {
          case '2':
            $clist[$key]['cate'] = '系列讲话';
            break;
          case '3':
            $clist[$key]['cate'] = '党章党规';
            break;
          case '4':
            $clist[$key]['cate'] = '各级精神';
            break;
          case '5':
            $clist[$key]['cate'] = '微党课';
            break;
          case '6':
            $clist[$key]['cate'] = '党建动漫';
            break;
          case '7':
            $clist[$key]['cate'] = '越乡先锋';
            break;
        }
        $clist[$key]['commentnum'] = $ClassComment->where('mcid='.$value['id'])->count();
        // $clist[$key]['browsenum'] = $ClassBrowse->where('mcid='.$value['id'])->count();
        // $clist[$key]['collectnum'] = $ClassCollect->where('mcid='.$value['id'])->count();
        if($nowuid == $value['uid']){
  				$clist[$key]['candel'] = '1';
  			}else{
  				$clist[$key]['candel'] = '0';
  			}
      }
      $this->assign('clist',$clist);
      $this->display('classlist');
    }
    /**
    *微课堂内容
    */
    public function getClassContent(){
      $id = $_GET['id'];
  		$Class = D('Class');
      $ImageUrl = C('ImageUrl');
  		$ninfo = $Class->where('id='.$id)->field('videopath,content')->find();
      if($ninfo['videopath']){
        $ninfo['videopath'] = $ImageUrl.$ninfo['videopath'];
        $ninfo['hasvideo'] = '1';
      }else{
        $ninfo['hasvideo'] = '0';
      }
  		$this->assign('ninfo',$ninfo);
  		$this->display('classcontent');
    }
    /**
    *发布新Class
    */
    public function addClass(){
      $Class = D('Class');
      $Admins = D('Admins');

  		$account = session('admininfo');
  		$query['account'] = $account;
  		$uid = $Admins->where($query)->getField('uid');

  		if($_FILES['video']['name']){//有传头像
  			$savePath = 'Upload/videofile/';
  			$info = UploadVideoFile($savePath);
  			$videopath = '/'.$info['video']['savepath'].$info['video']['savename'];
  			$Class->videopath = $videopath;
  		}
      $file = $_FILES["photo"];
      if(!empty($file["name"])) {
        $thumbimage_url = UploadImageByMySelf('class',$file);
        $Class->img = $thumbimage_url;
      }
      $Class->uid = $uid;
      $Class->author = $_POST['author'];
      $Class->cateid = $_POST['cateid'];
      $Class->title = $_POST['title'];
      $Class->content = $_POST['content'];
      if(is_numeric($_POST['point']))
        $Class->point = $_POST['point'];
      $Class->addtime = time();
      $res = $Class->add();
      if($res)
  			$this->classlist();
    }
    /**
    *删除微课堂
    */
    public function delClass(){
      $id  = $_POST['id'];
  		$Class = D('Class');
  		$re = $Class->where('id='.$id)->delete();
      //$ClassBrowse->where('bid='.$id)->delete();
      //$ClassCollect->where('bid='.$id)->delete();
  		if($re){
  			$data['msg'] = 'success';
  		}else{
  			$data['msg'] = 'fail';
  		}
  		$this->ajaxReturn($data);
    }
    /**
    *编辑
    */
    public function EditClass(){
      $Class = D('Class');
      $classid = $_POST['classid'];
      $file = $_FILES["photo"];
      $thumbimage_url = "";
      if(!empty($file["name"])) {
        $thumbimage_url = UploadImageByMySelf('class',$file);
        $Class->img = $thumbimage_url;
      }
      if($_POST['description'])
        $Class->content = $_POST['description'];
      $Class->title = $_POST['title'];
      $Class->author = $_POST['author'];
      $Class->point = $_POST['point'];
      $Class->addtime = strtotime($_POST['addtime']);
      $Class->where('id='.$classid)->save();
      $date['code'] = '200';
      $this->ajaxReturn($date);
    }
    /**
    *评论列表
    */
    public function getCommentList(){
      if(isset($_GET['id']) && is_numeric($_GET['id']) && $_GET['id']>0){
        $Users = D('Users');
        $ClassComment = D('ClassComment');
        $clist = $ClassComment->where('mcid='.$_GET['id'])->select();
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
      $ClassComment = D('ClassComment');
      $ClassComment->where('id='.$id)->delete();
      $date['code'] = '200';
      $this->ajaxReturn($date);
    }

    /**
    *零距离会场
    */
    public function zero(){
      $Meeting = D('Meeting');
      $MeetingJoin = D('MeetingJoin');
      $Users = D('Users');
      $zlist = $Meeting->select();
      foreach ($zlist as $key => $value) {
        $zlist[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
        $zlist[$key]['begin'] = date('Y-m-d H:i',$value['begin']);
        $zlist[$key]['end'] = date('Y-m-d H:i',$value['end']);
        $user = $Users->where('uid='.$value['uid'])->field('realname,party,branch')->find();
        $zlist[$key]['author'] = $user['realname'];
        $zlist[$key]['party'] = $user['party'];
        $zlist[$key]['branch'] = $user['branch'];
        $zlist[$key]['num'] = $MeetingJoin->where('mtid='.$value['id'])->count();
      }
      $this->assign('zlist',$zlist);
      $this->display('zerolist');
    }
    /**
    *聊天记录
    */
    public function getHistory(){
      if(isset($_GET['id']) && is_numeric($_GET['id']) && $_GET['id']>0){
        $mtid = $_GET['id'];
        $MeetingHistory = D('MeetingHistory');
        $hlist = $MeetingHistory->alias('mh')->join('lct_users u ON mh.fromuid=u.uid')
        ->field('u.realname,u.party,u.branch,mh.id,mh.content,mh.datetime,mh.source')
        ->where('mh.groupid='.$mtid.' and targetType=3')->select();
        foreach ($hlist as $key => $value) {
          if(!$value['content'])
            $hlist[$key]['content'] = '表情';
        }
        $this->assign('hlist',$hlist);
        $this->display('history');
      }else{
        $this->error('获取错误');
      }
    }
    /**
    *参与会议人员
    */
    public function getMeetingJoinUser(){
      $mtid = $_GET['id'];
      $MeetingJoin = D('MeetingJoin');
      $join = $MeetingJoin->alias('mj')->join('lct_users u ON u.uid=mj.uid')
      ->field('u.realname,u.party,u.branch,u.phone,mj.ifsign')->where('mj.mtid='.$mtid)->select();
      $this->assign('join',$join);
      $this->display('joinlist');
    }

    /**
    *获取需要编辑的内容
    */
    public function getClassContentToEdit(){
      $id = $_POST['classid'];
  		$Class = D('Class');
  		$content = $Class->where('id='.$id)->getField('content');
      $data['content'] = $content;
      $this->ajaxReturn($data);
    }
    /**
    *
    */
    public function EditBoxCount(){
      $Box = D('Box');
      $Box->browse = $_POST['browse'];
      $Box->collect = $_POST['collect'];
      $Box->where('id='.$_POST['bid'])->save();
      $date['code'] = '200';
      $this->ajaxReturn($date);
    }

    public function EditClassCount(){
      $Class = D('Class');
      $classid = $_POST['classid'];
      $Class->browse = $_POST['browse'];
      $Class->collect = $_POST['collect'];
      $Class->where('id='.$classid)->save();
      $date['code'] = '200';
      $this->ajaxReturn($date);
    }
}
