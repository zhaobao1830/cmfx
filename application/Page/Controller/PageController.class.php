<?php
namespace Page\Controller;
use Common\Controller\AdminbaseController;
class PageController extends AdminbaseController {
    public function index(){
        $this->show('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px } a,a:hover{color:blue;}</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>ThinkPHP</b>！</p><br/>版本 V{$Think.version}</div><script type="text/javascript" src="http://ad.topthink.com/Public/static/client.js"></script><thinkad id="ad_55e75dfae343f5a1"></thinkad><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script>','utf-8');
    }
    public function main(){
        $this->display('index');
    }
      
     public function editpage(){
        $oid=$_POST['oid'];
        $data['page']=$_POST['page'];  //修改组织节点可以查看的页面    
         $loginuid=$_SESSION['ADMIN_ID'];         
         $user=M('Users');            
         $nowuser=$user->where('id='.$loginuid)->find();
         
         $nowuseroid=$nowuser['oid'];
         $org=M('Orginization');
         $pid=$org->where('id='.$oid)->getField('parentid');
         if($pid!=0){
             $ppid=$org->where('id='.$pid)->getField('parentid');
         }         
         $pidarray=array();
         if($pid){
            array_push($pidarray,$pid);
         }
         if($ppid){
             array_push($pidarray,$ppid);
         }
         
         //如果是超级管理员或者是上级管理员，则允许分配
       if($nowuser['isadmin']==2 ||in_array($nowuser['oid'],$pidarray)){    
            if($org->where('id='.$oid)->save($data)){
                $return['state']=true;
                $return['msg']='修改成功';
            }else{
                $return['state']=false;
                $return['msg']='修改失败';
            }
       }else{
             $return['state']=false;
             $return['msg']='抱歉，您无权限修改';
       }
        echo json_encode($return);
        die;
    }
    
     
    //页面选择页
    public function selectpage(){
      $user=M('Users');    
      $uid=$_SESSION['ADMIN_ID'];            
      $userinfo=$user->where('id='.$uid)->find(); 
      $return=array();
      $org=M('Orginization');
      $page=M('Page');
      if($userinfo['isadmin'] && $userinfo['isadmin']==1){//如果isadmin不为1，说明当前用户是某个组织的管理员                   
           $oid=$userinfo['oid'];        
           $result=$org->where('id='.$oid)->find();                       
           $data=$org->select();              
           $return=$this->getChildwithoutuser($data, $oid); 
           $orginfo=$org->where('id='.$oid)->find();
           $map['id']=array('in',$orginfo['page']);
           $currentpage= $page->where($map)->select();
           $return['currentpage']=$currentpage;  //当前已有权限页面     
           $allpage=$page->select();       //所有页面
           $return['allpage']=$allpage;
           array_push($return,$result);      //组织树  
      }elseif($userinfo['isadmin']==2) {//isadmin=2 说明为超级管理员            
           $return=$org->select();                            
           $return['currentpage']=$page->select();
           $return['allpage']=$page->select();
        }else{//当前用户为普通用户
           //$return['currentpage']=$userinfo['page'];
           $return['allpage']=$page->select();
      }
      $this->display('return',json_encode($return));
      $this->display('page');
     
    }
 
    
    //添加页面
    public function addpage(){
        $data['pagename']=$_POST['pagename'];
        $data['pageurl']=$_POST['pageurl'];
        $data['reporturl']=$_POST['reporturl'];
        $data['info']=$_POST['info'];
        $page=$M('Page');
        if($page->save($data)){
            $return['state']=true;
            $return['msg']='页面添加成功';
        }else{
            $return['state']=false;
            $return['msg']='页面添加失败';
        }
        echo json_encode($return);
        die;
    }
    
   
   
}