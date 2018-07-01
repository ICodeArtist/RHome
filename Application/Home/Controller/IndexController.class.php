<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function hello($name='thinkphp'){
    	$Data = M('data');
    	$re = $Data->find(1);
		$this->assign('name',$name);
		$this->assign('re',$re);
		$this->display();
	}
  public function index(){
		echo 'index for tk';
	}
}
