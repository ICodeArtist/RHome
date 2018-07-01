<?php
namespace Home\Controller;
use Think\Controller;
class FormController extends Controller{

	public function add(){
		$name =  $this->fetch('Index:edit');
		$this->show($name);
		// $this->assign('n',$name);
		// $this->display('Tk/tk');
	}
	public function insert(){
		$Form = D('Form');
		if($Form->create()) {
			$result = $Form->add();
			if($result) {
				$this->success('数据添加成功！');
			}else{
				$this->error('数据添加错误！');
			}
		}else{
			$this->error($Form->getError());
		}
	}

	public function read(){
		if(IS_GET){
			$data['status'] = $_POST['name'];
			$data['content'] = $_POST['password'];
		}
		
		$this->ajaxReturn($data);
	}
	public function year($year=2017,$month=05){
		echo 'year='.$year.' and month='.$month;
	}
}