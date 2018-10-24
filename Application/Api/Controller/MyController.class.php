<?php
namespace Api\Controller;
use Think\Controller;
class MyController extends Controller {
    /**
    *联系人
    *@param permission-1能看所有party；2能看所在party所有branch；默认3只能在所在branch
    *@return
    */
    public function contacts(){
      $uid = I('uid');
      $iPageItem = I('iPageItem');
      $iPageIndex = I('iPageIndex');
      $search = trim(I('post.search',''));
      $Users = D('Users');
      $ImageUrl = C('ImageUrl');
      $user = $Users->where('uid='.$uid)->field('permission,orgid,party,branch')->find();//获取用户信息
      $permission = $user['permission'];//获取用户权限
      $query = "ifdelete=0";
      if($permission == '2'){//所在party所有branch
        $query .= " and party='".$user['party']."'";
      }else if($permission == '3'){//只能所在branch
        $query .= " and branch='".$user['branch']."'";
      }
      if($search){
        $query .= " and (realname like '%".$search."%' or branch like '%".$search."%' )";
      }
      if($iPageItem){
        $uinfo = $Users->where($query)->page($iPageIndex+1,$iPageItem)->field('uid,phone,realname,headpic,party,branch')->select();
      }else{
        $uinfo = $Users->where($query)->field('uid,phone,realname,headpic,party,branch')->select();
      }
      $res = array();
      if(empty($uinfo))
        ApiResult('201',$res,'没有联系人');
      foreach ($uinfo as $key => $value) {
        $res[$key]['uid'] = $value['uid'];
        $res[$key]['phone'] = $value['phone'];
        $res[$key]['realname'] = $value['realname'];
        $res[$key]['headpic'] = $ImageUrl.$value['headpic'];
        $res[$key]['party'] = $value['party'];
        $res[$key]['branch'] = $value['branch'];
      }
      ApiResult('200',$res,'');
    }
    /**
    *我的分享
    */
    public function share(){
      $uid = I('uid');
      $type = I('type');
      if($type == '1'){//金点子
        $Dynamic = D('Dynamic');
        $dlist = $Dynamic->where('uid='.$uid.' and cateid=1 and ifshow=1')->order('addtime desc')->select();
        $res = array();
        if(empty($dlist))
          ApiResult('201',$res,'没有~');
        $Users = D('Users');
        $DynamicComment = D('DynamicComment');
        $DynamicBrowse = D('DynamicBrowse');
        $DynamicCollect = D('DynamicCollect');
        $ImageUrl = C('ImageUrl');
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
          if($uid && is_numeric($uid) && $uid>0){
            $c = $DynamicBrowse->where('dyid='.$value['id'].' and uid='.$uid)->count();
            if($c>0){
              $res[$key]['ifbrowse'] = '1';
            }
            $t = $DynamicCollect->where('dyid='.$value['id'].' and uid='.$uid)->count();
            if($t>0){
              $res[$key]['ifcollect'] = '1';
            }
          }
        }
        ApiResult('200',$res,'');
      }else{//回复评论
        $DynamicComment = D('DynamicComment');
        $dlist = $DynamicComment->alias('dc')->join('lct_dynamic d ON d.id=dc.dyid')
        ->field('d.uid,d.title,dc.content,dc.addtime,dc.dyid')
        ->where('dc.uid='.$uid)->select();
        $res = array();
        if(empty($dlist))
          ApiResult('201',$res,'没有~');
        $Users = D('Users');
        $ImageUrl = C('ImageUrl');
        $commentuser = $Users->where('uid='.$uid)->field('nickname,headpic')->find();
        $nickname = $commentuser['nickname'];
        $headpic = $ImageUrl.$commentuser['headpic'];
        foreach ($dlist as $key => $value) {
          $author = $Users->where('uid='.$value['uid'])->field('nickname,headpic')->find();
          $res[$key]['nickname'] = $nickname;
          $res[$key]['headpic'] = $headpic;
          $res[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
          $res[$key]['content'] = $value['content'];
          $res[$key]['anickname'] = $author['nickname'];
          $res[$key]['aheadpic'] = $ImageUrl.$author['headpic'];
          $res[$key]['title'] = $value['title'];
        }
        ApiResult('200',$res,'wait');
      }

    }
    /**
    *我的收藏
    *@param type:收藏类型。1-e联心;2-党建动态;3-公告;4-百宝箱;5-微课堂
    */
    public function collect(){
      $uid = I('uid');
      $type = I('type');
      $ImageUrl = C('ImageUrl');
      $res = array();
      switch ($type) {
        case '1':
          $Dynamic = D('Dynamic');
          $DynamicCollect = D('DynamicCollect');
          $DynamicComment = D('DynamicComment');
          $DynamicBrowse = D('DynamicBrowse');
          $Users = D('Users');//e联心发布者是用户
          $eheart = $DynamicCollect->alias('dc')->join('lct_dynamic d ON dc.dyid=d.id')
          ->field('d.title,dc.dyid,d.uid,d.addtime,d.cateid,d.content,d.img')
          ->where('d.cateid != 0 and dc.uid='.$uid)->select();
          foreach ($eheart as $key => $value) {
            $res[$key]['dyid'] = $value['dyid'];
            $author = $Users->where('uid='.$value['uid'])->field('nickname,headpic')->find();
            $res[$key]['realname'] = $author['nickname'];
            $res[$key]['headpic'] = $ImageUrl.$author['headpic'];
            $res[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
            $res[$key]['title'] = $value['title'];
            $res[$key]['content'] = $value['content'];
            $res[$key]['commentnum'] = $DynamicComment->where('dyid='.$value['dyid'])->count();
            $res[$key]['browsenum'] = $DynamicBrowse->where('dyid='.$value['dyid'])->count();
            $res[$key]['collectnum'] = $DynamicCollect->where('dyid='.$value['dyid'])->count();
            $res[$key]['ifcollect'] = '1';
            $res[$key]['ifbrowse'] = '1';
            $res[$key]['img'] = array();
            $img_arr = json_decode($value['img'],true);
            if(!empty($img_arr)){
              foreach ($img_arr as $kk => $vv) {
                $res[$key]['img'][$kk]['imgurl'] = $ImageUrl.$vv;
              }
            }
          }
          break;
        case '2':
          $Dynamic = D('Dynamic');
          $DynamicCollect = D('DynamicCollect');
          $DynamicComment = D('DynamicComment');
          $DynamicBrowse = D('DynamicBrowse');
          $Admins = D('Admins');//党建动态发布者是管理员
          $dyinfo = $DynamicCollect->alias('dc')->join('lct_dynamic d ON dc.dyid=d.id')
          ->field('d.title,dc.dyid,d.uid,d.addtime,d.cateid,d.content,d.img')
          ->where('d.cateid = 0 and dc.uid='.$uid)->select();
          foreach ($dyinfo as $key => $value) {
            $res[$key]['dyid'] = $value['dyid'];
            $author = $Admins->where('uid='.$value['uid'])->field('nickname')->find();
            $res[$key]['realname'] = $author['nickname'];
            $res[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
            $res[$key]['title'] = $value['title'];
            $res[$key]['content'] = $value['content'];
            $res[$key]['commentnum'] = $DynamicComment->where('dyid='.$value['dyid'])->count();
            $res[$key]['browsenum'] = $DynamicBrowse->where('dyid='.$value['dyid'])->count();
            $res[$key]['collectnum'] = $DynamicCollect->where('dyid='.$value['dyid'])->count();
            $res[$key]['ifcollect'] = '1';
            $res[$key]['ifbrowse'] = '1';
            $res[$key]['img'] = array();
            $img_arr = json_decode($value['img'],true);
            if(!empty($img_arr)){
              foreach ($img_arr as $kk => $vv) {
                $res[$key]['img'][$kk]['imgurl'] = $ImageUrl.$vv;
              }
            }
          }
          break;
        case '3':
          $Notice = D('Notice');
          $NoticeCollect = D('NoticeCollect');
          $ninfo = $NoticeCollect->alias('nc')->join('lct_notice n ON nc.nid=n.id')
          ->field('n.id,n.title,n.addtime')
          ->where('nc.uid='.$uid)->select();
          foreach ($ninfo as $key => $value) {
            $res[$key]['nid'] = $value['id'];
            $res[$key]['title'] = $value['title'];
            $res[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
            $res[$key]['ifread'] = '1';
          }
          break;
        case '4':
          $Box = D('Box');
          $BoxBrowse = D('BoxBrowse');
          $BoxCollect = D('BoxCollect');
          $binfo = $BoxCollect->alias('bc')->join('lct_box b ON bc.bid=b.id')
          ->field('b.id,b.title,b.addtime')
          ->where('bc.uid='.$uid)->select();
          foreach ($binfo as $key => $value) {
            $res[$key]['bid'] = $value['id'];
            $res[$key]['title'] = $value['title'];
            $res[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
            $res[$key]['browsenum'] = $BoxBrowse->where('bid='.$value['id'])->count();
            $res[$key]['collectnum'] = $BoxCollect->where('bid='.$value['id'])->count();
            $res[$key]['ifcollect'] = '1';
            $res[$key]['ifbrowse'] = '1';
          }
          break;
        case '5'://微课堂暂定
          $Class = D('Class');
          $ClassBrowse = D('ClassBrowse');
          $ClassCollect = D('ClassCollect');
          $ClassComment = D('ClassComment');
          $cinfo = $ClassCollect->alias('cc')->join('lct_class c ON cc.mcid=c.id')
          ->field('c.id,c.title,c.addtime,c.videopath,c.img')
          ->where('cc.uid='.$uid)->select();
          foreach ($cinfo as $key => $value) {
            $res[$key]['mcid'] = $value['id'];
            $res[$key]['title'] = $value['title'];
            $res[$key]['video'] = $value['videopath']?$ImageUrl.$value['videopath']:"";
            $res[$key]['img'] = $value['img']?$ImageUrl.$value['img']:"";
            $res[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
            $res[$key]['browsenum'] = $ClassBrowse->where('mcid='.$value['id'])->count();
            $res[$key]['collectnum'] = $ClassCollect->where('mcid='.$value['id'])->count();
            $res[$key]['commentnum'] = $ClassComment->where('mcid='.$value['id'])->count();
            $res[$key]['ifcollect'] = '1';
            $res[$key]['ifbrowse'] = '1';
          }
          break;
      }
      if(empty($res))
        ApiResult('201','','该收藏为空');
      ApiResult('200',$res,'');
    }
    /**
    *我的学习
    */
    public function study(){
      $uid = I('uid');
      $Class = D('Class');
      $ClassBrowse = D('ClassBrowse');
      $ClassCollect = D('ClassCollect');
      $ClassComment = D('ClassComment');
      $ImageUrl = C('ImageUrl');
      $cinfo = $ClassBrowse->alias('cb')->join('lct_class c ON cb.mcid=c.id')
      ->field('c.id,c.title,c.addtime,c.videopath,c.img')
      ->where('cb.uid='.$uid)->select();
      if(empty($cinfo))
        ApiResult('201','','你没有已学课程');
      foreach ($cinfo as $key => $value) {
        $res[$key]['mcid'] = $value['id'];
        $res[$key]['title'] = $value['title'];
        $res[$key]['video'] = $value['videopath']?$ImageUrl.$value['videopath']:"";
        $res[$key]['img'] = $value['img']?$ImageUrl.$value['img']:"";
        $res[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
        $res[$key]['browsenum'] = $ClassBrowse->where('mcid='.$value['id'])->count();
        $res[$key]['collectnum'] = $ClassCollect->where('mcid='.$value['id'])->count();
        $res[$key]['commentnum'] = $ClassComment->where('mcid='.$value['id'])->count();
        $res[$key]['ifcollect'] = '0';
        $res[$key]['ifbrowse'] = '0';
        $t = $ClassCollect->where('mcid='.$value['id'].' and uid='.$uid)->count();
        if($t>0){
          $res[$key]['ifcollect'] = '1';
        }
        $bnum = $ClassBrowse->where('mcid='.$value['id'].' and uid='.$uid)->count();
        if($bnum>0){
          $res[$key]['ifbrowse'] = '1';
        }
      }
      ApiResult('200',$res,'');
    }
    /**
    *签到
    */
    public function signin(){
      $uid = I('uid');

      $Signin = D('Signin');
      $Point = D('Point');
      $today = strtotime(date('Y-m-d',time()));
      $f = $Signin->where('addtime='.$today.' and uid='.$uid)->find();
      if(!empty($f))
        ApiResult('201','','今天你已经签过了');
      $point = 1;                               //签到给1积分
      $msg = '签到收入';
      $Signin->uid = $uid;
      $Signin->addtime = strtotime(date('Y-m-d',time()));
      $Signin->signtime = date('m.d',time());
      $r = $Signin->add();
      if($r){
        $yestoday = strtotime(date('Y-m-d',time()-86400));
        $y = $Signin->where('addtime='.$yestoday.' and uid='.$uid)->find();
        //如果昨天签到了，累计签到数+1。如果没有签到，则置1
        if(!empty($y)){
          $Point->where('uid='.$uid)->setInc('signsum',1);
        }else{
          $Point->where('uid='.$uid)->setField('signsum',1);
        }
        $ss = $Point->where('uid='.$uid)->getField('signsum');
        if($ss%7 == 0){
          $point = 4;
          $msg = '连续签到7天';
        }//连续签到7天，则多加4分
        setPointLog($uid,$msg,$point);
        $Point->where('uid='.$uid)->setInc('sum',$point); // 用户的积分增加
        $res = $this->getSignInfo($uid);
        if(!empty($res))
          ApiResult('200',$res,'');
      }else{
        ApiResult('202','','签到失败');
      }
    }
    /**
    *签到情况
    */
    public function pointinfo(){
      $uid = I('uid');
      $res = $this->getSignInfo($uid);
      if(!empty($res))
        ApiResult('200',$res,'');
    }
    /**
    *获取签到情况
    */
    public function getSignInfo($uid){
      $Signin = D('Signin');
      $Point = D('Point');
      $Users = D('Users');
      $ifexist = $Point->where('uid='.$uid)->count();
      if($ifexist<1){
        $Point->uid = $uid;
        $Point->add();
      }
      $ImageUrl = C('ImageUrl');
      $yesterday = strtotime(date('Y-m-d',time()-86400));
      $today = strtotime(date('Y-m-d',time()));
      $ystdsign = $Signin->where('uid='.$uid.' and addtime='.$yesterday)->find();//昨天签到情况
      $tdsign = $Signin->where('uid='.$uid.' and addtime='.$today)->find();//今天签到情况
      $userPoint = $Point->where('uid='.$uid)->field('sum,signsum')->find();//用户的积分总数，连续签到天数
      $res['today'] = date('Y-m',time());
      $res['pointsum'] = $userPoint['sum'];
      $res['signsum'] = $userPoint['signsum'];
      $res['ifsign'] = '0';
      $res['week'] = array();
      $res['list'] = array();//排行榜
      if(empty($ystdsign)){
        for($i=0;$i<7;$i++){
          $res['week'][$i]['date'] = date('m.d',time()+86400*$i);
          $res['week'][$i]['color'] = 'red';
          if($i==0){
            $res['week'][$i]['date'] = '今天';
            if(!empty($tdsign)){
              $res['week'][$i]['color'] = 'gray';
              $res['ifsign'] = '1';
            }
          }
          if($i==1){
            $res['week'][$i]['date'] = '明天';
          }
        }
      }else{
        for($i=0;$i<7;$i++){
          $res['week'][$i]['date'] = date('m.d',time()+86400*($i-1));
          $res['week'][$i]['color'] = 'red';
          if($i==0){
            $res['week'][$i]['color'] = 'gray';
          }
          if($i==1){
            $res['week'][$i]['date'] = '今天';
            if(!empty($tdsign)){
              $res['week'][$i]['color'] = 'gray';
              $res['ifsign'] = '1';
            }
          }
          if($i==2){
            $res['week'][$i]['date'] = '明天';
          }
        }
      }
      $listinfo = $Point->alias('p')->join('lct_users s ON s.uid=p.uid')
      ->field('s.headpic,s.nickname,p.signsum,p.sum')
      ->where('1=1')//原来是对流动党员，现在全体都排名。2017/10/27改
      //->where('s.identity=1')//流动党员
      ->order('p.sum desc')->limit(20)->select();
      foreach ($listinfo as $key => $value) {
        $res['list'][$key]['headpic'] = $ImageUrl.$value['headpic'];
        $res['list'][$key]['nickname'] = $value['nickname'];
        $res['list'][$key]['signsum'] = $value['signsum'];
        $res['list'][$key]['pointsum'] = $value['sum'];
      }
      return $res;
    }

    /**
     * 我发布的公益秀
     */
     public function benshow(){
       $uid = I('uid');
       $iPageItem = I('iPageItem');
       $iPageIndex = I('iPageIndex');

       $Dynamic = D('Dynamic');
       $DynamicComment = D('DynamicComment');
       $DynamicBrowse = D('DynamicBrowse');
       $DynamicCollect = D('DynamicCollect');
       $Users = D('Users');
       $ImageUrl = C('ImageUrl');
       $query = "cateid=4 and uid=".$uid;
       $dlist = $Dynamic->where($query)->page($iPageIndex+1,$iPageItem)->order('addtime desc')->select();
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

    /**
     *我的组织
     */
     public function org(){
       $uid = I('uid');
       $iPageItem = I('iPageItem');
       $iPageIndex = I('iPageIndex');
       $phone = D('Users')->where('uid='.$uid)->getField('phone');
       $OrgJoin = D('OrgJoin');
       $OrgParty = D('OrgParty');
       $ImageUrl = C('ImageUrl');
       $ojlist = $OrgJoin->alias('oj')->join('lct_org o ON oj.oid=o.id')
       ->field('oj.oid,o.score,oj.ifinit,o.orgname,o.img,o.orgid')
       ->where('oj.phone="'.$phone.'"')->page($iPageIndex+1,$iPageItem)->order('oj.addtime desc')->select();
       if(empty($ojlist)){
         ApiResult('201',array(),'没有组织');
       }
       $res = array();
       foreach ($ojlist as $key => $value) {
         $res[$key]['oid'] = $value['oid'];
         $res[$key]['orgname'] = $value['orgname'];
         $res[$key]['point'] = $value['score'];
         $res[$key]['num'] = $OrgJoin->where('oid='.$value['oid'])->count();
         $res[$key]['img'] = $ImageUrl.$value['img'];
         $res[$key]['party'] = $OrgParty->where('orgid='.$value['orgid'])->getField('party');
         $res[$key]['ifinit'] = $value['ifinit'];
       }
       ApiResult("200",$res,"");
     }
}
