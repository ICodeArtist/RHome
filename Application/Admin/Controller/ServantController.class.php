<?php
namespace Admin\Controller;
use Think\Controller;
class ServantController extends Controller {
  /**
   * 服务列表
   */
  public function svslist(){
    $Svs = D('Svs');
    $ImageUrl = C('ImageUrl');
    if(isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p']>0){
      $p = $_GET['p'];
    }else{
      $p = 1;
    }
    $slist = $Svs->where('1=1')->field('id,name,phone,title,content,img,address,score,addtime,status,party,branchid,uid,lev')
    ->page($p.',10')->select();
    $count = $Svs->where('1=1')->count();
    $Page = new \Think\PagePlus($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
    $show = $Page->show();// 分页显示输出
    $this->assign('page',$show);// 赋值分页输出
    foreach ($slist as $key => $value) {
      $slist[$key]['addtime'] = date('Y/m/d',$value['addtime']);
      $img_arr = json_decode($value['img'],true);
      $slist[$key]['img'] = array();
      if(!empty($img_arr)){
        foreach ($img_arr as $k => $va) {
          $slist[$key]['img'][$k]['imgurl'] = $ImageUrl.$va;
        }
      }
      if($value['party'] && is_numeric($value['party']) && $value['party']>0){
        $slist[$key]['party'] = D('OrgParty')->where('orgid='.$value['party'])->getField('party');
      }else{
        $slist[$key]['party'] = "异常";
      }
      $slist[$key]['barnch'] = "";
      $slist[$key]['person'] = "";
      switch ($value['lev']) {
        case '2':
        if($value['branchid'] && is_numeric($value['branchid']) && $value['branchid']>0){
          $slist[$key]['barnch'] = D('OrgBranch')->where('id='.$value['branchid'])->getField('branch');
        }else{
          $slist[$key]['barnch'] = "异常";
        }
          break;
        case '3':
        if($value['branchid'] && is_numeric($value['branchid']) && $value['branchid']>0){
          $slist[$key]['barnch'] = D('OrgBranch')->where('id='.$value['branchid'])->getField('branch');
        }else{
          $slist[$key]['barnch'] = "异常";
        }
        if($value['uid'] && is_numeric($value['uid']) && $value['uid']>0){
          $slist[$key]['person'] = D('Users')->where('uid='.$value['uid'])->getField('realname');
        }else{
          $slist[$key]['person'] = "异常";
        }
          break;
      }
      switch ($value['status']) {
        case '1':
          $slist[$key]['status'] = "处理中";
          break;
        case '2':
          $slist[$key]['status'] = "待评价";
          break;
        case '3':
          $slist[$key]['status'] = "已完成";
          break;
      }
    }
    $this->assign('slist',$slist);
    $this->display('svslist');
  }
  //获取处理96345的组织
  public function getDealParty(){
    $OrgParty = D('OrgParty');
    $plist = $OrgParty->alias('o')->join('lct_users u ON u.account=o.telephone')
    ->where('1=1')->field('o.orgid,o.party')->order('o.orgid asc')->select();
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
   * 接电话后，发布需求服务
   *Array ( [party] => [name] => [phone] => [title] => [content] => [address] => [score] => )
   */
  public function addSvs(){
    $party = $_POST['party'];
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $title = trim($_POST['title']);
    $content = $_POST['content'];
    $address = trim($_POST['address']);
    $score = trim($_POST['score']);
    /*详情图*/
    $flag = 0;
		$picture = '[';
		$file = $_FILES["picture"];
		if(!empty($file["name"])) {
			foreach ($file["name"] as $key => $value) {
        if($file["name"][$key]){
          $file1['name'] = $file["name"][$key];
  				$file1['tmp_name'] = $file["tmp_name"][$key];
  				$file1['type'] = $file["type"][$key];
  				$picture .= '"';
  				$picture .= UploadImageByMySelf('servant',$file1);
  				$picture .= '",';
        }else{
          $flag++;
        }
			}
		}
    if($flag==3){
      $picture .= '"/Upload/images/def/96345.png"';
    }
		$picture = rtrim($picture,',');
		$picture .= ']';
    $Svs = D('Svs');
    $Svs->name = $name;
    $Svs->phone = $phone;
    $Svs->title = $title;
    $Svs->content = $content;
    $Svs->img = $picture;
    $Svs->address = $address;
    $Svs->score = $score;
    $Svs->addtime = time();
    $Svs->status = 1;
    $Svs->party = $party;
    $Svs->lev = 1;
    $Svs->updatetime = time();
    $res = $Svs->add();
    if($res){
      $OrgParty = D('OrgParty');
      $mobile = $OrgParty->where('orgid='.$party)->getField('telephone');
      if($mobile>0){
        $message = '你有一条96345需要处理';
        SmsMessage($mobile,'23',$message);
      }
			$this->success('新增成功', __CONTROLLER__.'/svslist');
    }
  }
}
