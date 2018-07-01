<?php
namespace Admin\Controller;
use Think\Controller;
class MediaController extends Controller {
    /**
    *App轮播图
    */
    public function carousel(){
      $Carousel = D('Carousel');
      $ImageUrl = C('ImageUrl');
      $CArr = $Carousel->select();
      foreach ($CArr as $key => $value) {
        switch ($value['type']) {
          case '0':
            $CArr[$key]['t'] = '纯图片';
            $CArr[$key]['content'] = '';
            break;
          case '1':
            $CArr[$key]['t'] = '外链接';
            $CArr[$key]['content'] = $value['link'];
            break;
          case '2':
            $CArr[$key]['t'] = '公告';
            $CArr[$key]['content'] = $value['itemid'];
            break;
          case '3':
            $CArr[$key]['t'] = '动态';
            $CArr[$key]['content'] = $value['itemid'];
            break;
        }
        unset($CArr[$key]['type']);
        unset($CArr[$key]['link']);
        unset($CArr[$key]['itemid']);
        $CArr[$key]['img'] = $ImageUrl.$value['img'];
        $CArr[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
      }
      $this->assign('nowcommunity',$nowcommunity);
      $this->assign('CArr',$CArr);
      $this->display('carousel');
    }
    //添加轮播图
    public function addCarousel(){
      $Carousel = D('Carousel');
      /*图片处理*/
      $file = $_FILES["photo"];
      $thumbimage_url = "";
      if(!empty($file["name"])) {
        $thumbimage_url = UploadImageByMySelf('carousel',$file);
      }
      $Carousel->type = $_POST['type'];
      $Carousel->itemid = $_POST['itemid'];
      $Carousel->link = $_POST['link'];
      $Carousel->img = $thumbimage_url;
      $Carousel->addtime = time();
      $res = $Carousel->add();
      if($res)
        $this->success('新增成功', __CONTROLLER__.'/carousel');
    }
    //删除轮播图
    public function delCarousel(){
      $id  = $_POST['id'];
  		$Carousel = D('Carousel');
  		$re = $Carousel->where('id='.$id)->delete();
  		if($re){
  			$data['msg'] = 'success';
  		}else{
  			$data['msg'] = 'fail';
  		}
  		$this->ajaxReturn($data);
    }
}
?>
