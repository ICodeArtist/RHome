<?php
namespace Api\Controller;
use Think\Controller;
class EmapController extends Controller {
    /**
    *地图点点
    */
    public function point(){
      $search = I('post.search','');
      $query = ' 1=1 ';
      if($search){
        $query .= " and orgname like '%".$search."%' ";
      }
      $ImageUrl = C('ImageUrl');
      $BArr = D('Emap')->where($query)->select();
      if(empty($BArr))
        ApiResult('201',array(),'没有找到');
      foreach ($BArr as $key => $value) {
        $BArr[$key]['img'] = $value['img']?$ImageUrl.$value['img']:"";
        unset($BArr[$key]['addtime']);
      }
      ApiResult('200',$BArr,'');
    }
    /**
    *地图列表
    */
    public function blist(){
      $party = I('party');
      $query['party'] = $party;
      $BArr = D('OrgBranch')->where($query)->field('id,party,branch,description,telephone,lng,lat,address')->select();
      if(empty($BArr))
        ApiResult('201',array(),'没有');
      foreach ($BArr as $key => $value) {
        if($value['party'] == $value['branch']){
          $BArr[$key]['orgname'] = $value['party'];
        }else{
          $BArr[$key]['orgname'] = $value['party'].' '.$value['branch'];
        }
        // unset($BArr[$key]['party']);
        // unset($BArr[$key]['branch']);
        if(!$value['lng'])
          $BArr[$key]['lng'] = "";
        if(!$value['lat'])
          $BArr[$key]['lat'] = "";
        if(!$value['address'])
          $BArr[$key]['address'] = "";
        if(!$value['description'])
          $BArr[$key]['description'] = "";
        if(!$value['telephone'])
          $BArr[$key]['telephone'] = "";
        $BArr[$key]['img'] = "";//图暂时没有的
      }
      ApiResult('200',$BArr,'');
    }
    /**
    *党支部信息
    */
    public function binfo(){
      $id = I('id');
      $query['id'] = $id;
      $BArr = D('OrgBranch')->where($query)->field('id,party,branch,description,telephone,lng,lat,address')->find();
      if(empty($BArr))
        ApiResult('201',array(),'没有');
      if($BArr['party'] == $BArr['branch']){
        $BArr['orgname'] = $BArr['party'];
      }else{
        $BArr['orgname'] = $BArr['party'].' '.$BArr['branch'];
      }
      // unset($BArr['party']);
      // unset($BArr['branch']);
      if(!$BArr['lng'])
        $BArr['lng'] = "";
      if(!$BArr['lat'])
        $BArr['lat'] = "";
      if(!$BArr['address'])
        $BArr['address'] = "";
      if(!$BArr['description'])
        $BArr['description'] = "";
      if(!$BArr['telephone'])
        $BArr['telephone'] = "";
      $BArr['img'] = "";//图暂时没有的
      ApiResult('200',$BArr,'');
    }
}
