<?php
/**
*工具类
*/
namespace Api\Controller;
use Think\Controller;
class ToolsController extends Controller {

  public function ResizeAllImage(){
    $basepath = C('public_path');
    $ImageUrl = C('ImageUrl');
    $dirpath = '/Upload/images/dynamic/20171122/';
    $dir = $basepath.$dirpath;  //要获取的目录
  	//先判断指定的路径是不是一个文件夹
  	if (is_dir($dir)){
  		if ($dh = opendir($dir)){
  			while (($file = readdir($dh))!= false){
          //大于1M压缩
          $i = $basepath.$dirpath.$file;
          if($this->isImage($i))
            $imagesize = ceil(filesize($i) / 1000);
          if($imagesize>1000){
    				//文件名的全路径 包含文件名
            $imgurl = $ImageUrl.$dirpath.$file;
            $im = imagecreatefromjpeg($imgurl);
      			ResizeImageReplace($im,$dirpath,$file,0.2);
      			ImageDestroy($im);
          }

  			}
  			closedir($dh);
  		}
  	}
  }
  function isImage($filename){
    $types = '.gif|.jpeg|.png|.bmp'; //定义检查的图片类型
    if(file_exists($filename)){
      if(($info = @getimagesize($filename)))
        return true;
    }else{
      return false;
    }
  }
}
