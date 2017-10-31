<?php
require_once "WxPay.Api.class.php";
/**
 * 
 * 刷卡支付实现类
 * 该类实现了一个刷卡支付的流程，流程如下：
 * 1、提交刷卡支付
 * 2、根据返回结果决定是否需要查询订单，如果查询之后订单还未变则需要返回查询（一般反复查10次）
 * 3、如果反复查询10订单依然不变，则发起撤销订单
 * 4、撤销订单需要循环撤销，一直撤销成功为止（注意循环次数，建议10次）
 * 
 * 该类是微信支付提供的样例程序，商户可根据自己的需求修改，或者使用lib中的api自行开发，为了防止
 * 查询时hold住后台php进程，商户查询和撤销逻辑可在前端调用
 * 
 * @author widy
 *
 */
class MicroPay{
	/**
	 * 
	 * 提交刷卡支付，并且确认结果，接口比较慢
	 * @param WxPayMicroPay $microPayInput
	 * @throws WxpayException
	 * @return 返回查询接口的结果
	 */
	public function pay($microPayInput)
	{
		//①、提交被扫支付
		$result = WxPayApi::micropay($microPayInput, 5);
		//如果返回成功
		if(!array_key_exists("return_code", $result)|| !array_key_exists("out_trade_no", $result)|| !array_key_exists("result_code", $result))
		{
			/**echo "接口调用失败,请确认是否输入是否有误！";
			throw new WxPayException("接口调用失败！".(isset($result['return_msg']) ? $result['return_msg']:''));**/
			if(!array_key_exists("out_trade_no", $result) && array_key_exists("err_code", $result) && ($result['err_code']=='USERPAYING')){
				/**echo json_encode(array('error' => 3, 'msg' => '错误码：' . $result['err_code'] . "<br/>错误描述：" . $result['err_code_des']));**/
			}else{
			   $msg=isset($result['err_code_des']) && !empty($result['err_code_des']) ? '错误码：' . $result['err_code'] . "错误描述：" . $result['err_code_des'] :'';
			   $msg=empty($msg) && isset($result['return_msg']) ? $result['return_msg']:'';
			   $micropay_jsonstr=json_encode(array('error' => 1, 'msg' => $msg,'codemsg'=>isset($result['return_msg']) ? $result['return_msg']:''));
			}
		}
		
		//签名验证
		$out_trade_no = $microPayInput->GetOut_trade_no();
		
		//②、接口调用成功，明确返回调用失败
		if($result["return_code"] == "SUCCESS" && $result["result_code"] == "FAIL" &&  $result["err_code"] != "USERPAYING" &&  $result["err_code"] != "SYSTEMERROR")
		{   
			if($result["err_code"]=='AUTHCODEEXPIRE'){
			   echo json_encode(array('error' => 1, 'msg' => '支付刷卡授权码不正确！'));
			   exit();
			}elseif(isset($result["err_code_des"]) && !empty($result["err_code_des"])){
			   echo json_encode(array('error' => 1, 'msg' => $result["err_code_des"]));
			   exit();
			}else{
			  return false;
			}
			
		}

		//③、确认支付是否成功
		set_time_limit(60);
		$queryTimes = 15;
		while($queryTimes > 0)
		{
			$succResult = 0;
			$queryResult = $this->query($out_trade_no, $succResult);
			//如果需要等待1s后继续
			if($succResult == 2){
				sleep(2);
				if($queryTimes<18){
				 sleep(2);
				}
				continue;
			} else if($succResult == 1){//查询成功
				return $queryResult;
			} else {//订单交易失败
				break;
			}
			$queryTimes--;
		}
		
		//④、次确认失败，则撤销订单
		if(!$this->cancel($out_trade_no))
		{
			//echo json_encode(array('error' => 1, 'msg' => '撤销单失败！'));
			//throw new WxpayException("撤销单失败！");

		}
		if(isset($micropay_jsonstr) && !empty($micropay_jsonstr)){
		  echo $micropay_jsonstr;
		  exit();
		}
		return false;
	}
	
	/**
	 * 
	 * 查询订单情况
	 * @param string $out_trade_no  商户订单号
	 * @param int $succCode         查询订单结果
	 * @return 0 订单不成功，1表示订单成功，2表示继续等待
	 */
	public function query($out_trade_no, &$succCode)
	{
		$queryOrderInput = new WxPayOrderQuery();
		$queryOrderInput->SetOut_trade_no($out_trade_no);
		$result = WxPayApi::orderQuery($queryOrderInput);
		
		if($result["return_code"] == "SUCCESS" 
			&& $result["result_code"] == "SUCCESS")
		{
			//支付成功
			if($result["trade_state"] == "SUCCESS"){
				$succCode = 1;
			   	return $result;
			}
			//用户支付中
			else if($result["trade_state"] == "USERPAYING"){
				$succCode = 2;
				return false;
			}
		}
		
		//如果返回错误码为“此交易订单号不存在”则直接认定失败
		if($result["err_code"] == "ORDERNOTEXIST")
		{
			$succCode = 0;
		} else{
			//如果是系统错误，则后续继续
			$succCode = 2;
		}
		return false;
	}
	
	/**
	 * 
	 * 撤销订单，如果失败会重复调用10次
	 * @param string $out_trade_no
	 * @param 调用深度 $depth
	 */
	public function cancel($out_trade_no, $depth = 0)
	{
		if($depth > 10){
			return false;
		}
		
		$clostOrder = new WxPayReverse();
		$clostOrder->SetOut_trade_no($out_trade_no);
		$result = WxPayApi::reverse($clostOrder);
		
		//接口调用失败
		if($result["return_code"] != "SUCCESS"){
			return false;
		}
		
		//如果结果为success且不需要重新调用撤销，则表示撤销成功
		if($result["result_code"] != "SUCCESS" 
			&& $result["recall"] == "N"){
			return true;
		} else if($result["recall"] == "Y") {
			return $this->cancel($out_trade_no, ++$depth);
		}
		return false;
	}
}