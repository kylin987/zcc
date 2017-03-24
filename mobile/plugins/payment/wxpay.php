<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 liuwave@qq.com All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：wxpay.php
 * ----------------------------------------------------------------------------
 * 功能描述：微信支付插件
 * ----------------------------------------------------------------------------
 * 
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
if (! defined('IN_ECTOUCH')) {
    die('Deny Access');
}

$payment_lang = ROOT_PATH . 'plugins/payment/language/' . C('lang') . '/' . basename(__FILE__);

if (file_exists($payment_lang)) {
    include_once ($payment_lang);
    L($_LANG);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE) {
    $i = isset($modules) ? count($modules) : 0;
    /* 代码 */
    $modules[$i]['code'] = basename(__FILE__, '.php');
    /* 描述对应的语言项 */
    $modules[$i]['desc'] = 'wxpay_desc';
    /* 是否支持货到付款 */
    $modules[$i]['is_cod'] = '0';
    /* 是否支持在线支付 */
    $modules[$i]['is_online'] = '1';
    /* 作者 */
    $modules[$i]['author'] = 'ECTOUCH TEAM';
    /* 网址 */
    $modules[$i]['website'] = 'http://mp.weixin.qq.com/';
    /* 版本号 */
    $modules[$i]['version'] = '2.5';
    /* 配置信息 */

    /* 配置信息 */
    $modules[$i]['config']  = array(
        array('name' => 'wxpay_appid',           'type' => 'text',   'value' => ''),
        array('name' => 'wxpay_appsecret',       'type' => 'text',   'value' => ''),
        array('name' => 'wxpay_mchid',      'type' => 'text',   'value' => ''),
        array('name' => 'wxpay_key',      'type' => 'text', 'value' => ''),
        array('name' => 'wxpay_signtype',      'type' => 'text', 'value' => 'sha1')
    );
    
    return;
}

/**
 * 微信支付类
 */
class wxpay
{

    var $parameters; // cft 参数
    var $payment; // 配置信息
    /**
     * 生成支付代码
     *
     * @param array $order
     *            订单信息
     * @param array $payment
     *            支付方式信息
     */
    function get_code($order, $payment)
    {
        if (!defined('EC_CHARSET'))
        {
            $charset = 'utf-8';
        }
        else
        {
            $charset = EC_CHARSET;
        }

        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
        if( !preg_match('/micromessenger/', $ua)){
            return '<div style="text-align:center"><button class="btn btn-primary" type="button" disabled>请在微信中支付</button></div>';
        }
        if(!isset($_SESSION["openid"]) || empty($_SESSION["openid"])){
            return '<div style="text-align:center"><button class="btn btn-primary" type="button" disabled>未得权限</button></div>';
        }






        $charset = strtoupper($charset);
        // 配置参数
        $this->payment = $payment;
		
		if($order['dl']){		//如果有这个值，那么说明是代理申请付款
			$body = "千盈资本花园合伙人入股金";
			$out_trade_no = $order['id']. 'ODO' . $order['user_id'];
			$total_fee = $order['daili_amount'];
			$log_id = $order['id'];
		}elseif($order['cz']){		//如果有这个值说明是充值
			$body = $order['order_sn'];
			$out_trade_no = $order['order_sn'] . 'O' . $order['log_id'] . 'OC';
			$total_fee = $order['order_amount'];
			$log_id = $order['log_id'];
		}elseif($order['two']){		//如果有这个值说明是在订单详情里付款的
			$body = $order['order_sn'];
			$out_trade_no = $order['order_sn'] . 'O' . $order['log_id'] . 'OT' .gmtime();
			$total_fee = $order['order_amount'];
			$log_id = $order['log_id'];
		}else{
			$body = $order['order_sn'];
			$out_trade_no = $order['order_sn'] . 'O' . $order['log_id'];
			$total_fee = $order['order_amount'];
			$log_id = $order['log_id'];
		}

        //$root_url = str_replace('mobile', '', __URL__);
        $notify_url=return_url(basename(__FILE__, '.php'), array('type'=>0));
		//$notify_url=__URL__."/test.php";
		//model('Users')->xierizhi($notify_url);
        $this->setParameter("openid", $_SESSION["openid"]); // 商品描述
        $this->setParameter("body", $body); // 商品描述
        $this->setParameter("out_trade_no", $out_trade_no); // 商户订单号
        $this->setParameter("total_fee", $total_fee * 100); // 总金额
        $this->setParameter("notify_url", $notify_url); // 通知地址
        $this->setParameter("trade_type", "JSAPI"); // 交易类型
        $this->setParameter("input_charset", $charset);
		
        $prepay_id = $this->getPrepayId();
		//logg($prepay_id);
		//model('Users')->xierizhi($prepay_id);
        $jsApiParameters = $this->getParameters($prepay_id);
        // wxjsbridge
        $js = '<script language="javascript">
        function jsApiCall(){WeixinJSBridge.invoke("getBrandWCPayRequest",' . $jsApiParameters . ',function(res){if(res.err_msg == "get_brand_wcpay_request:ok"){location.href="' . return_url(basename(__FILE__, '.php'), array(
            'type' => 0,
            'status' => 1,
            'log_id'=>$log_id
        )) . '"}else{location.href="' . return_url(basename(__FILE__, '.php'), array(
            'type' => 0,
            'status' => 0
        )) . '"}});}function callpay(){if (typeof WeixinJSBridge == "undefined"){if( document.addEventListener ){document.addEventListener("WeixinJSBridgeReady", jsApiCall, false);}else if (document.attachEvent){document.attachEvent("WeixinJSBridgeReady", jsApiCall);document.attachEvent("onWeixinJSBridgeReady", jsApiCall);}}else{jsApiCall();}}
            </script>';
        
        $button = '<div style="text-align:center"><button class="btn-info ect-btn-info" style="background-color:#44b549;" type="button" onclick="callpay()">微信安全支付</button></div>' . $js;
        
        return $button;
    }

    /**
     * 响应操作
     */
    public function callback($data)
    {
		//model('Users')->xierizhi('直接来的');
        if ($data['status'] == 1) {					
			return true;
        } else {
            return false;
        }
    }

    /**
     * 异步响应操作
     */
    public function notify($data)
    {
		
		//logg('异步来了');
        if (! empty($data['postStr'])) {
            $payment = model('Payment')->get_payment($data['code']);
            $postdata = json_decode(json_encode(simplexml_load_string($data['postStr'], 'SimpleXMLElement', LIBXML_NOCDATA)), true);
            /* 检查插件文件是否存在，如果存在则验证支付是否成功，否则则返回失败信息 */
            // 微信端签名
            $wxsign = $postdata['sign'];
            unset($postdata['sign']);
            
            foreach ($postdata as $k => $v) {
                $Parameters[$k] = $v;
            }
            // 签名步骤一：按字典序排序参数
            ksort($Parameters);
            
            $buff = "";
            foreach ($Parameters as $k => $v) {
                $buff .= $k . "=" . $v . "&";
            }
            $String="";
            if (strlen($buff) > 0) {
                $String = substr($buff, 0, strlen($buff) - 1);
            }
            // 签名步骤二：在string后加入KEY
            $String = $String . "&key=" . $payment['wxpay_key'];
            // 签名步骤三：MD5加密
            $String = md5($String);
            // 签名步骤四：所有字符转为大写
            $sign = strtoupper($String);
            // 验证成功
            if ($wxsign == $sign) {
                // 交易成功
				//logg($postdata);
                if ($postdata['result_code'] == 'SUCCESS') {
                    // 获取log_id
                    $out_trade_no = explode('O', $postdata['out_trade_no']);
					if($out_trade_no[1]=='D'){
						$k_amount = floatval($postdata['total_fee'])/100;
						model('ClipsBase')->log_account_change($out_trade_no[2],$k_amount,0,0,0,"微信充值",0);
						model('Base')->model->table('apply_list')
							->data('openid = "' . $postdata['openid'] . '", pay_status = 1, result_code = "' . $postdata['result_code'] . '", total_fee = "' . $postdata['total_fee'] . '", out_trade_no = "' . $postdata['out_trade_no'] . '", transaction_id = "' . $postdata['transaction_id'] . '", time_end = "' . $postdata['time_end'] . '"')
							->where('id = ' . $out_trade_no[0])
							->update();
						model('ClipsBase')->log_account_change($out_trade_no[2],$k_amount*-1,0,0,0,"入股金支付",20);
					}elseif($out_trade_no[2]=='C'){
						//logg($out_trade_no);
						$log_id = $out_trade_no[1]; // 充值号log_id
						$chongzhi_info=model('Payment')->log_get_chongzhi_info($log_id);
						//logg($chongzhi_info);
						if($chongzhi_info['is_paid'] == 1){
						
						}else{
							// 改变订单状态
							//logg('改变');
							model('Payment')->order_paid($log_id, 2);
							// 修改充值信息(openid，tranid)
							model('Base')->model->table('pay_log')
								->data('openid = "' . $postdata['openid'] . '", transid = "' . $postdata['transaction_id'] . '"')
								->where('log_id = ' . $log_id)
								->update();
						}
					}else{
						$log_id = $out_trade_no[1]; // 订单号log_id
						
						$order_info=model('Payment')->log_get_orderinfo($log_id);
						//model('Users')->xierizhi($order_info);
						//logg($order_info);
						if($order_info['pay_status']==2){
							
						}else{
							//先加钱
							$k_amount = floatval($postdata['total_fee'])/100;
							model('ClipsBase')->log_account_change($order_info['user_id'],$k_amount,0,0,0,"微信充值",0);
							// 改变订单状态
							model('Payment')->order_paid($log_id, 2);
						
							// 修改订单信息(openid，tranid)
							model('Base')->model->table('pay_log')
								->data('openid = "' . $postdata['openid'] . '", transid = "' . $postdata['transaction_id'] . '"')
								->where('log_id = ' . $log_id)
								->update();
							
							//再扣钱
							$k_info = "微信支付订单号：".$order_info['order_sn'];
							$account_log = model('ClipsBase')->log_account_change3($order_info['user_id'],$k_amount*-1,0,0,0,$k_info,99);
							//更新到订单详情里
							$k_own_money = floatval($account_log[own_money])*-1;
							$k_divi_money = floatval($account_log[divi_money])*-1;
							model('ClipsBase')->update_order_money($order_info ['order_sn'],$k_own_money,$k_divi_money);
							
							//
							//如果当前购买者为未消费用户，那么根据订单的up_id修改上级ID							
							if($order_info['rank_points'] == 0){
								model('ClipsBase')->update_upid($order_info['user_id'],$order_info['up_id']);
							}
							
							//付款后即送积分							
							$integral = model('ClipsBase')->integral_to_give($order_info['order_id']);
							$info="订单 ".$order_info['order_sn']." 奖励"; 
							model('ClipsBase')->log_account_change($order_info['user_id'], 0, 0, intval($integral['rank_points']) , intval($integral['custom_points']) , $info);
							
							//在这里添加到ab_log里
							model('ClipsBase')->log_ab_change($order_info['order_id']);
							
							//在这里添加到销售任务流水里
							model('ClipsBase')->log_task($order_info['order_id']);
						}		
						/*	
						if(method_exists('WechatController', 'do_oauth')){
							// 如果需要，微信通知 wanglu
							
							$order_id = model('Base')->model->table('order_info')
								->field('order_id')
								->where('order_sn = "' . $out_trade_no[0] . '"')
								->getOne();
							$order_url = __HOST__ . url('user/order_detail', array(
								'order_id' => $order_id
							));
							$order_url = urlencode(base64_encode($order_url));
							send_wechat_message('pay_remind', '支付成功', $out_trade_no[0] . ' 订单已支付', $order_url, $out_trade_no[0]);
							
						}
						*/
						//微信通知上级
						//$chenghu=getChenghu($order['user_id']);
						//$tz_info="您的分销订单<".$out_trade_no[0].">已成功支付。您可以到分销订单中查看";
						//model('Users')->xierizhi($out_trade_no[0]);
						//model('ClipsBase')->send_wechat_msg(3,$out_trade_no[0],$tz_info,0);
					}	
                }
                $returndata['return_code'] = 'SUCCESS';
            } else {
                $returndata['return_code'] = 'FAIL';
                $returndata['return_msg'] = '签名失败';
            }
        } else {
            $returndata['return_code'] = 'FAIL';
            $returndata['return_msg'] = '无数据返回';
        }
        // 数组转化为xml
        $xml = "<xml>";
        foreach ($returndata as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
        $xml .= "</xml>";
        
        echo $xml;
        exit();
    }

    function trimString($value)
    {
        $ret = null;
        if (null != $value) {
            $ret = $value;
            if (strlen($ret) == 0) {
                $ret = null;
            }
        }
        return $ret;
    }

    /**
     * 作用：产生随机字符串，不长于32位
     */
    public function createNoncestr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i ++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 作用：设置请求参数
     */
    function setParameter($parameter, $parameterValue)
    {
        $this->parameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
    }

    /**
     * 作用：生成签名
     */
    public function getSign($Obj)
    {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        // 签名步骤一：按字典序排序参数
        ksort($Parameters);
        
        $buff = "";
        foreach ($Parameters as $k => $v) {
            $buff .= $k . "=" . $v . "&";
        }
        $String="";
        if (strlen($buff) > 0) {
            $String = substr($buff, 0, strlen($buff) - 1);
        }
        // echo '【string1】'.$String.'</br>';
        // 签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $this->payment['wxpay_key'];
        // echo "【string2】".$String."</br>";
        // 签名步骤三：MD5加密
        $String = md5($String);
        // echo "【string3】 ".$String."</br>";
        // 签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        // echo "【result】 ".$result_."</br>";
        return $result_;
    }

    /**
     * 作用：以post方式提交xml到对应的接口url
     */
    public function postXmlCurl($xml, $url, $second = 30)
    {
        // 初始化curl
        $ch = curl_init();
        // 设置超时
        curl_setopt($ch, CURLOP_TIMEOUT, $second);
        // 这里设置代理，如果有的话
        // curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        // curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        // 设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        // 要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        // post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        // 运行curl
        $data = curl_exec($ch);
        curl_close($ch);
        // 返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error" . "<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($ch);
            return false;
        }
    }

    /**
     * 获取prepay_id
     */
    function getPrepayId()
    {
        // 设置接口链接
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        try {
            // 检测必填参数
            if ($this->parameters["out_trade_no"] == null) {
                throw new Exception("缺少统一支付接口必填参数out_trade_no！" . "<br>");
            } elseif ($this->parameters["body"] == null) {
                throw new Exception("缺少统一支付接口必填参数body！" . "<br>");
            } elseif ($this->parameters["total_fee"] == null) {
                throw new Exception("缺少统一支付接口必填参数total_fee！" . "<br>");
            } elseif ($this->parameters["notify_url"] == null) {
                throw new Exception("缺少统一支付接口必填参数notify_url！" . "<br>");
            } elseif ($this->parameters["trade_type"] == null) {
                throw new Exception("缺少统一支付接口必填参数trade_type！" . "<br>");
            } elseif ($this->parameters["trade_type"] == "JSAPI" && $this->parameters["openid"] == NULL) {
                throw new Exception("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！" . "<br>");
            }
			$root_url = str_replace('mobile', '', __URL__);
			$notify_url=$root_url."/notify_wap_wxpay.php";
			//model('Users')->xierizhi($notify_url);
			$this->parameters["notify_url"] = $notify_url;
            $this->parameters["appid"] = $this->payment['wxpay_appid']; // 公众账号ID
            $this->parameters["mch_id"] = $this->payment['wxpay_mchid']; // 商户号
            $this->parameters["spbill_create_ip"] = $_SERVER['REMOTE_ADDR']; // 终端ip
            $this->parameters["nonce_str"] = $this->createNoncestr(); // 随机字符串
            $this->parameters["sign"] = $this->getSign($this->parameters); // 签名
			
            $xml = "<xml>";
            foreach ($this->parameters as $key => $val) {
                if (is_numeric($val)) {
                    $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
                } else {
                    $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
                }
            }
            $xml .= "</xml>";
        } catch (Exception $e) {
            die($e->getMessage());
        }
        
        // $response = $this->postXmlCurl($xml, $url, 30);
        $response = Http::curlPost($url, $xml, 30);
        $result = json_decode(json_encode(simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		//logg($result);
        $prepay_id = $result["prepay_id"];
        return $prepay_id;
    }

    /**
     * 作用：设置jsapi的参数
     */
    public function getParameters($prepay_id)
    {
        $jsApiObj["appId"] = $this->payment['wxpay_appid'];
        $timeStamp = time();
        $jsApiObj["timeStamp"] = "$timeStamp";
        $jsApiObj["nonceStr"] = $this->createNoncestr();
        $jsApiObj["package"] = "prepay_id=$prepay_id";
        $jsApiObj["signType"] = "MD5";
        $jsApiObj["paySign"] = $this->getSign($jsApiObj);
        $this->parameters = json_encode($jsApiObj);
        
        return $this->parameters;
    }

    public function redirtUrlForOpenid($p){
        if(!empty($p["state"]) && !empty($p["redirect_uri"])){
            $payment = model('Payment')->get_payment('wxpay');
            $wxUrl='https://open.weixin.qq.com/connect/oauth2/authorize?';

            $wxUrl.='appid='.$payment['wxpay_appid'];

            $wxUrl.='&redirect_uri='.urlencode($p['redirect_uri']);
            $wxUrl.='&response_type=code&scope=snsapi_base';
            $wxUrl.='&state='.$p["state"];
            $wxUrl.='#wechat_redirect';
//var_dump($p);
//var_dump($wxUrl);
            ecs_header("Location: $wxUrl\n");
        }
    }
    public function getOpenidByCode($code){
	//var_dump($code);
        if(!empty($code)){
            $payment = model('Payment')->get_payment('wxpay');
            $wxJsonUrl="https://api.weixin.qq.com/sns/oauth2/access_token?";
            $wxJsonUrl.='appid='.$payment['wxpay_appid'];
            $wxJsonUrl.='&secret='.$payment['wxpay_appsecret'];
			$wxJsonUrl.='&code='.$code;
            $wxJsonUrl.='&grant_type=authorization_code';

            $re=json_decode(Http::doGet($wxJsonUrl));
//var_dump($re);
//var_dump($re->openid);
            if(isset($re->openid)){

                $_SESSION["openid"]=$re->openid;
				/*
                if(!empty($_SESSION["user_id"])){
                    model('Base')->model->table('users')
                        ->data('openid = "' . $re->openid .'"')
                        ->where('user_id = ' . $_SESSION["user_id"])
                        ->update();
                }
				*/

            }else{
                //todo 测试用
                throw new Exception($re->errcode."  ".$re->errmsg . "<br>");
            }
        }



        //appid=APPID&secret=SECRET&code=CODE&grant_type=authorization_code
/*
        配置信息
        $modules[$i]['config']  = array(
           array('name' => 'wxpay_appid',           'type' => 'text',   'value' => ''),
            array('name' => 'wxpay_appsecret',       'type' => 'text',   'value' => ''),
            array('name' => 'wxpay_mchid',      'type' => 'text',   'value' => ''),
            array('name' => 'wxpay_key',      'type' => 'text', 'value' => ''),
            array('name' => 'wxpay_signtype',      'type' => 'text', 'value' => 'sha1')
        );
*/
    }
	
}