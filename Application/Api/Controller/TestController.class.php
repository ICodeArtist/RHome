<?php
namespace Api\Controller;
use Think\Controller;
class TestController extends Controller {
    public function __construct(){
      header("content-type:text/html;charset=utf8");
    }
    public function getHistory(){
      vendor("RC.rongcloud");
			$appKey = '3argexb630uke';
			$appSecret = 'loGLEwWMJAUL';
			$RongCloud = new \RongCloud($appKey,$appSecret);
      for($i=0;$i<24;$i++){
        $day = date('Ymd',time()-86400);
        $date = $day.'00'+$i;
        $result = $RongCloud->message()->getHistory($date);
        $re = json_decode($result,true);
        if($re['url']){
          $url = $re['url'];
          $filename = GetRCMessageHistory($url);//解压后的聊天记录文件名
          $file_path = "./Public/Upload/MessageHistory/unzip/".$filename;
          //var_dump($file_path);
          if(file_exists($file_path)){
           $longstr = file_get_contents($file_path);//将整个文件内容读入到一个字符串中
           $strArray = explode("\n", rtrim($longstr,"\n"));
           foreach ($strArray as $key => $value){
             $str = substr($value, 19);
             $data = json_decode($str, true);//每条记录的array();
             var_dump($data['appId']);
           }
         }
       }
     }
   }

   // public function setPointToZero(){
   //   $Point = D('Point');
   //   $Point->sum = 0;
   //   $Point->where('1=1')->save();
   // }

   // public function setPoint(){
   //   $Users = D('Users');
   //   $Dynamic = D('Dynamic');
   //   $Point = D('Point');
   //   $uArr = $Users->where('aut=1 and ifdelete=0')->field('uid')->select();
   //   foreach ($uArr as $key => $value) {
   //     $uid = $value['uid'];
   //     $num = $Dynamic->where('cateid != 0 and uid='.$uid)->count();
   //     if($num>0){
   //       $point = $num * 2;
   //       $Point->where('uid='.$uid)->setInc('sum',$point);
   //     }
   //   }
   }
 }
