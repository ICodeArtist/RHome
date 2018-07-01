<?php
namespace Admin\Controller;
use Think\Controller;
class TopicController extends Controller {
  /**
   *专题轮播类型
   */
  public function spcate(){
    $Spcate = D('Spcate');
    $ImageUrl = C('ImageUrl');
    $sclist = $Spcate->where('1=1')->field('id,name,img')->select();
    foreach ($sclist as $key => $value) {
      $sclist[$key]['img'] = $ImageUrl.$value['img'];
    }
    $this->assign('sclist',$sclist);
    $this->display('sclist');
  }
  /**
   * 添加类型
   */
   public function addSc(){
     $Spcate = D('Spcate');
 		 $Spcate->name = $_POST['name'];
     $file = $_FILES["photo"];
     if(!empty($file["name"])) {
       $path = 'spcate';
       $thumbimage_url = UploadImageByMySelf($path,$file);
       $Spcate->img = $thumbimage_url;
     }
 		 $res = $Spcate->add();
     if($res)
	     $this->success('新增成功', __CONTROLLER__.'/spcate');
   }
   /**
 	*删除类型
 	*/
 	public function delSpcate(){
 		$cid = $_POST['cid'];
 		$Spcate = D('Spcate');
 		$re = $Spcate->where('id='.$cid)->delete();
 		if($re){
 			$data['msg'] = 'success';
 		}else{
 			$data['msg'] = 'fail';
 		}
 		$this->ajaxReturn($data);
 	}
  /**
	*编辑类型
	*/
	public function editSpcate(){
		$name = $_POST['name'];
		$cid = $_POST['cid'];

		$Spcate = D('Spcate');
		$Spcate->name = $name;
    $file = $_FILES["photo"];
    if(!empty($file["name"])) {
      $path = 'spcate';
      $thumbimage_url = UploadImageByMySelf($path,$file);
      $Spcate->img = $thumbimage_url;
    }
		$re = $Spcate->where('id='.$cid)->save();

		if($re)
			$data['code'] = '200';
		$this->ajaxReturn($data);
	}
  /**
   *专题列表special
   */
  public function splist(){
    $Special = D('Special');
    $ImageUrl = C('ImageUrl');
    $splist = $Special->alias('sp')->join('lct_spcate sc ON sc.id=sp.cateid')
    ->where('1=1')->field('sp.id,sp.title,sp.img,sp.addtime,sp.author,sc.name')->select();
    foreach ($splist as $key => $value) {
      $splist[$key]['addtime'] = date('Y-m-d',$value['addtime']);
      $splist[$key]['img'] = $ImageUrl.$value['img'];
    }
    $this->assign('splist',$splist);
    $this->display('splist');
  }

  /**
  *ajax返回专题分类
  */
  public function getSpCateid(){
    $Spcate = D('Spcate');
    $sl = $Spcate->field('id,name')->select();
    if(!empty($sl)){
      foreach ($sl as $key => $value) {
        $data[$key]['id'] = $value['id'];
        $data[$key]['name'] = $value['name'];
      }
    }else{
      $data['msg'] = 'fail';
    }
    $this->ajaxReturn($data);
  }
  /**
   *发布专题
   */
   public function addSpecial(){
     $Special = D('Special');
     /*展示图片*/
     $file1 = $_FILES["photo"];
     $picture = '';
     if(!empty($file1["name"])) {
       $picture = UploadImageByMySelf('special',$file1);
     }
     $Special->author = $_POST['author'];
     $Special->cateid = $_POST['cateid'];;
     $Special->title = $_POST['title'];
     $Special->content = $_POST['content'];
     $Special->img = $picture;
     $Special->addtime = time();
     $res = $Special->add();
     if($res)
	     $this->success('新增成功', __CONTROLLER__.'/splist');
   }
   /**
   *专题内容
   */
   public function getSpContent(){
     $id = $_GET['id'];
     $Special = D('Special');
     $ninfo = $Special->where('id='.$id)->field('content')->find();
     $this->assign('ninfo',$ninfo);
     $this->display('spcontent');
   }
   /**
    * 修改专题
    */
    public function EditSpecial(){
      $Special = D('Special');
      $Special->title = $_POST['title'];
      if($_POST['description'])
        $Special->content = $_POST['description'];
      $file1 = $_FILES["photo"];
  		$picture = '';
  		if(!empty($file1["name"])) {
  			$picture = UploadImageByMySelf('special',$file1);
        $Special->img = $picture;
  		}
      $Special->author = $_POST['author'];
      $Special->addtime = strtotime($_POST['addtime']);
      $Special->where('id='.$_POST['spid'])->save();
      $date['code'] = '200';
      $this->ajaxReturn($date);
    }
    /**
     * 获得专题内容去编辑
     */
     public function getSpecialContentToEdit(){
       $spid = $_POST['spid'];
       $Special = D('Special');
       $content = $Special->where('id='.$spid)->getField('content');
       $data['content'] = $content;
       $this->ajaxReturn($data);
     }
     /**
      * 删除专题
      */
      public function delSpecial(){
        $spid = $_POST['spid'];
     		$Special = D('Special');
     		$re = $Special->where('id='.$spid)->delete();
     		if($re){
     			$data['msg'] = 'success';
     		}else{
     			$data['msg'] = 'fail';
     		}
     		$this->ajaxReturn($data);
      }
}
