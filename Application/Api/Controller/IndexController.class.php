<?php
namespace Api\Controller;
use Think\Controller;
class IndexController extends Controller {
    /**
    *党建动态列表
    */
    public function dynamiclist(){
      $uid = I('uid');
      $lev = I('lev');
      $type = I('type');
      $orgid = I('orgid');
      $iPageItem = I('iPageItem');
      $iPageIndex = I('iPageIndex');
      $Dynamic = D('Dynamic');
      $DynamicBrowse = D('DynamicBrowse');
      $DynamicCollect = D('DynamicCollect');
      $ImageUrl = C('ImageUrl');
      //查询参数
      $query['cateid'] = '0';
      if($lev != '')
        $query['lev'] = $lev;
      if($type != '')
        $query['type'] = $type;
      if($orgid!= '')
        $query['orgid'] = $orgid;

      if($iPageItem){
        $dlist = $Dynamic->where($query)->field('id,title,lev,type,orgid,img,addtime,browse,collect')
        ->page($iPageIndex+1,$iPageItem)->order('addtime desc')->select();
      }else{
        $dlist = $Dynamic->where($query)->field('id,title,lev,type,orgid,img,addtime,browse,collect')
        ->order('addtime desc')->select();
      }
      $res = array();
      foreach ($dlist as $key => $value) {
        $res[$key]['dyid'] = $value['id'];
        $res[$key]['title'] = $value['title'];
        $res[$key]['content'] = "";
        $res[$key]['img'] = array();
        $img_arr = json_decode($value['img'],true);
        if(empty($img_arr)){
          $img_arr = array("/Upload/images/def/dynamic.png");
        }
        foreach ($img_arr as $k => $v) {
          $res[$key]['img'][$k]['imgurl'] = $ImageUrl.$v;
        }
        switch ($value['lev']) {
          case '1':
            //1-廉政清风;2-文化宣传;3-统一战线;4-职工之家;5-飞扬青春;6-铿锵玫瑰;7-组工堡垒;8-其他;
            switch ($value['type']) {
              case '1':
                $res[$key]['sign'] = "廉政清风";
                break;
              case '2':
                $res[$key]['sign'] = "文化宣传";
                break;
              case '3':
                $res[$key]['sign'] = "统一战线";
                break;
              case '4':
                $res[$key]['sign'] = "职工之家";
                break;
              case '5':
                $res[$key]['sign'] = "飞扬青春";
                break;
              case '6':
                $res[$key]['sign'] = "铿锵玫瑰";
                break;
              case '7':
                $res[$key]['sign'] = "组工堡垒";
                break;
              case '8':
                $res[$key]['sign'] = "其他";
                break;
            }
            break;
          case '2':
            if($value['orgid']){
              $res[$key]['sign'] = D('OrgParty')->where('orgid='.$value['orgid'])->getField('party');
            }else{
              $res[$key]['sign'] = "街道";
            }
            break;
        }
        $res[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
        // $res[$key]['browsenum'] = $DynamicBrowse->where('dyid='.$value['id'])->count();
        // $res[$key]['collectnum'] = $DynamicCollect->where('dyid='.$value['id'])->count();
        $res[$key]['browsenum'] = $value['browse'];
        $res[$key]['collectnum'] = $value['collect'];
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
      if(!empty($res)){
        ApiResult('200',$res,'');
      }else{
        ApiResult('201',$res,'还没有哦~');
      }
    }
    /**
    *党建动态详情
    */
    public function dynamic(){
      $uid = I('uid');
      $dyid = I('dyid');
      $Dynamic = D('Dynamic');
      $DynamicBrowse = D('DynamicBrowse');
      $DynamicCollect = D('DynamicCollect');
      $ImageUrl = C('ImageUrl');
      $dy = $Dynamic->where('id='.$dyid)->find();
      if(empty($dy))
        ApiResult('201',array(),'动态去火星了~');
      $res = array();
      $res['dyid'] = $dy['id'];
      $res['title'] = $dy['title'];
      $res['author'] = $dy['author'];
      $res['content'] = $dy['content'];
      $res['contentUrl'] = APP_URL."/Public/api/share/detail.html?type=dynamic&id=".$dyid;
      $res['img'] = array();
      $img_arr = json_decode($dy['img'],true);
      if(empty($img_arr)){
        $img_arr = array("/Upload/images/def/dynamic.png");
      }
      foreach ($img_arr as $key => $value) {
        $res['img'][$key]['imgurl'] = $ImageUrl.$value;
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
          //增加2点积分
          $Point = D('Point');
          $msg = '阅读动态(id='.$dyid.')增加';
          $point = 2;
          setPointLog($uid,$msg,$point);
          $Point->where('uid='.$uid)->setInc('sum',$point); // 用户的积分增加
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
        $DynamicBrowse->dyid = $dyid;
        $DynamicBrowse->uid = 0;
        $DynamicBrowse->addtime = time();
        $DynamicBrowse->add();
      }
      $Dynamic->where('id='.$dyid)->setInc('browse',1);
      // $res['browsenum'] = $DynamicBrowse->where('dyid='.$dy['id'])->count();
      // $res['collectnum'] = $DynamicCollect->where('dyid='.$dy['id'])->count();
      $res['browsenum'] = $dy['browse'];
      $res['collectnum'] = $dy['collect'];
      ApiResult('200',$res,'');
    }

    public function dycollect(){
      $uid = I('uid');
      $dyid = I('dyid');
      $type = I('type');
      $DynamicCollect = D('DynamicCollect');
      $Dynamic = D('Dynamic');
      $dy = $Dynamic->where('id='.$dyid)->find();
      if(empty($dy))
        ApiResult('202','','动态去火星了~');
      $t = $DynamicCollect->where('dyid='.$dyid.' and uid='.$uid)->count();
      if($type == '1'){
        if($t>0)
          ApiResult('201','','已经收藏了你心里没数吗？');
        $DynamicCollect->dyid = $dyid;
        $DynamicCollect->uid = $uid;
        $DynamicCollect->addtime = time();
        $DynamicCollect->add();
        $Dynamic->where('id='.$dyid)->setInc('collect',1);
        ApiResult('200','','收藏成功');
      }else{
        if($t==0)
          ApiResult('203','','没有收藏过');
        $DynamicCollect->where('dyid='.$dyid.' and uid='.$uid)->delete();
        // $Dynamic->where('id='.$dyid)->setDec('collect',1);
        ApiResult('200','','取消成功');
      }
    }

    /**
    *党费支付
    */
    public function pay(){
      ApiResult('400','','wait');
    }

    /**
    *公告列表
    */
    public function noticelist(){
      $uid = I('get.uid','');
      $iPageItem = I('iPageItem');
      $iPageIndex = I('iPageIndex');
      $Notice = D('Notice');
      $NoticeRead = D('NoticeRead');
      if($iPageItem){
        $NArr = $Notice->where('1=1')->page($iPageIndex+1,$iPageItem)->order('addtime desc')->select();
      }else{
        $NArr = $Notice->where('1=1')->order('addtime desc')->select();
      }
      $res = array();
      if(empty($NArr))
        ApiResult('201',$res,'没有公告');
      foreach ($NArr as $key => $value) {
        $res[$key]['nid'] = $value['id'];
        $res[$key]['title'] = $value['title'];
        $res[$key]['addtime'] =date('Y-m-d H:i:s',$value['addtime']);
        $res[$key]['ifread'] = '0';
        if($uid && is_numeric($uid) && $uid>0){
          $NR = $NoticeRead->where('noticeid='.$value['id'].' and uid='.$uid)->find();
          if(!empty($NR)){
            $res[$key]['ifread'] = '1';
          }
        }
      }
      ApiResult('200',$res,'');
    }
    /**
    *公告详情
    */
    public function notice(){
      $uid = I('get.uid','');
      $nid = I('nid');
      $Notice = D('Notice');
      $NoticeRead = D('NoticeRead');
      $ninfo = $Notice->where('id='.$nid)->find();
      $res = array();
      if(empty($ninfo))
        ApiResult('201',$res,'该公告已被删除');
      $res['author'] = $ninfo['author'];
      $res['title'] = $ninfo['title'];
      $res['content'] = $ninfo['content'];
      $res['contentUrl'] = APP_URL."/Public/api/share/detail.html?type=notice&id=".$nid;
      $res['addtime'] =date('Y-m-d H:i:s',$ninfo['addtime']);
      if($uid && is_numeric($uid) && $uid>0){
        $NR = $NoticeRead->where('noticeid='.$nid.' and uid='.$uid)->find();
        if(empty($NR)){
          $NoticeRead->uid = $uid;
          $NoticeRead->noticeid = $nid;
          $NoticeRead->addtime = time();
          $NoticeRead->add();
        }
      }
      $res['readnum'] = $NoticeRead->where('noticeid='.$nid)->count();
      ApiResult('200',$res,'');
    }
    /**
    *收藏/取消收藏公告
    */
    public function noticecollect(){
      $uid = I('uid');
      $nid = I('nid');
      $type = I('type');
      $NoticeCollect = D('NoticeCollect');
      $Notice = D('Notice');
      $no = $Notice->where('id='.$nid)->find();
      if(empty($no))
        ApiResult('202','','公告去火星了~');
      $t = $NoticeCollect->where('nid='.$nid.' and uid='.$uid)->count();
      if($type == '1'){
        if($t>0)
          ApiResult('201','','已经收藏了你心里没数吗？');
        $NoticeCollect->nid = $nid;
        $NoticeCollect->uid = $uid;
        $NoticeCollect->addtime = time();
        $NoticeCollect->add();
        ApiResult('200','','收藏成功');
      }else{
        if($t==0)
          ApiResult('203','','没有收藏过');
        $NoticeCollect->where('nid='.$nid.' and uid='.$uid)->delete();
        ApiResult('200','','取消成功');
      }
    }
    /**
    *媒体组
    */
    public function carousel(){
      $uid = I('get.uid','');
      $Carousel = D('Carousel');
      $ImageUrl = C('ImageUrl');
      $res['carousel'] = array();//轮播图
      $res['notice'] = array();//公告
      $res['dynamics'] = array();//动态区级
      $res['basic'] = array();//动态基层
      $res['vote'] = array();//投票
      //轮播图
      $CselArr = $Carousel
      ->field('type,itemid,link,img')->order('addtime desc')
      ->select();
      foreach ($CselArr as $key => $value) {
        $res['carousel'][$key]['type'] = $value['type'];
        $res['carousel'][$key]['link'] = "";
        $res['carousel'][$key]['itemid'] = "";
        if($value['link'] && $value['type']==1){
          $res['carousel'][$key]['link'] = $value['link'];
        }
        if($value['itemid'] && $value['type'] >1){
          $res['carousel'][$key]['itemid'] = $value['itemid'];
        }
        $res['carousel'][$key]['img'] = $ImageUrl.$value['img'];
      }
      //公告
      $Notice = D('Notice');
      $today = time();
      $query['begin'] = array('NEQ','null');
      $query['end'] = array('NEQ','null');
      $query['begin'] = array('ELT',$today);
      $query['end'] = array('EGT',$today);
      $NoticeArr = $Notice->where($query)->field('title')
      ->order('addtime desc')->select();
      foreach ($NoticeArr as $key => $value) {
          $res['notice'][$key]['title'] = $value['title'];
      }
      //正在进行中的投票
      $Vote = D('Vote');
      $map['begin'] = array('LT',$today);
      $map['end'] = array('GT',$today);
      $vlist = $Vote->where($map)->field('id,img')->select();
      foreach ($vlist as $key => $value) {
        $res['vote'][$key]['vid'] = $value['id'];
        $res['vote'][$key]['img'] = $ImageUrl.$value['img'];
      }
      //动态
      $Dynamic = D('Dynamic');
      $DynamicBrowse = D('DynamicBrowse');
      $DynamicCollect = D('DynamicCollect');
      $ImageUrl = C('ImageUrl');
      //区级
      $dlist = $Dynamic->where('cateid=0 and lev=1')->field('id,title,lev,type,orgid,img,addtime,browse,collect')
      ->order('addtime desc')->limit(3)->select();
      foreach ($dlist as $key => $value) {
        $res['dynamics'][$key]['dyid'] = $value['id'];
        $res['dynamics'][$key]['title'] = $value['title'];
        $res['dynamics'][$key]['content'] = "";
        $res['dynamics'][$key]['img'] = "";
        $img_arr = json_decode($value['img'],true);
        if(!empty($img_arr)){
          foreach ($img_arr as $k => $v) {
            $res['dynamics'][$key]['img'][$k]['imgurl'] = $ImageUrl.$v;
          }
        }
        switch ($value['lev']) {
          case '1':
            //1-廉政清风;2-文化宣传;3-统一战线;4-职工之家;5-飞扬青春;6-铿锵玫瑰;7-组工堡垒;8-其他;
            switch ($value['type']) {
              case '1':
                $res['dynamics'][$key]['sign'] = "廉政清风";
                break;
              case '2':
                $res['dynamics'][$key]['sign'] = "文化宣传";
                break;
              case '3':
                $res['dynamics'][$key]['sign'] = "统一战线";
                break;
              case '4':
                $res['dynamics'][$key]['sign'] = "职工之家";
                break;
              case '5':
                $res['dynamics'][$key]['sign'] = "飞扬青春";
                break;
              case '6':
                $res['dynamics'][$key]['sign'] = "铿锵玫瑰";
                break;
              case '7':
                $res['dynamics'][$key]['sign'] = "组工堡垒";
                break;
              case '8':
                $res['dynamics'][$key]['sign'] = "其他";
                break;
            }
            break;
          case '2':
            if($value['orgid']){
              $res['dynamics'][$key]['sign'] = D('OrgParty')->where('orgid='.$value['orgid'])->getField('party');
            }else{
              $res['dynamics'][$key]['sign'] = "街道";
            }
            break;
        }
        $res['dynamics'][$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
        // $res['dynamics'][$key]['browsenum'] = $DynamicBrowse->where('dyid='.$value['id'])->count();
        // $res['dynamics'][$key]['collectnum'] = $DynamicCollect->where('dyid='.$value['id'])->count();
        $res['dynamics'][$key]['browsenum'] = $value['browse'];
        $res['dynamics'][$key]['collectnum'] = $value['collect'];
        $res['dynamics'][$key]['ifbrowse'] = '0';
        $res['dynamics'][$key]['ifcollect'] = '0';
        if($uid && is_numeric($uid) && $uid>0){
          $c = $DynamicBrowse->where('dyid='.$value['id'].' and uid='.$uid)->count();
          if($c>0){
            $res['dynamics'][$key]['ifbrowse'] = '1';
          }
          $t = $DynamicCollect->where('dyid='.$value['id'].' and uid='.$uid)->count();
          if($t>0){
            $res['dynamics'][$key]['ifcollect'] = '1';
          }
        }
      }
      //基层
      $dlist = $Dynamic->where('cateid=0 and lev=2')->field('id,title,lev,type,orgid,img,addtime,browse,collect')
      ->order('addtime desc')->limit(3)->select();
      foreach ($dlist as $key => $value) {
        $res['basic'][$key]['dyid'] = $value['id'];
        $res['basic'][$key]['title'] = $value['title'];
        $res['basic'][$key]['content'] = "";
        $res['basic'][$key]['img'] = "";
        $img_arr = json_decode($value['img'],true);
        if(!empty($img_arr)){
          foreach ($img_arr as $k => $v) {
            $res['basic'][$key]['img'][$k]['imgurl'] = $ImageUrl.$v;
          }
        }
        switch ($value['lev']) {
          case '1':
            //1-廉政清风;2-文化宣传;3-统一战线;4-职工之家;5-飞扬青春;6-铿锵玫瑰;7-组工堡垒;8-其他;
            switch ($value['type']) {
              case '1':
                $res['basic'][$key]['sign'] = "廉政清风";
                break;
              case '2':
                $res['basic'][$key]['sign'] = "文化宣传";
                break;
              case '3':
                $res['basic'][$key]['sign'] = "统一战线";
                break;
              case '4':
                $res['basic'][$key]['sign'] = "职工之家";
                break;
              case '5':
                $res['basic'][$key]['sign'] = "飞扬青春";
                break;
              case '6':
                $res['basic'][$key]['sign'] = "铿锵玫瑰";
                break;
              case '7':
                $res['basic'][$key]['sign'] = "组工堡垒";
                break;
              case '8':
                $res['basic'][$key]['sign'] = "其他";
                break;
            }
            break;
          case '2':
            if($value['orgid']){
              $res['basic'][$key]['sign'] = D('OrgParty')->where('orgid='.$value['orgid'])->getField('party');
            }else{
              $res['basic'][$key]['sign'] = "街道";
            }
            break;
        }
        $res['basic'][$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
        // $res['basic'][$key]['browsenum'] = $DynamicBrowse->where('dyid='.$value['id'])->count();
        // $res['basic'][$key]['collectnum'] = $DynamicCollect->where('dyid='.$value['id'])->count();
        $res['basic'][$key]['browsenum'] = $value['browse'];
        $res['basic'][$key]['collectnum'] = $value['collect'];
        $res['basic'][$key]['ifbrowse'] = '0';
        $res['basic'][$key]['ifcollect'] = '0';
        if($uid && is_numeric($uid) && $uid>0){
          $c = $DynamicBrowse->where('dyid='.$value['id'].' and uid='.$uid)->count();
          if($c>0){
            $res['basic'][$key]['ifbrowse'] = '1';
          }
          $t = $DynamicCollect->where('dyid='.$value['id'].' and uid='.$uid)->count();
          if($t>0){
            $res['basic'][$key]['ifcollect'] = '1';
          }
        }
      }
      ApiResult('200',$res,'');
    }
}
