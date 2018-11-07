<?php

/* * 
 * 系统权限配置，用户角色管理
 */
namespace Orginization\Controller;
use Common\Controller\AdminbaseController;
class OrginizationController extends AdminbaseController {

    protected $child=array();
    function _initialize() {
        parent::_initialize();
        $this->assign("taxonomys",$this->taxonomys);
       
    }
    public function main(){
        $this->index();
    }
    
     //获取用户对应的组织树及权限
   public function index(){     
     $user=M('Users');
      $uid=$_SESSION['ADMIN_ID'];
      $userinfo=$user->where('id='.$uid)->find();    
      $oid=$userinfo['oid']; 
      $return=array();
      $org=M('Orginization');           	
      if($userinfo['isadmin'] && $userinfo['isadmin']==1){//如果isadmin不为1，说明当前用户是某个组织的管理员                                  
           $result=$org->where('id='.$oid)->find();                     
           $data=$org->select();                
           //$return1=$this->getChildwithoutuser($data, $oid);      
           //array_push($return1,$result); 
           $return1=$this->getChildbylevel($result['level']);
           $adminNum=$user->where('oid='.$oid .' and isadmin=1 and user_status=1')->count();
           $return['orglist']=$return1;
           $return['adminNum']=$adminNum;                          
           $return['issuperadmin']='false';    
         
      }elseif($userinfo['isadmin']==2) {//isadmin=2 说明为超级管理员            
           $return1=$org->select();                      
           $return['orglist']=$return1;         
           $return['issuperadmin']='true';
        }else{//当前用户为普通用户
           $return=$userinfo;
         }       
               
      $this->assign('return',json_encode($return));
      $this->display();   
    }
  
    //某个组织下的用户列表
 public function userlist(){
     $oid=$_POST['oid'];    
     $curpage=$_POST['curpage']?$_POST['curpage']:1;
     $psize=$_POST['psize']?$_POST['psize']:20;      
     $offset=($curpage-1)*$psize;
     $user=M('Users');   
     $count=$user->field('id,user_login,user_nicename,user_email')->where('oid='.$oid .' and isadmin=0')->count();
     $userlist=$user->limit($offset.','.$psize)->order('id asc')->field('id,user_login,user_nicename,user_email')->where('oid='.$oid .' and isadmin=0')->select();
     $totalpage=ceil($count/$psize);
     $return=array();  
     $return['count']=$count;
     $return['totalpage']=$totalpage;     
     $return['userlist']=$userlist;
     $orgadmin=$user->where('oid='.$oid .' and isadmin!=0 and user_status=1')->find();
     $return['orgadmin']=$orgadmin;    
     echo json_encode($return); 
     die;   
 }   
//无组织用户列表
  public function noorguser(){
        $user=M('Users');            
        $curpage=$_POST['curpage']?$_POST['curpage']:1;
        $psize=$_POST['psize']?$_POST['psize']:20;    
        $offset=($curpage-1)*$psize;           
        $userlist=$user->limit($offset.','.$psize)->order('id asc')->field('id,user_login,user_nicename,user_email')->where('oid=0 and user_status=1')->select();                                                   
        $count=$user->field('id,user_login,user_nicename,user_email')->where('oid=0 and user_status=1')->count();
        $totalpage=ceil($count/$psize);
        $return['totalpage']=$totalpage; 
        $return['count']=$count;
        $return['userlist']=$userlist;
        echo json_encode($return);
        die;     
    }

    
    //不返回组织节点下的用户列表
    public function authority(){
       $user=M('Users');    
      $uid=$_SESSION['ADMIN_ID'];
      $userinfo=$user->where('id='.$uid)->find();    
      $oid=$userinfo['oid']; 
      $return=array();
      $org=M('Orginization');          	
      if($userinfo['isadmin'] && $userinfo['isadmin']==1){//如果isadmin不为1，说明当前用户是某个组织的管理员                                  
           $result=$org->where('id='.$oid)->find();                     
           $data=$org->select();                
           //$return1=$this->getChildwithoutuser($data, $oid);  
           $return1=$this->getChildbylevel($result['level']);
           //array_push($return1,$result);                 
           $adminNum=$user->where('oid='.$oid .' and isadmin=1 and user_status=1')->count();
           $return['orglist']=$return1;
           $return['adminNum']=$adminNum;                          
           $return['issuperadmin']='false';    
         
      }elseif($userinfo['isadmin']==2) {//isadmin=2 说明为超级管理员            
           $return1=$org->select();                      
           $return['orglist']=$return1;         
           $return['issuperadmin']='true';
        }else{//当前用户为普通用户
           $return=$userinfo;
         }                     
      $this->assign('return',json_encode($return));
      $this->display();   
     
    }
    
    //查询某个组织节点的父级结点权限    
    private function menucache($oid) { 
         $adminid=$_SESSION['ADMIN_ID'];   
         $user=M("Users");
         $loginuser=$user->where('id='.$adminid)->find();                   
         $menu=M('Menu');
            if($loginuser['isadmin']==2 && $oid==$loginuser['oid']){  //如果当前登录的是超级管理员，则显示所有的权限
              // $data = $menu->where('status=1 and type=1')->select();
               $data = $menu->where('status=1 ')->select();
            }else{//如果当前登录的是某个单位的管理员，则显示其当前父类的所拥有的权限
                $org=M('Orginization');
                $currentorg=$org->where('id='.$oid)->find();               
                $authid=$org->where('id='.$currentorg['parentid'])->getField('authority');                       
                if($authid){
                    $ids=explode(',', $authid);     
                    $where['id']=array('in', $ids);
                    $where['status']=array('eq',1);
                    //$where['type']=array('eq',1);
                }else{
                    $where['id']=array('eq',-1);
                    $where['status']=array('eq',1);
                    //$where['type']=array('eq',1);
                }            
                $data=$menu->where($where)->select();
            }
      
        return $data;
    }
    
     public function getorgauthority(){               
       //组织id
        $oid = I("post.oid");       
        if (empty($oid)) {
        	$this->error("参数错误！");
        }       
        $result = $this->menucache($oid); 
        $newmenus=array();       
        $org=M('Orginization');
        $authid=$org->where('id='.$oid)->getField('authority');
        if($authid){
            $autharray= explode(',', $authid);
            $map['id']=array('in',$autharray); 
            $map['status']=array('eq',1);
	    //$map['type']=array('eq',1);
        }else{
            $map['id']=array('eq',-1);
            $map['status']=array('eq',1);
	   // $map['type']=array('eq',1);
        }
        $menus=M('Menu');
        $priv_data=$menus->where($map)->getField("id",true);//获取权限表数据对应的id 用户验证是否拥有该权限_is_checked教研       
       // $curauth=$menus->where($map)->getField("id,parentid,name",true);//获取权限表数据
        $curauth=$menus->where($map)->select();//获取权限表数据
        foreach ($result as $m){
        	$newmenus[$m['id']]=$m;
        }       
       // foreach ($result as $n => $t) {
        	//$result[$n]['checked'] = ($this->_is_checked($t, $priv_data)) ? 'checked' : '';
        	//$result[$n]['level'] = $this->_get_level($t['id'], $newmenus);        	
       // }
        if(empty($curauth)){
            $curauth=array();
        }
        $return['allauthority']=$result;
        $return['currentauthority']= ($curauth);         
        echo json_encode($return);
        die;
         
     }
     /**
     *  检查指定菜单是否有权限
     * @param array $menu menu表中数组
     * @param int $roleid 需要检查的角色ID
     */
    private function _is_checked($menu, $priv_data) { 	
    	if($priv_data){
	    if(in_array($menu['id'],$priv_data)){
                return true;
            }else {
                return false;
            }    	
    	}else{
    		return false;
    	}   	
    }
    
     /**
     * 获取菜单深度
     * @param $id
     * @param $array
     * @param $i
     */
    protected function _get_level($id, $array = array(), $i = 0) {
        
        	if ($array[$id]['parentid']==0 || empty($array[$array[$id]['parentid']]) || $array[$id]['parentid']==$id){
        		return  $i;
        	}else{
        		$i++;
        		return $this->_get_level($array[$id]['parentid'],$array,$i);
        	}
        		
    }
    //获取某个节点的权限信息
    public function getorgauthority1(){
        $curpage=$_POST['curpage']?$_POST['curpage']:1;
        $psize=$_POST['psize']?$_POST['psize']:20;     
        $offset=($curpage-1)*$psize;
        $loginid=$_SESSION['ADMIN_ID'];
        $oid=$_POST['oid'];         
        $org=M('Orginization');
        $user=M('Users');
        $authRule=M('Menu');
        $userinfo=$user->where('id='.$loginid)->find();     
        $data=array();
        $allauth=array();
        
        if($userinfo['isadmin']==2 && $oid==$userinfo['oid']){
            $count=$authRule->count();          
            $data=$authRule->limit($offset.','.$psize)->select();              
            $return['allauthority']=$data;
            $return['currentauthority']=$data; 
            $totalpage=ceil($count/$psize);   
            $return['count']=$count;
            $return['totalpage']=$totalpage;     
        }else{
            $orginfo=$org->where('id='.$oid)->find();
            if($oid==$userinfo['oid'] ){               
                $pauth=$orginfo;
            }else{ 
                //如果当前节点是一级节点
                if(strlen($orginfo['level'])==3){
                    $authids=$authRule->getField('id',true);
                    $pauth['authority']=implode(',',$authids);
                }
                if(strlen($orginfo['level'])>3){
                   $pauth=$org->where('id='.$orginfo['parentid'])->find();                  
                }              
            }
            if(!empty($pauth['authority'])){
               if(!empty($orginfo['authority'])){                  
                    $map['id']=array('in',$orginfo['authority']);
                    $data=$authRule->where($map)->select();   //当前节点权限列表               
                      
                }else{              
                    $data=array();                    
                }
                $map1['id']=array('in',$pauth['authority']);
                $count=$authRule->where($map1)->count();                     
                $allauth=$authRule->limit($offset.','.$psize)->where($map1)->select();                   
                $totalpage=ceil($count/$psize);   
                $return['count']=$count;
                $return['totalpage']=$totalpage;   
            }else{
                $allauth=array();
                $data=array();
                $return['count']=0;
                $return['totalpage']=0;  
            }
            $return['allauthority']=$allauth;
            $return['currentauthority']=$data;  
        }
            
            
        echo json_encode($return);
        die;
    }
    //获取某个节点的父节点列表
    public function getParentid($oid=0){
       // $oid='15277671';  //001002001005
        $org=M('Orginization');
        $orginfo=$org->where('id='.$oid)->find();
        $level=substr($orginfo['level'],0,strlen($orginfo['level'])-3);
        $parray=array();
        while($level>0){
            $pid=$org->where('level='.$level)->getField('id');
            array_push($parray,$pid);
            $level=substr($level,0,strlen($level)-3);
        }     
        return $parray;
    }
    
    //修改某个组织节点的权限
    public function editauthority(){
        $oid=$_POST['oid'];
        $data['authority']=$_POST['authority'];      
         $loginuid=$_SESSION['ADMIN_ID'];         
         $user=M('Users');            
         $nowuser=$user->where('id='.$loginuid)->find();
         
         $nowuseroid=$nowuser['oid'];
         $org=M('Orginization');
        if($nowuser['isadmin']!=2){
            $pidarray=$this->getParentid($oid);
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
    
   public function getChildbylevel($level='001'){      
       $org=M('Orginization');
       $this->child=$org->where("level like '$level%'")->select();
      
       return $this->child;
   }
    
    public function getChildwithoutuser($data,$oid){      
        
        foreach($data as $k => $v)
        {                    
           if($v['parentid'] == $oid){ //父亲找到儿子               
              $this->child[]=$v;
              $this->getChildwithoutuser($data, $v['id']);                                         
          }
        }       
        return $this->child;      
    }
  
  
   
   //为组织节点添加管理员
    public function addadmin(){
        $oid=$_POST['oid'];
        $adminname=$_POST['adminname'];      
        $user=M('Users');
        $userinfo=$user->where("user_login='$adminname' and user_status=1")->find();
        $return=array();
        if($userinfo!=null){         
                $userdata['oid']=$oid;
                $userdata['isadmin']=1;
                if($user->where('id='.$userinfo['id'])->data($userdata)->save()){
                    $return['state']='success';
                    $return['msg']="添加成功！";
                      //$this->success("添加成功！");
                }           
                else{
                    $return['state']='fail';
                    $return['msg']="添加失败！";
                    // $this->success("添加失败！");
                }       			          
        }else{
              $return['state']='fail';
              $return['msg']="用户名不存在，请检查用户名是否正确或者用户已禁用！";
              //$this->error("用户名不存在，请检查用户名是否正确或者用户已禁用！");	
        }
        echo json_encode($return);
        die;
    }
    
  
    
    //重新设置管理员
    public function resetadmin(){
        $oid=$_POST['oid'];
        $uid=$_POST['uid'];//设置为管理员的人员id
        $orgadminid=$_POST['adminid'];
      
        $user=M('Users');
    
        if(count(explode($uid,','))>1){
            $return['state']=false;
            $return['msg']='设置失败，只能设置一个管理员';
            echo json_encode($return);
            die;
        }
        $userinfo=$user->where('id='.$uid)->find();
        if($userinfo['isadmin']==1){
            $return['state']=false;
            $return['msg']='设置失败，当前用户已是管理员';
            echo json_encode($return);
            die;
        }
         $loginuid=$_SESSION['ADMIN_ID'];                     
         $nowuser=$user->where('id='.$loginuid)->find();        
         $nowuseroid=$nowuser['oid'];
         $org=M('Orginization');             
         $pidarray=$this->getParentid($oid);      
         
         //如果是超级管理员或者是上级管理员，则允许分配
       if($nowuser['isadmin']==2 ||in_array($nowuser['oid'],$pidarray)){  
           //没有选择管理员
           if(empty($orgadminid)){
               $hasadmin=$user->where('oid='.$oid .' and isadmin!=0')->find();               
               if($hasadmin){//没有选个要修改的管理员，且已存在管理员
                   $return['msg']='该组织节点已有管理员，如果要修改，请选择要修改的管理员';
                   $return['state']=false;
                   echo json_encode($return);
                   die;
               }else{//没有选择要修改的管理员，也不存在管理员
                   $data['oid']=$oid;
                   $data['isadmin']=1;  
                   if($user->where('id='.$uid)->save($data)){
                        $return['state']=true;
                        $return['msg']='设置成功';
                       //返回用户列表及修改后的管理员信息
                        $userlist=$user->field('id,user_login,user_nicename,user_email')->where('oid='.$oid .' and isadmin=0')->select();                   
                        $useradmin=$user->where('oid='.$oid .' and isadmin=1 and user_status=1')->select();
                        $return['userlist']=$userlist;
                        $return['useradmin']=$useradmin;
                   }else{
                        $return['state']=false;
                        $return['msg']='设置失败';
                        $user->where('id='.$orgadminid)->save($data);
                   }
                   echo json_encode($return);
                   die;
               }
           }else{
            $da['isadmin']=0;
            if($user->where('id='.$orgadminid)->save($da)){
                 $data['oid']=$oid;
                 $data['isadmin']=1;           
                 if($user->where('id='.$uid)->save($data)){
                     $return['state']=true;
                     $return['msg']='设置成功';
                     $userlist=$user->field('id,user_login,user_nicename,user_email')->where('oid='.$oid .' and isadmin=0')->select();                   
                     $useradmin=$user->where('oid='.$oid .' and isadmin=1 and user_status=1')->select();
                     $return['userlist']=$userlist;
                     $return['useradmin']=$useradmin;
                  }else{
                     $return['state']=false;
                     $return['msg']='设置失败';
                     $user->where('id='.$orgadminid)->save($data);
                 }
            }else{
                $return['state']=false;
                $return['msg']='设置失败';
            }
           }  
       }else{
           $return['state']=false;
           $return['msg']='抱歉，您无权设置';
       }
        echo json_encode($return);
        die;
    }
  
   public function addorg(){
        $uid=$_SESSION['ADMIN_ID'];
        $id=$_GET['id'];
        $pid=$_GET['pId'];
        $user=M('Users');
        $userinfo=$user->where('id='.$uid)->find();
        $org=M('Orginization');
        $oid=$userinfo['oid'];        
        $result=$org->where('id='.$oid)->find();                       
        $data=$org->select();               
        //$return=$this->getChildwithoutuser($data, $oid); 
        $return=$this->getChildbylevel($result['level']);
        //array_push($return,$result);
        $noorgusers=$this->noorgusers();
        
        $this->assign('pid',$pid);
        $this->assign('id',$id);
        $this->assign('noorguser',json_encode($noorgusers));
        $this->assign('orginfo',json_encode($return));
        $this->display('addOrginization');
    }
    public function noorgusers(){
        $user=M('Users');         
        $count=$user->field('id,user_login,user_nicename,user_email')->where('oid=0 and user_status=1')->count();     
        $page=$this->page($count,4);      
        $userlist=$user->limit($page->firstRow . ',' . $page->listRows)->order('id asc')->field('id,user_login,user_nicename,user_email')->where('oid=0 and user_status=1')->select();                   
        $this->assign('page',$page->show('Admin'));
        return $userlist;      
    }
    
   //添加组织节点，并分配管理员
    public function addorginization(){
        $data['parentid']=$_POST['parentid'];
        $data['org_name']=$_POST['orgname'];
        $data['org_info']=$_POST['orginfo'];     
        $uid=$_POST['adminid'];
        $loginid=$_SESSION['ADMIN_ID'];
         
        $return=array();
        $user=M('Users');
        $userinfo= $user->where('id='.$uid)->find();
        $nowuser=$user->where('id='.$loginid)->find();
        $org=M('Orginization');
        $orgname=$_POST['orgname'];
         $map['org_name']=array('eq',$orgname);
         $isexist= $org->where($map)->select();          
       
         if($isexist){
              $return['state']='fail';
              $return['msg']="组织名称已存在！";
              echo json_encode($return);
              die;
         }
           if(empty($uid)){
              $return['state']='fail';
              $return['msg']="请选择管理员！";
              echo json_encode($return);
              die;	    
         }              
        if($userinfo['isadmin']){
             $return['state']='fail';
             $return['msg']="该用户已是管理员！";
             echo json_encode($return);
             die;
        }
       
         if(empty($data['org_name'])){
              $return['state']='fail';
              $return['msg']="请填写组织名称！";
              echo json_encode($return);
              die;	    
         }   
         if($org->add($data)){ 
              $oid=$org->getLastInsID(); 
            $da['isadmin']=1;
            $da['oid']=$oid;
            if($user->where('id='.$uid)->save($da)){
                //$re=$org->where('id='.$oid)->find();
                //$re['oid']=$oid;
                $loginoid=$nowuser['oid'];        
                $result=$org->where('id='.$loginoid)->find();                       
                $data=$org->select();              
                //$return=$this->getChildwithoutuser($data, $loginoid); 
                //array_push($return,$result);
                $return=$this->getChildbylevel($result['level']);
                $re['orglist']=$return;
                $re['state']='success';
                $re['msg']='添加成功';
                echo json_encode($re);
                die;
            } else{              
                 $return['state']='fail';
                 $return['msg']="添加管理员失败,请重新添加";   
                 $org->where('id='.$oid)->delete();
            }                 
         }else{
             $return['state']='fail';
             $return['msg']="添加组织节点失败";
            //$this->success('添加失败'); 
         }
         echo json_encode($return);die;
    }
    
    
    //删除组织节点
     public function delorginization(){
         $oid=$_POST['oid'];   
         $location=$_POST['location'];
         $return=array();
         $loginuid=$_SESSION['ADMIN_ID'];
         $user=M('Users');
         $nowuser=$user->where('id='.$loginuid)->find();
         $org=M('Orginization');
      
        $pidarray=$this->getParentid($oid);      
         
         //如果是超级管理员或者是上级管理员，则允许删除
       if($nowuser['isadmin']==2 ||in_array($nowuser['oid'],$pidarray)){           
         $result=$org->where("id=".$oid)->delete();
         if($result){
             $user=M('Users');
             $data['oid']=0;
             $data['isadmin']=0;
             $user->where('oid='.$oid)->save($data);
             
             $return['state']=true;
             $return['msg']='删除成功';
            // if($location=='authority'){
               //  $auth=
            // }else{
                 
           //  }
             //$this->success('删除成功');
         }else{
             $return['state']=false;
             $return['msg']='删除失败';
            //$this->success('删除失败'); 
         }  
       }else{
           $return['state']=false;
           $return['msg']='抱歉，您无权限删除';         
       }
         echo json_encode($return);
         die;
    }
    
    //为无组织用户添加组织
    public function allotorg(){           
        $oid=$_POST['oid'];
        $uid=$_POST['uid'];     
        $ids=explode(',',$uid);
        $loginid=$_SESSION['ADMIN_ID'];
         $user=M('Users');
         $nowuser=$user->where('id='.$loginid)->find();
       
         //如果当前节点的管理员，则允许添加
       if($nowuser['oid']==$oid ||$nowuser['isadmin']==2){
           $data['oid']=$oid;         
           $map['id']=array('in',$ids);
           if($user->where($map)->save($data)){
               $return['state']=true;
               $return['msg']='添加成功';
               $return['noorgusers']=$this->noorgusers();
               $userlist=$user->field('id,user_login,user_nicename,user_email')->where('oid='.$oid .' and isadmin=0')->select();
               $return['userlist']=$userlist;
           }else{
               $return['state']=false;
               $return['msg']='添加失败';
           }
       }else{
           $return['state']=false;
           $return['msg']='抱歉，您无权限添加';         
       }
    
         echo json_encode($return);
         die;
    }
        
    
    //将人员从组织节点移除,只有自己的管理员或超级管理员可以删除本节点下的成员
    public function delfromorg(){
        $oid=$_POST['oid'];
        $uid=$_POST['uid'];   
        $ids=explode(',',$uid);
        $data['oid']=0;
        $data['isadmin']=0;
        $data['authority']=null;       
        $loginuid=$_SESSION['ADMIN_ID'];    
        $user=M('Users');  
        $nowuser=$user->where('id='.$loginuid)->find();      
         //只有自己的管理员可以删除本节点下的成员
       if($nowuser['oid']==$oid ||$nowuser['isadmin']==2){
           $map['id']=array('in',$ids);
           $data['oid']=0;         
            if($user->where($map)->save($data)){
                $return['state']=true;
                $return['msg']='移除成功';
                $return['noorgusers']=$this->noorgusers();
               $userlist=$user->field('id,user_login,user_nicename,user_email')->where('oid='.$oid.' and isadmin=0')->select();
               $return['userlist']=$userlist;
            }else{
                $return['state']=false;
                $return['msg']='移除失败';
            }
       }else{
           $return['state']=false;
           $return['msg']='抱歉，您无权删除';
       }
        echo json_encode($return);
        die;
    }
    //搜索人员列表中的用户
    public function searchuser(){
        $username=$_POST['username'];         
        $oid=$_POST['oid'];       
        $curpage=$_POST['curpage']?$_POST['curpage']:1;
        $psize=$_POST['psize']?$_POST['psize']:3;   
        //$psize=5;
        $offset=($curpage-1)*$psize;
        $user=M('Users');
        if(!empty($username)){          
            $map['user_login']=array('like',"%$username%");
        }      
        $map['oid']=array('eq',$oid); 
        $map['user_status']=array('eq',1); 
        $map['isadmin']=array('neq',1);
        $userlist=$user->limit($offset.','.$psize)->where($map)->select(); 
        $count=$user->where($map)->count(); 
        $totalpage=ceil($count/$psize); 
       // $this->assign("totalpage",$totalpage);
        $return['userlist']=$userlist;
        $return['totalpage']=$totalpage;
         
        echo json_encode($return);
        die;
    }
      //搜索无组织用户
    public function noorgsearch(){
        $username=$_POST['username'];   
        $curpage=$_POST['curpage']?$_POST['curpage']:1;
        $psize=$_POST['psize']?$_POST['psize']:3;   
        //$psize=5;
        $offset=($curpage-1)*$psize;
        $user=M('Users');
        if(!empty($username)){          
            $map['user_login']=array('like',"%$username%");
        }    
        $map['oid']=array('eq',0);
        $map['user_status']=array('eq',1); 
        $userlist=$user->where($map)->limit($offset.','.$psize)->select(); 
        $count=$user->where($map)->count(); 
        $totalpage=ceil($count/$psize);  
        $return['userlist']=$userlist;
        $return['totalpage']=$totalpage;
            
        echo json_encode($return);
        die;
    }
    
    //修改用户所属组织
    public function edituseroid(){
        $oid=$_POST['oid'];
        $uid=$_POST['uid'];      
        $loginid=$_SESSION['ADMIN_ID'];
        $user=M('Users');
        $nowuser=$user->where('id='.$loginid)->find();
        $org=M('Orginization');
        $pidarray=$this->getParentid($oid);      
              
         //如果是超级管理员或者是上级管理员，则允许删除
       if($nowuser['isadmin']==2 ||in_array($nowuser['oid'],$pidarray)){ 
           $data['oid']=$oid;
           if($user->where('id='.$uid)->save($data)){
               $return['state']=true;
               $return['msg']='修改成功';
           }else{
               $return['state']=false;
               $return['msg']='修改失败';
           }
       } else{
             $return['state']=false;
             $return['msg']='抱歉，您无权修改';
       }  
       echo json_encode($return);
       die;
    }
    
    
    //进入到修改组织节点信息页，返回回显信息
    public function editorg(){
      $oid=$_GET['oid'];    
      $user=M('Users');
      $uid=$_SESSION['ADMIN_ID'];
      $userinfo=$user->where('id='.$uid)->find();    
      $uoid=$userinfo['oid']; 
      $return=array();
      $org=M('Orginization'); 
     //如果当前用户是某个组织的管理员  或超级管理员 
      if(($userinfo['isadmin'] && $userinfo['isadmin']==1)||$userinfo['isadmin']==2){                                     
          $result=$org->where('id='.$uoid)->find();           
           $data=$org->select();                
           //$return1=$this->getChildwithoutuser($data, $uoid);     
           //array_push($return1,$result);
           $return1=$this->getChildbylevel($result['level']);
           $return['orglist']=$return1;                                         
      }elseif($userinfo['isadmin']==2) {//isadmin=2 说明为超级管理员            
           $return1=$org->select();                      
           $return['orglist']=$return1;         
           $return['issuperadmin']='true';
        }else{//当前用户为普通用户
           $return=$userinfo;
         }          
        
        $orginfo=$org->where('id= '.$oid)->find();   
        $this->assign('oid',$oid);
        $this->assign('orginfo',json_encode($return));
        $this->assign('data',json_encode($orginfo));
        $this->display('editorg');
    }
    
    //编辑组织节点
    public function editorginization(){
        $oid=$_POST['oid'];
        //$data['parentid']=$_POST['parentid'];
        $data['org_name']=$_POST['org_name'];
        $data['org_info']=$_POST['org_info'];
        //$data['authority']=$_POST['authority'];
        $return=array();
        $org=M('Orginization');
         $loginuid=$_SESSION['ADMIN_ID'];
         $user=M('Users');
         $nowuser=$user->where('id='.$loginuid)->find();
         $pidarray=$this->getParentid($oid);      
         
         //如果是超级管理员或者是上级管理员，则允许删除
       if($nowuser['isadmin']==2 ||in_array($nowuser['oid'],$pidarray)){
            if($org->where('id='.$oid)->save($data)){
                if($nowuser['isadmin']==2){
                     $return['state']='success';
                     $return['msg']='修改成功'; 
                     $return1=$org->select();                      
                     $return['orglist']=$return1;       
                }else{
                     $return['state']='success';
                     $return['msg']='修改成功';
                     $result=$org->where('id='.$nowuser['oid'])->find();                     
                     $data=$org->select();                
                     //$return1=$this->getChildwithoutuser($data, $nowuser['oid']);        
                     //array_push($return1,$result); 
                     $return1=$this->getChildbylevel($result['level']);
                     $return['orglist']=$return1;      
                }           
            }else{
                 $return['state']='fail';
                 $return['msg']='修改失败';
           }     
      }else{
           $return['state']='fail';
           $return['msg']='抱歉，您无权删除';
      }
       
        echo json_encode($return);
        die;
    }
   
    //拖拽修改组织节点的父节点
    public function editorgpid(){
        $data['id']=$_POST['oid'];
        $data['parentid']=$_POST['parentid'];      
        $org=M("Orginization");
        if($org->where('id='.$data['id'])->save($data)){
            $return['state']=true;
            $return['msg']='修改成功';
        }else{
            $return['state']='false';
            $return['msg']='修改失败';
        }
        echo json_encode($return);
        die;
    }
  
    
}

