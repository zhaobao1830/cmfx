<?php
namespace User\Controller;

use Common\Controller\AdminbaseController;

class IndexadminController extends AdminbaseController {
    
    // 后台本站用户列表
    public function index(){
        $where=array();
        $request=I('request.');
        
        if(!empty($request['uid'])){
            $where['id']=intval($request['uid']);
        }
        //$userid=$_SESSION['ADMIN_ID'];
        $users_model=M("Users");  
        //$user=$users_model->where('id='.$userid)->find();
        $user=$_SESSION['user'];      
        if(!empty($request['keyword'])){//如果查询条件不为空  
            $keyword=$request['keyword'];
            $keyword_complex=array();
            $keyword_complex['user_login']  = array('like', "%$keyword%");//安用户登录名进行模糊查询
           // $keyword_complex['user_nicename']  = array('like',"%$keyword%");
            //$keyword_complex['user_email']  = array('like',"%$keyword%");    
            if($user['isadmin']!=2){
                $keyword_complex['oid']=array('eq',$user['oid']);     
            }
            //$keyword_complex['_logic'] = 'or';    
            
            $where['_complex'] = $keyword_complex;         
        } else{  //如果查询条件为空
            if($user['isadmin']!=2){
               $where['oid']=array('eq',$user['oid']);           
            } 
        }
    	  	
    	$count=$users_model->where($where)->count();
    	$page = $this->page($count, 20);
    	
    	$list = $users_model
    	->where($where)
    	->order("create_time DESC")
    	->limit($page->firstRow . ',' . $page->listRows)
    	->select();
    	
    	$this->assign('list', $list);
    	$this->assign("page", $page->show('Admin'));
    	
    	$this->display(":index");
    }
    
    // 后台本站用户禁用
    public function ban(){
    	$id= I('get.id',0,'intval');
    	if ($id) {
    		$result = M("Users")->where(array("id"=>$id,"user_type"=>2))->setField('user_status',0);
    		if ($result) {
    			$this->success("会员拉黑成功！", U("indexadmin/index"));
    		} else {
    			$this->error('会员拉黑失败,会员不存在,或者是管理员！');
    		}
    	} else {
    		$this->error('数据传入失败！');
    	}
    }
    
    // 后台本站用户启用
    public function cancelban(){
    	$id= I('get.id',0,'intval');
    	if ($id) {
    		$result = M("Users")->where(array("id"=>$id,"user_type"=>2))->setField('user_status',1);
    		if ($result) {
    			$this->success("会员启用成功！", U("indexadmin/index"));
    		} else {
    			$this->error('会员启用失败！');
    		}
    	} else {
    		$this->error('数据传入失败！');
    	}
    }
}
