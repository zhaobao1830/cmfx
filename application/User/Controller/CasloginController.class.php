<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tuolaji <479923197@qq.com>
// +----------------------------------------------------------------------
namespace User\Controller;
use Common\Controller\HomebaseController;
class CasloginController extends HomebaseController {
    
    public function isNeibuUser(){
              $t=false;
              $username=$_SESSION['username'];             
              //判断是否是内部用户
              $result=httppost('http://www.crecgec.com/api/creclb/index.php?api=NeibuUser&module=creclb',array('usercode'=>$username));      
             
              $resu=json_decode($result,true);                                       
              if(in_array('内部用户',$resu['USERTYPE'])){               
                  $t=true;                
               }                       
              return $t;
    }
    
         public function caslogin(){                
                 
                        //引入CAS客户端进行CAS的单点登录,注释是暂时关闭
			Vendor('CASClient.phpCAS');
			\phpCAS::client(CAS_VERSION_2_0,C('cas_host'),C('cas_port'),C('cas_context'),C('CLIENT_ID'),base64_encode(C('client_secret')));
			\phpCAS::setNoCasServerValidation();
			\phpCAS::handleLogoutRequests();
			\phpCAS::forceAuthentication();
                        session_start();
                        session('access_token', $_SESSION['CAS']['ast']);
                        session('refresh_token', $_SESSION['CAS']['rft']);                       
			$client = new \SoapClient(C('use_webservice'));
                        $param=array('access_token'=>session('access_token'),"refresh_token"=>"","username"=>"","clientId"=>C('CLIENT_ID'),"clientSecret"=>C('client_secret'));
                        $abc = $client->getUserInfos($param);  
                        $res1=json_decode($abc->return,true);
                      
                        if(empty($res1)){                            
                            unset($_SESSION);
                            session_destroy();
                            $this->error('用户信息已过期，请关闭浏览器打开后重新登录',__ROOT__."/");                          
                            exit;
                        }                           
			session('username',$res1['user_name']);                			
                        file_put_contents('data/loginlog.txt', 'ip::'.get_client_ip(0,true).',logintime::'.date("Y-m-d H:i:s").",login_name::".$_SESSION['username'].PHP_EOL,FILE_APPEND);
                         
                        if($this->isNeibuUser()){    
                             session('user',$res1); 
                             
			     session('expires',time()); 
                             $username=$_SESSION['username'];
                             $users_model=M('Users');
                             $find_user=$users_model->where(array("user_login"=>$username))->find(); 
                                                         
                             if($find_user){
                                 session_start();
                                 session('user',$find_user); 
                                 session('ADMIN_ID',$find_user['id']);   
                                 $this->dologin();
                                    //header('Location:http://localhost/wx/index.php?g=User&m=Login&a=dologin');
                                   // $this->redirect("User/Login/dologin");
                             }else{                                 
                                    $this->CASRegister();
                              }                            
                             exit;      
                        }else{
                              session_destroy();  //清除服务器的sesion文件                               
                              $this->error('十分抱歉通知您：您不是内部用户，不能登录',U('User/Caslogin/dologout'));                         
                               exit; 
                        }
                        
        }

	public function dologout(){
                session_destroy();  //清除服务器的sesion文件  
                Vendor('CASClient.phpCAS');
                \phpCAS::client(CAS_VERSION_2_0,C('cas_host'),C('cas_port'),C('cas_context'),C('CLIENT_ID'),base64_encode(C('client_secret')));
                \phpCAS::logout(array ("service" =>"https://passport.crecgec.com/logout?clientId=".C('cascallback')));
	}
      
        public function CASRegister(){
            session_start();   
            $user=$_SESSION['user'];
            $username=$_SESSION['username'];
            $users_model=M("Users");
            //$find_user=$users_model->where(array("user_login"=>$username))->find();  
            //if(!$find_user){             
                $pwd= '666666';
                $data = array(
	                    'user_login' => $username,
	                    'user_pass'   => sp_password($pwd),
	                    'user_nicename'=>$username,
                            'user_email'=>$user['user_email'],
                            'last_login_time' => date("Y-m-d H:i:s"),
	                    'last_login_ip'   => get_client_ip(0,true),	                   
                             'user_status' => 1,
			    'user_type'=>2,
                           'create_time'=>date("Y-m-d H:i:s"),
	                );
	     $s=$users_model->add($data);            
           // }  
            $find_user=$users_model->where(array("user_login"=>$username))->find();
            if($find_user){
                session('user',$find_user); 
                session('ADMIN_ID',$find_user['id']);   
            }
            redirect(__ROOT__."/admin");
            die;
        }
        
     public function dologin(){ 
        $username=$_SESSION['username'];              
        if(strpos($username,"@")>0){//邮箱登陆
            $where['user_email']=$username;
        }else{
            $where['user_login']=$username;
        }
        $users_model=M('Users');
        $result = $users_model->where($where)->find();      
        if(!empty($result)){
           
               //写入此次登录信息
                $data = array(
                    'last_login_time' => date("Y-m-d H:i:s"),
                    'last_login_ip' => get_client_ip(0,true),
                );
                $users_model->where("id=".$result["id"])->save($data);                          
                 sp_clear_cache();
                 redirect(__ROOT__.'/admin');              
        }else{
            $this->error("用户名不存在！");
        }
      }
     
}
