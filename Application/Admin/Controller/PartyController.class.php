<?php
namespace Admin\Controller;
use Think\Controller;
class PartyController extends Controller {
    /**
    *功能：区级，镇级选择
    */
    public function getLevNum(){
      $uid = session('uid');
      $Admins = D('Admins');
      $adminlevnum = $Admins->where('uid='.$uid)->field('lev1,lev2')->find();
      $data = array();
      if($adminlevnum['lev1']){
        $data[0]['id'] = '1';
        $data[0]['name'] = '区级';
      }
      if($adminlevnum['lev2']){
        $data[1]['id'] = '2';
        $data[1]['name'] = '基层';
      }
      $data = array_values($data);
      $this->ajaxReturn($data);
    }
    /**
    *功能：分类选择
    */
    public function getLev(){
      $uid = session('uid');
      $Admins = D('Admins');
      $adminlev = $Admins->where('uid='.$uid)->field('lev')->find();
      //动态分类
      $lev = array(
        array("name"=>"廉政清风","val"=>"1"),
        array("name"=>"文化宣传","val"=>"2"),
        array("name"=>"统一战线","val"=>"3"),
        array("name"=>"职工之家","val"=>"4"),
        array("name"=>"飞扬青春","val"=>"5"),
        array("name"=>"铿锵玫瑰","val"=>"6"),
        array("name"=>"组工堡垒","val"=>"7"),
        array("name"=>"其他","val"=>"8")
      );
      $data = array();
      foreach ($lev as $key => $value) {
        if( in_array($value['val'],explode(',',$adminlev['lev']))){
          $data[$key]['id'] = $value['val'];
          $data[$key]['name'] = $value['name'];
        }
      }
      $data = array_values($data);
      $this->ajaxReturn($data);
    }
    /**
    *功能：选择镇街级组织
    */
    public function getChangeParty(){
      $permission = session('permission');
      $OrgParty = D('OrgParty');
      $query = ' 1=1 ';
      if($permission == 2){
        $adminorgid = cookie('adminorgid');
        $query .= ' and orgid='.$adminorgid;
      }
      // if(isset($_POST['orgid']) && is_numeric($_POST['orgid']) && $_POST['orgid']>0){
      //   $query .= ' and orgid='.$_POST['orgid'];
      // }
      $plist = $OrgParty->where($query)
      ->field('orgid,party')->select();
      if(!empty($plist)){
  			foreach ($plist as $key => $value) {
  				$data[$key]['id'] = $value['orgid'];
  				$data[$key]['name'] = $value['party'];
  			}
  		}else{
  			$data['msg'] = 'fail';
  		}
  		$this->ajaxReturn($data);
    }
    /**
    *功能:选择村社区组织
    */
    public function getChangeBranch(){
      $OrgBranch = D('OrgBranch');
      if(!$_POST['orgid']){
        $data['code'] = '201';
        $this->ajaxReturn($data);
      }
      $plist = $OrgBranch->where('orgid='.$_POST['orgid'])->field('id,branch')->select();
      if(!empty($plist)){
  			foreach ($plist as $key => $value) {
  				$data[$key]['id'] = $value['id'];
  				$data[$key]['name'] = $value['branch'];
  			}
  		}else{
        $data['code'] = '201';
  			$data['msg'] = 'fail';
  		}
  		$this->ajaxReturn($data);
    }
    /**
    *镇街级组织
    */
    public function getPartyList(){
      $permission = session('permission');
      $OrgParty = D('OrgParty');
      $OrgBranch = D('OrgBranch');
      $query = '1=1 ';
      if($permission == 2){
        $adminorgid = cookie('adminorgid');
        $query .= ' and orgid='.$adminorgid;
      }
      $plist = $OrgParty->where($query)
      ->field('orgid,party,description,secretary,contact,telephone,address,lng,lat')->select();
      foreach ($plist as $key => $value) {
        $c = $OrgBranch->where('orgid='.$value['orgid'])->count();
        if($c>0){//有关联，则不能删除
          $plist[$key]['iflink'] = '1';
        }else{
          $plist[$key]['iflink'] = '0';
        }
      }
      $this->assign('plist',$plist);
      $this->display('partylist');
    }
    /**
    *添加镇街级组织
    */
    public function addParty(){
      $OrgParty = D('OrgParty');
      $OrgParty->party = $_POST['party'];
      $OrgParty->description = $_POST['description']?$_POST['description']:"";
      $OrgParty->secretary = $_POST['secretary']?$_POST['secretary']:"";
      $OrgParty->contact = $_POST['contact']?$_POST['contact']:"";
      $OrgParty->telephone = $_POST['telephone']?$_POST['telephone']:"";
      $OrgParty->address = $_POST['address']?$_POST['address']:"";
      $OrgParty->lng = $_POST['lng']?$_POST['lng']:"";
      $OrgParty->lat = $_POST['lat']?$_POST['lat']:"";
      $res = $OrgParty->add();
      if($res)
        $this->getPartyList();
    }
    /**
    *编辑镇街级组织信息
    */
    public function EditParty(){
      $OrgParty = D('OrgParty');
      $OrgBranch = D('OrgBranch');
      $Users = D('Users');
      $oldparty = $OrgParty->where('orgid='.$_POST['orgid'])->getField('party');//原来的一级组织名
      //更新一级组织
      $party = $_POST['party'];
      $OrgParty->party = $party;
      $OrgParty->description = $_POST['description'];
      $OrgParty->secretary = $_POST['secretary'];
      $OrgParty->contact = $_POST['contact'];
      $OrgParty->telephone = $_POST['telephone'];
      $OrgParty->address = $_POST['address'];
      if($_POST['lng'])
        $OrgParty->lng = $_POST['lng'];
      if($_POST['lat'])
        $OrgParty->lat = $_POST['lat'];
      $OrgParty->where('orgid='.$_POST['orgid'])->save();
      //更新党员一级组织
      $Users->party = $party;
      $Users->where('orgid='.$_POST['orgid'])->save();
      //更新二级组织
      $OrgBranch->branch = $party;
      $OrgBranch->description = $_POST['description'];
      $OrgBranch->secretary = $_POST['secretary'];
      $OrgBranch->contact = $_POST['contact'];
      $OrgBranch->telephone = $_POST['telephone'];
      $OrgBranch->address = $_POST['address'];
      if($_POST['lng'])
        $OrgBranch->lng = $_POST['lng'];
      if($_POST['lat'])
        $OrgBranch->lat = $_POST['lat'];
      $query['branch'] = $oldparty;
      $OrgBranch->where($query)->save();

      $OrgBranch->party = $party;
      $OrgBranch->where('orgid='.$_POST['orgid'])->save();
      $data['code'] = '200';
      $this->ajaxReturn($data);
    }
    /**
    *
    */
    public function delParty(){
      $OrgParty = D('OrgParty');
      $re = $OrgParty->where('orgid='.$_POST['orgid'])->delete();
      if($re){
  			$data['msg'] = 'success';
  		}else{
  			$data['msg'] = 'fail';
  		}
  		$this->ajaxReturn($data);
    }
    /**
    *村社区组织
    */
    public function getBranchList(){
      $permission = session('permission');
      $query = '1=1 ';
      if($permission == 2){
        $adminorgid = cookie('adminorgid');
        $query .= ' and orgid='.$adminorgid;
      }
      $OrgBranch = D('OrgBranch');
      $blist = $OrgBranch->where($query)
      ->field('id,party,branch,description,secretary,contact,telephone,address,lng,lat')->select();
      foreach ($blist as $key => $value) {
        $c = D('Users')->where('branch="'.$value['branch'].'"')->count();
        if($c>0){//有关联，则不能删除
          $blist[$key]['iflink'] = '1';
        }else{
          $blist[$key]['iflink'] = '0';
        }
      }
      $this->assign('blist',$blist);
      $this->display('branchlist');
    }
    /**
    *村社区组织
    */
    public function addBranch(){
      $OrgBranch = D('OrgBranch');
      $OrgParty = D('OrgParty');
      $party = $OrgParty->where('orgid='.$_POST['orgid'])->getField('party');
      $OrgBranch->orgid = $_POST['orgid'];
      $OrgBranch->party = $party;
      $OrgBranch->branch = $_POST['branch'];
      $OrgBranch->description = $_POST['description']?$_POST['description']:"";
      $OrgBranch->secretary = $_POST['secretary']?$_POST['secretary']:"";
      $OrgBranch->contact = $_POST['contact']?$_POST['contact']:"";
      $OrgBranch->telephone = $_POST['telephone']?$_POST['telephone']:"";
      $OrgBranch->address = $_POST['address']?$_POST['address']:"";
      $OrgBranch->lng = $_POST['lng']?$_POST['lng']:"";
      $OrgBranch->lat = $_POST['lat']?$_POST['lat']:"";
      $res = $OrgBranch->add();
      if($res)
        $this->getBranchList();
    }
    /**
    *编辑村社区组织信息
    */
    public function EditBranch(){
      $OrgParty = D('OrgParty');
      $OrgBranch = D('OrgBranch');
      $Users = D('Users');
      $oldbranch = $OrgBranch->where('id='.$_POST['branchid'])->getField('branch');//原组织名
      $query['party'] = $oldbranch;
      $ifparty = $OrgParty->where($query)->find();
      //更新二级组织
      $OrgBranch->branch = $_POST['branch']?$_POST['branch']:"";
      $OrgBranch->description = $_POST['description']?$_POST['description']:"";
      $OrgBranch->secretary = $_POST['secretary']?$_POST['secretary']:"";
      $OrgBranch->contact = $_POST['contact']?$_POST['contact']:"";
      $OrgBranch->telephone = $_POST['telephone']?$_POST['telephone']:"";
      $OrgBranch->address = $_POST['address']?$_POST['address']:"";
      if($_POST['lng'])
        $OrgBranch->lng = $_POST['lng'];
      if($_POST['lat'])
        $OrgBranch->lat = $_POST['lat'];
      $OrgBranch->where('id='.$_POST['branchid'])->save();
      //更新二级组织下党员的二级组织信息
      $q['branch'] = $oldbranch;
      $Users->branch = $_POST['branch']?$_POST['branch']:"";
      $Users->where($q)->save();
      //更新一级组织
      if(!empty($ifparty)){
        $OrgParty->party = $_POST['branch']?$_POST['branch']:"";
        $OrgParty->description = $_POST['description']?$_POST['description']:"";
        $OrgParty->secretary = $_POST['secretary']?$_POST['secretary']:"";
        $OrgParty->contact = $_POST['contact']?$_POST['contact']:"";
        $OrgParty->telephone = $_POST['telephone']?$_POST['telephone']:"";
        $OrgParty->address = $_POST['address']?$_POST['address']:"";
        if($_POST['lng'])
          $OrgParty->lng = $_POST['lng'];
        if($_POST['lat'])
          $OrgParty->lat = $_POST['lat'];
        $OrgParty->where($query)->save();
        $mp['party'] = $_POST['branch'];
        $orgid = $OrgParty->where($mp)->getField('orgid');
        if($orgid){
          $OrgBranch->party = $_POST['branch'];
          $OrgBranch->where('orgid='.$orgid)->save();
        }
      }
      $data['code'] = '200';
      $this->ajaxReturn($data);
    }
    /**
    *删除村社区组织
    */
    public function delBranch(){
      $OrgBranch = D('OrgBranch');
      $re = $OrgBranch->where('id='.$_POST['id'])->delete();
      if($re){
  			$data['msg'] = 'success';
  		}else{
  			$data['msg'] = 'fail';
  		}
  		$this->ajaxReturn($data);
    }
    /**
    *党建动态
    */
    public function getDynamicList(){
      $nowuid = session('uid');
      $permission = session('permission');
      $Dynamic = D('Dynamic');
      $DynamicBrowse = D('DynamicBrowse');
      $DynamicCollect = D('DynamicCollect');
      $DynamicComment = D('DynamicComment');
      $ImageUrl = C('ImageUrl');
      if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>0){
        $p = $_GET['p'];
      }else{
        $p = 1;
      }
      $dlist = $Dynamic->where('cateid=0')
      ->field('id,uid,lev,type,orgid,title,img,addtime,author,content,browse as browsenum,collect as collectnum')
      ->page($p.',10')->order('addtime desc')
      ->select();
      $count = $Dynamic->where('cateid=0')->count();
      $Page = new \Think\PagePlus($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
      $show = $Page->show();// 分页显示输出
      // print_r($count);exit;
      $this->assign('page',$show);// 赋值分页输出
      foreach ($dlist as $key => $value) {
        $dlist[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
        $img_arr = json_decode($value['img'],true);
        $dlist[$key]['img'] = array();
        if(!empty($img_arr)){
          foreach ($img_arr as $k => $v) {
            $dlist[$key]['img'][$k]['imgurl'] = $ImageUrl.$v;
          }
        }
        switch ($value['lev']) {
          case '1':
            $dlist[$key]['leve'] = "区级";
            //1-廉政清风;2-文化宣传;3-统一战线;4-职工之家;5-飞扬青春;6-铿锵玫瑰;7-组工堡垒;8-其他;
            switch ($value['type']) {
              case '1':
                $dlist[$key]['type'] = "廉政清风";
                break;
              case '2':
                $dlist[$key]['type'] = "文化宣传";
                break;
              case '3':
                $dlist[$key]['type'] = "统一战线";
                break;
              case '4':
                $dlist[$key]['type'] = "职工之家";
                break;
              case '5':
                $dlist[$key]['type'] = "飞扬青春";
                break;
              case '6':
                $dlist[$key]['type'] = "铿锵玫瑰";
                break;
              case '7':
                $dlist[$key]['type'] = "组工堡垒";
                break;
              case '8':
                $dlist[$key]['type'] = "其他";
                break;
            }
            $dlist[$key]['org'] = "";
            break;
          case '2':
            $dlist[$key]['leve'] = "基层";
            $dlist[$key]['type'] = "";
            $dlist[$key]['org'] = D('OrgParty')->where('orgid='.$value['orgid'])->getField('party');
            break;
        }

        $dlist[$key]['commentnum'] = $DynamicComment->where('dyid='.$value['id'])->count();
        // $dlist[$key]['browsenum'] = $DynamicBrowse->where('dyid='.$value['id'])->count();
        // $dlist[$key]['collectnum'] = $DynamicCollect->where('dyid='.$value['id'])->count();
        if($nowuid == $value['uid'] || $permission == '1'){
  				$dlist[$key]['candel'] = '1';
  			}else{
  				$dlist[$key]['candel'] = '0';
  			}
      }
      $this->assign('dlist',$dlist);
      $this->display('dynamiclist');
    }
    /**/
    public function getDynamicEditPage(){
      $this->display('dynamicedit');
    }
    /**
    *删除动态
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
    *动态内容
    */
    public function getContent(){
  		$id = $_GET['id'];
  		$Dynamic = D('Dynamic');
  		$ninfo = $Dynamic->where('id='.$id)->field('content')->find();
  		$this->assign('ninfo',$ninfo);
  		$this->display('content');
  	}
    /**
    *发布党建动态
    */
    public function addDynamic(){
      $Dynamic = D('Dynamic');
  		$Admins = D('Admins');

  		$account = session('admininfo');
  		$query['account'] = $account;
  		$uid = $Admins->where($query)->getField('uid');
      /*展示图片*/
  		$file1 = $_FILES["photo"];
  		$picture = '';
  		if(!empty($file1["name"])) {
        $picture .='["';
  			$picture .= UploadImageByMySelf('dynamic',$file1);
        $picture .='"]';
  		}
  		$Dynamic->uid = $uid;
      $Dynamic->author = $_POST['author'];
      $Dynamic->cateid = 0;
      $Dynamic->lev = $_POST['lev']?$_POST['lev']:1;
      $Dynamic->type = $_POST['type']?$_POST['type']:7;
      $Dynamic->orgid = $_POST['orgid']?$_POST['orgid']:'';
  		$Dynamic->title = $_POST['title'];
  		$Dynamic->content = $_POST['content'];
      $Dynamic->img = $picture;
  		$Dynamic->addtime = time();
  		$res = $Dynamic->add();
  		if($res)
  			$this->getDynamicList();
    }
    //修改动态
    public function EditDynamic(){
      $Dynamic = D('Dynamic');
      $Dynamic->title = $_POST['title'];
      if($_POST['description'])
        $Dynamic->content = $_POST['description'];
      if($_POST['lev'])
        $Dynamic->lev = $_POST['lev'];
      if($_POST['type'])
        $Dynamic->type = $_POST['type'];
      if($_POST['orgid'])
        $Dynamic->orgid = $_POST['orgid'];
      $file1 = $_FILES["photo"];
  		$picture = '';
  		if(!empty($file1["name"])) {
        $picture .='["';
  			$picture .= UploadImageByMySelf('dynamic',$file1);
        $picture .='"]';
        $Dynamic->img = $picture;
  		}
      $Dynamic->author = $_POST['author'];
      $Dynamic->addtime = strtotime($_POST['addtime']);
      $Dynamic->where('id='.$_POST['dyid'])->save();
      $date['code'] = '200';
      $this->ajaxReturn($date);
    }
    /**
    *e地图
    */
    public function eMap(){
      $ImageUrl = C('ImageUrl');
      $elist = D('Emap')->select();
      foreach ($elist as $key => $value) {
        if($value['img'])
          $elist[$key]['img'] = $ImageUrl.$value['img'];
      }
      $this->assign('elist',$elist);
      $this->display('emaplist');
    }
    /**
    *e地图增加
    */
    public function addEmap(){
      $Emap = D('Emap');
      $file = $_FILES["photo"];
      if(!empty($file["name"])) {
        $thumbimage_url = UploadImageByMySelf('emap',$file);
        $Emap->img = $thumbimage_url;
      }
      $Emap->orgname = $_POST['orgname'];
      $Emap->description = $_POST['description'];
      $Emap->telephone = $_POST['telephone'];
      $Emap->address = $_POST['address'];
      $Emap->lng = $_POST['lng'];
      $Emap->lat = $_POST['lat'];
      $Emap->addtime = time();
      $res = $Emap->add();
      if($res)
  			$this->eMap();
    }
    /**
    *e地图编辑
    */
    public function EditEmap(){
      $Emap = D('Emap');
      $id = $_POST['id'];
      $file = $_FILES["photo"];
      if(!empty($file["name"])) {
        $thumbimage_url = UploadImageByMySelf('emap',$file);
        $Emap->img = $thumbimage_url;
      }
      $Emap->orgname = $_POST['orgname'];
      $Emap->description = $_POST['description'];
      $Emap->telephone = $_POST['telephone'];
      $Emap->address = $_POST['address'];
      $Emap->lng = $_POST['lng'];
      $Emap->lat = $_POST['lat'];
      $Emap->where('id='.$id)->save();
      $date['code'] = '200';
      $this->ajaxReturn($date);
    }
    /**
    *e地图删除
    */
    public function delEmap(){
      $Emap = D('Emap');
      $re = $Emap->where('id='.$_POST['id'])->delete();
      if($re){
  			$data['msg'] = 'success';
  		}else{
  			$data['msg'] = 'fail';
  		}
  		$this->ajaxReturn($data);
    }

    /**
    *修改
    */
    public function EditCount(){
      $Dynamic = D('Dynamic');
      $Dynamic->browse = $_POST['browsenum'];
      $Dynamic->collect = $_POST['collectnum'];
      $Dynamic->where('id='.$_POST['dyid'])->save();
      $date['code'] = '200';
      $this->ajaxReturn($date);
    }
}
