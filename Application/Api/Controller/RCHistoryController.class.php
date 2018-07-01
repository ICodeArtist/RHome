<?php
namespace Api\Controller;
use Think\Controller;
class RCHistoryController extends Controller {
    public function __construct(){
      header("content-type:text/html;charset=utf8");
    }
    public function getHistory(){
      $MeetingHistory = D('MeetingHistory');
      vendor("RC.rongcloud");
      $appKey = C('appKey');
  		$appSecret = C('appSecret');
			$RongCloud = new \RongCloud($appKey,$appSecret);
      $l = 0;
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
             $msgUID = $data['msgUID'];//唯一聊天记录id
             $mUID = $MeetingHistory->where('msgid="'.$msgUID.'"')->count();
             //只记录文本消息
             if($data['classname'] == 'RC:TxtMsg' && ($data['source'] == 'Android' || $data['source'] == 'iOS')
                && $data['content']['content'] && $mUID==0){
               $MeetingHistory->msgid = $data['msgUID'];
               $MeetingHistory->fromuid = $data['fromUserId'];
               $MeetingHistory->touid = $data['targetId'] ;
               $MeetingHistory->targetType = $data['targetType'];
               $MeetingHistory->groupid = $data['GroupId'];
               $MeetingHistory->classname = $data['classname'];
               $MeetingHistory->content = $data['content']['content'];
               $MeetingHistory->dateTime = $data['dateTime'];
               $MeetingHistory->source = $data['source'];
               $MeetingHistory->add();
               $l++;
             }
           }
         }
       }
     }
     $log['lognum'] = '记录了'.$l.'条';
     RH_log($log,'his');
   }
 }
