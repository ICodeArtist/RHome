<?php
namespace Api\Controller;
use Think\Controller;
class TopicController extends Controller {
  /**
   *专题轮播
   */
  public function spcarousel(){
    $Spcate = D('Spcate');
    $ImageUrl = C('ImageUrl');
    $sclist = $Spcate->where('1=1')->field('id as scid,name,img')->select();
    if(empty($sclist))
      ApiResult('201',array(),'没有');
    foreach ($sclist as $key => $value) {
      $sclist[$key]['img'] = $value['img']?$ImageUrl.$value['img']:"";
    }
    ApiResult('200',$sclist,'');
  }
  /**
   *专题列表
   */
  public function splist(){
    $scid = I('scid');
    $iPageItem = I('iPageItem');
    $iPageIndex = I('iPageIndex');
    $Special = D('Special');
    $ImageUrl = C('ImageUrl');
    $splist = $Special->where('cateid='.$scid)->field('id as spid,title,img,addtime')->page($iPageIndex+1,$iPageItem)
    ->order('addtime desc')->select();
    if(empty($splist))
      ApiResult('201',array(),'没有');
    foreach ($splist as $key => $value) {
      $splist[$key]['img'] = $value['img']?$ImageUrl.$value['img']:"";
      $splist[$key]['addtime'] = date('Y-m-d',$value['addtime']);
    }
    ApiResult('200',$splist,'');
  }
  /**
   *专题详情
   */
  public function spdetails(){
    $spid = I('spid');
    $Special = D('Special');
    $ImageUrl = C('ImageUrl');
    $spinfo = $Special->alias('sp')->join('lct_spcate sc ON sc.id=sp.cateid')
    ->field('sp.id as spid,sp.author,sp.title,sp.content,sp.img,sp.addtime,sc.name')
    ->where('sp.id='.$spid)->find();
    if(empty($spinfo))
      ApiResult('201',array(),'该专题已被删除');
    $spinfo['contentUrl'] = APP_URL."/Public/api/share/detail.html?type=spdetails&id=".$spid;
    $spinfo['img'] = $spinfo['img']?$ImageUrl.$spinfo['img']:"";
    $spinfo['addtime'] = date('Y-m-d',$spinfo['addtime']);
    ApiResult('200',$spinfo,'');
  }
}
