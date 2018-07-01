<?php
namespace Admin\Controller;
use Think\Controller;
class IndexController extends Controller {
    /**
    *后台入口
    */
    public function index(){
      if(!IS_POST){
        //if(!session('admininfo'))
        $this->display('login');
      }
      else{
        //$CommunityCategroy = D('CommunityCategroy');
        $username = trim($_POST['adminname']);
        $password = $_POST['adminpassword'];
        $res = MemberLogin($username,$password,'1'); //管理账号登录
        if($res['code'] != '200'){
          $this->error($res['date']);
        }else{
          $user = $res['date'];

          /*权限*/
          //一级
          $RoleMenu = D('RoleMenu');
          $rm = $RoleMenu->where('1=1')->field('id,name,sign')->select();
          //二级
          $RoleList = D('RoleList');
          $rl = $RoleList->where('1=1')->field('id,parent,name,href')->select();
          //权限展示
          $priv = array();
          if($user['permission'] == '1'){
            foreach ($rl as $key => $value) {
              $priv[$value['parent']][$key]['id'] = $value['id'];
              $priv[$value['parent']][$key]['name'] = $value['name'];
              $priv[$value['parent']][$key]['href'] = $value['href'];
            }
          }else{
            $role = explode(',', $user['role']);
            foreach ($rl as $key => $value) {
              foreach ($role as $va) {
                if($va == $value['id']){
                  $priv[$value['parent']][$key]['id'] = $value['id'];
                  $priv[$value['parent']][$key]['name'] = $value['name'];
                  $priv[$value['parent']][$key]['href'] = $value['href'];
                }
              }
            }
          }
          $this->assign('rm',$rm);
          $this->assign('priv',$priv);
          /*end*/
          cookie('adminorgid',$user['orgid']);
          session('uid',$user['uid']);
          session('admininfo',$user['account']);
          session('permission',$user['permission']);
          $this->assign('admininfo',$user);
          $this->display('index');
        }
      }
    }

    public function welcome(){
      $OrgParty = D('OrgParty');
      $p = $OrgParty->field('orgid,party,secretary,contact,telephone,address')->select();
      $this->assign('p',$p);
      $this->display('welcome');
    }

}
