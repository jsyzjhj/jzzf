<?php

defined('IN_BACKGROUND') || exit('No permission');
bpBase::loadAppClass('base', '', 0);

class common_controller extends base_controller
{
	protected $merchant = array();
	protected $employer = array();
	protected $mid;
	protected $storeid = 0;
	protected $eid = 0;
	protected $extraInsertData = array();
	protected $tablepre = '';

	public function __construct()
	{
	    
	    
		parent::__construct();
		$session_storage = getSessionStorageType();
		bpBase::loadSysClass($session_storage);
		$isLogin = 0;
		
		/**
		 *  一键登录管理
		 */
		
		if(!empty($_GET['m_mid'])){
           
			if (empty($_GET['aid'])) {
			   if(!empty($_SESSION['my_Cashier_Salesman'])){
			       $id = $_SESSION['my_Cashier_Salesman']['sid'];
			       //验证当前业务员下是否存在该商家
			       $merchant_get = M('cashier_merchants')->get_one(array('mid'=>$_GET['m_mid']));
			   
			       if(!empty($merchant_get)){
			           
			           $_SESSION['my_Cashier_Merchant']['mid'] = $merchant_get['mid'];
			           $this->merchant = $merchant_get;
			       }else{
			      
			           $this->errorTip('非法请求!!');
			       }
			   }elseif (!empty($_SESSION['my_Cashier_adminuser'])) {
	                       $mid = intval($_GET['m_mid']);
	                       $_SESSION['my_Cashier_Merchant']['mid'] = $mid;
	                       $merchant_agentid = M('cashier_merchants')->get_one(array('mid'=>$mid),'*');
	                       $this->merchant = $merchant_agentid;
	                   }else{
	                      
			       $this->errorTip('非法请求!!');
			   }
			}else{
				$mid =(int) $_GET['m_mid'];
				$aid =(int) $_GET['aid'];
				$merchant_agentid = M('cashier_merchants')->get_one(array('mid'=>$mid),'*');
				if ( $merchant_agentid['aid'] && $merchant_agentid['aid']==$aid ) {
				    unset($_SESSION['my_Cashier_Merchant']);
				    unset($_SESSION['my_Cashier_Employer']);
					$_SESSION['my_Cashier_Merchant']['mid'] = $mid;
					$this->merchant = $merchant_agentid;
				}else{
				   
					$this->errorTip('非法请求!!');
				}



			}
		}


		
		
		
		if (isset($_SESSION['my_Cashier_Merchant']['mid']) || !empty($_SESSION['my_Cashier_Merchant']['mid'])) {
			$isLogin = 1;
			$this->merchant = M('cashier_merchants')->get_one(array('mid' => $_SESSION['my_Cashier_Merchant']['mid']));
			$this->mid = $this->merchant['mid'];


		} else {
			if (isset($_SESSION['my_Cashier_Employer']['eid']) || !empty($_SESSION['my_Cashier_Employer']['eid'])) {
				$isLogin = 1;
				$this->employer = M('cashier_employee')->get_one(array('eid' => $_SESSION['my_Cashier_Employer']['eid']));
				$this->merchant = M('cashier_merchants')->get_one(array('mid' => $this->employer['mid']));
				$this->mid = $this->employer['mid'];
				$this->storeid = $this->employer['storeid'];
				$this->eid = $this->employer['eid'];

				if (0 < $this->storeid) {
					$this->extraInsertData = array('eid' => $this->eid, 'storeid' => $this->storeid);
				}

			}

		}
		
		
		if ($isLogin == 0) {
			header('Location:merchants.php?m=Index&c=login&a=index');
			exit();
		}
		
		/**
		 * 员工登录
		 */
		if(isset($_SESSION['my_Cashier_Employer']['eid']) || !empty($_SESSION['my_Cashier_Employer']['eid'])){
		    if(empty($this->employer)){
		        $this->errorTip('账号异常，请重新登录！', '/merchants.php?m=Index&c=login&a=index');
		        exit();
		    }
		}
		
		/**
		 * 商家登录
		 */
		
		if(isset($_SESSION['my_Cashier_Merchant']['mid']) || !empty($_SESSION['my_Cashier_Merchant']['mid'])){
		    if (empty($this->merchant)) {
		        $this->errorTip('账号异常，请重新登录！', '/merchants.php?m=Index&c=login&a=index');
		        exit();
		    }
		}
		
		
		
		/* if (empty($this->merchant)) {
			$this->errorTip('账号异常，请重新登录！', '/merchants.php?m=Index&c=login&a=index');
			exit();
		}else if(empty($this->merchant) && empty($this->employer)){
		    $this->errorTip('账号异常，请重新登录！', '/merchants.php?m=Index&c=login&a=index');
		    exit();
		} */
		
		


		$db_config = loadConfig('db');
		$this->tablepre = $db_config['default']['tablepre'];
		unset($db_config);
	}

	protected function getAdminuserInfo()
	{
		$adminUtmp = M('cashier_adminuser')->get_one('1=1');
		$adminUtmp = M('cashier_payconfig')->get_one(array('mid' => $adminUtmp['mid']), '*');

		if ($adminUtmp['configData']) {
			$adminUtmp['configData'] = unserialize(htmlspecialchars_decode($adminUtmp['configData'], ENT_QUOTES));
		}
		 else {
			$adminUtmp['configData'] = array();
		}

		return $adminUtmp;
	}

	protected function cleanIspfPay($type)
	{
		if ($type == 'wx') {
			$data = array('pfpaymid' => 0);
		}
		 else if ($type == 'ali') {
			$data = array('pfalipaymid' => 0);
		}
		 else {
			$data = array();
		}

		if (!empty($data)) {
			M('cashier_payconfig')->update($data, `11=11`);
		}


		return true;
	}

	protected function authorityControl($data = array())
	{
		/* $eid = 0;
		isset($_SESSION['my_Cashier_Employer']) && !empty($_SESSION['my_Cashier_Employer']) && isset($_SESSION['my_Cashier_Employer']['eid']) && !empty($_SESSION['my_Cashier_Employer']['eid']) && ($eid = intval($_SESSION['my_Cashier_Employer']['eid']));

		if ((0 < $eid) && !in_array(ROUTE_ACTION, $data)) {
			if (!$this->authority($this->employer['authority'])) {
				if (isAjax()) {
				    return true;
					exit(json_encode(array('status' => 0, 'error' => 1, 'msg' => '您没有权限访问！')));
				}
				 else {
				     return true;
					$this->errorTip('您没有权限访问！');
				}
			}

		}
 */

		return true;
	}
        
	protected function getStoreInfo($iswxpass = true)
	{
		$cashier_storesDb = M('cashier_stores');

		if ($iswxpass) {
			$whereArr = array('mid' => $this->mid, 'available_state' => 3);
		}
		 else {
			$whereArr = array('mid' => $this->mid);
		}

		$allStores = $cashier_storesDb->select($whereArr, 'id,mid,poi_id,business_name,branch_name,available_state', '', 'id DESC');
		return $allStores;
	}

		/*
	 * 导出数据
	 * @param array $data   数据总条数
	 *
	 * @param array $str   表格头
	 */
	public function ExportTable($data,$title,$filename = '',$page,$pagenum){
	   	$result = $data;

	   	//组装xls文本
	   	$str="";
        if($page==0){
            foreach ($title as &$value) {
                $str .= $value;
                $str .="\t";
            }
            $str .="\n";
        }
	   	 
	   	$str = iconv('utf-8', 'gb2312', $str);
	   	 
	   	foreach ($result as  $k=>$row) {
	   	    $str1 ="";
	   	    foreach($row as $key=>$v){
	   	        $key = iconv('utf-8', 'gb2312', $v);
	   	        $str1 .= $key."\t";
	   	    }
	   	    $str .= $str1 ."\n";
	
	   	}

	   	if(!$filename){
	   	    $filename = date('YmdHis') . '.xls';
	   	}

        if($page>$pagenum){

	   	    $this->exportExcelData($filename, file_get_contents($this->mid.".txt"));
        }else{
            if($page==0){
                $myfile = fopen($this->mid.".txt", "w") or die("Unable to open file!");
            } else {
                $myfile = fopen($this->mid.".txt", "a") or die("Unable to open file!");
            }
            flock($myfile, LOCK_EX);
            fwrite($myfile, $str);
            flock($myfile, LOCK_UN);
            fclose($myfile);

            header('Location: ./merchants.php?m=User&c=count&a=MerchantExcel&p='.($page+1).'');
        }
	   	 
	}
	 
	/**
	 * 导出excel表格
	 * @param unknown $filename
	 * @param unknown $content  
	 * 
	 */
	
	private function exportExcelData($filename, $content)
	{

	   	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	   	header("Content-Type: application/vnd.ms-execl");
	   	header("Content-Type: application/force-download");
	   	header("Content-Type: application/download");
	   	header("Content-Disposition: attachment; filename=" . $filename);
	   	header("Content-Transfer-Encoding: binary");
	   	header("Pragma: no-cache");
	   	header("Expires: 0");
	   	echo $content;
	   	unlink($this->mid.".txt");
	}
        
        
        //判断这个门店是否属于本商户
        public function sismid($sid){
            $mid = M('cashier_stores')->get_var('id='.$sid,'mid');
               if($mid != $this->mid){
                   $this->errorTip('门店不存在!');
               }
               return true;
        }
        //判断这个员工是否属于本商户
        public function eismid($eid){
            $mid = M('cashier_employee')->get_var('eid='.$eid,'mid');
               if($mid != $this->mid){
                   $this->errorTip('员工不存在!');
               }
               return true;
        }

    public function ExportTables($data,$title,$filename = ''){
        $result = $data;

        //组装xls文本
        $str="";
        foreach ($title as &$value) {
            $str .= $value;
            $str .="\t";
        }
        $str .="\n";

        $str = iconv('utf-8', 'gb2312', $str);

        foreach ($result as  $k=>$row) {
            $str1 ="";
            foreach($row as $key=>$v){
                $key = iconv('utf-8', 'gb2312', $v);
                $str1 .= $key."\t";
            }
            $str .= $str1 ."\n";

        }

        if(!$filename){
            $filename = date('YmdHis') . '.xls';
        }

        $this->exportExcelDatas($filename, $str);

    }

    /**
     * 导出excel表格
     * @param unknown $filename
     * @param unknown $content
     *
     */

    private function exportExcelDatas($filename, $content)
    {
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/vnd.ms-execl");
        header("Content-Type: application/force-download");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=" . $filename);
        header("Content-Transfer-Encoding: binary");
        header("Pragma: no-cache");
        header("Expires: 0");
        echo $content;
    }
}


?>