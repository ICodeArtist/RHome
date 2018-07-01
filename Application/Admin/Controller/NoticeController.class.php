<?php
namespace Admin\Controller;
use Think\Controllers;
class NoticeController extends Controllers {

	public function getNoticeList(){
		$nowuid = session('uid');
		$Notice = D('Notice');
		$NoticeRead = D('NoticeRead');
		$nlist = $Notice->where('1=1')->field('id,uid,title,addtime,author,begin,end,content')->select();
		foreach ($nlist as $key => $value) {
			$nlist[$key]['author'] = $value['author'];
			$nlist[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
			$nlist[$key]['begintime'] = $value['begin']?date('Y-m-d',$value['begin']):date('Y-m-d',$value['addtime']);
			$nlist[$key]['endtime'] = $value['end']?date('Y-m-d',$value['end']):"没设置";
			$nlist[$key]['browse'] = $NoticeRead->where('noticeid='.$value['id'])->count();
			if($nowuid == $value['uid']){
				$nlist[$key]['candel'] = '1';
			}else{
				$nlist[$key]['candel'] = '0';
			}
		}
    $this->assign('nlist',$nlist);
    $this->display('list');
	}
	//公告内容
	public function getContent(){
		$id = $_GET['id'];
		$Notice = D('Notice');
		$ninfo = $Notice->where('id='.$id)->field('content')->find();
		$this->assign('ninfo',$ninfo);
		$this->display('content');
	}
	//删除公告
	public function delNotice(){
		$nid  = $_POST['nid'];
		$Notice = D('Notice');
		$re = $Notice->where('id='.$nid)->delete();
		if($re){
			$data['msg'] = 'success';
		}else{
			$data['msg'] = 'fail';
		}
		$this->ajaxReturn($data);
	}
	//发布新公告
	public function addNotice(){
		$Notice = D('Notice');
		$Admins = D('Admins');

		$account = session('admininfo');
		$query['account'] = $account;
		$uid = $Admins->where($query)->getField('uid');
		$Notice->uid = $uid;
		$Notice->author = $_POST['author'];
		$Notice->title = $_POST['title'];
		$Notice->content = $_POST['content'];
		$Notice->addtime = time();
		if(isset($_POST['begin']) && $_POST['begin']){
			$Notice->begin = strtotime($_POST['begin']);
		}
		if(isset($_POST['end']) && $_POST['end']){
			$Notice->end = strtotime($_POST['end'])+86400;
		}
		$res = $Notice->add();
		if($res)
			$this->getNoticeList();
	}
	//
	public function EditNotice(){
		$Notice = D('Notice');
		$Notice->title = $_POST['title'];
		if($_POST['description'])
			$Notice->content = $_POST['description'];
		$Notice->begin = strtotime($_POST['begin']);
		$Notice->end = strtotime($_POST['end']);
		$Notice->author = $_POST['author'];
		$Notice->where('id='.$_POST['nid'])->save();
		$date['code'] = '200';
		$this->ajaxReturn($date);
	}
}
?>
