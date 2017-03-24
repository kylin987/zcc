<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：SaleController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTouch用户中心
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */


class ErweiController extends Common2Controller {   
    
    /**
     * 我要分销
     */
    public function to_sale(){
		
        // 设置分销商id
        if (I('sale')){
			cookie('sale_id',I('sale'),3600); 
            session('sale_id',I('sale'));
			$user_id=I('sale');
			
			$this->assign('sale_id',I('sale'));
		}else{
			if($_SESSION['user_id']){
				$user_id=$_SESSION['user_id'];
				$this->assign('sale_id',$user_id);
			}else{
				$this->redirect(url('user/login'));
            	exit;
			}	
		}	
			
		//获取用户信息
		$u_info = model('ClipsBase')->get_user_biao($user_id);
		
		/*
		$user_rank = model('ClipsBase')->get_user_rank($user_id);
		if($user_rank==117 ||empty($user_rank)){
			$this->display('user_sale_no.dwt');
			return;
		}
		*/
		$info = model('ClipsBase')->get_touxiang($user_id);
		if($u_info['0']['u_headimg']){
			$headimgurl=$u_info['0']['u_headimg'];
		}elseif($info['0']['headimgurl']){
			$headimgurl=$info['0']['headimgurl'];			
		}else{
			$headimgurl="themes/default/images/moren3.png";
		}
		
		if($u_info['0']['nicheng']){
			$nickname=$u_info['0']['nicheng'];
		}elseif($info['0']['nickname']){
			$nickname=$info['0']['nickname'];			
		}else{
			$nickname="......";
		}
		
		//微信分享功能
		/* 
		$timestamp = time();
		$wxnonceStr = $this->randStr();
		$wxticket = $this->wx_get_jsapi_ticket();			
		$dq_url = __HOST__ . $_SERVER['REQUEST_URI'];
		$dq_url2 = __HOST__ . $_SERVER['REQUEST_URI'];
					
		$wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s",$wxticket, $wxnonceStr, $timestamp,$dq_url);
		$wxSha1 = sha1($wxOri);
		
		
		$wxinfo = $this->get_config();
		
		$wx_share=array();
		$wx_share['appid']=$wxinfo['appid'];
		$wx_share['timestamp']=$timestamp;
		$wx_share['wxnonceStr']=$wxnonceStr;
		$wx_share['dq_url2']=$dq_url2;		
		$wx_share['wxSha1']=$wxSha1;
		$wx_share['k_title']=$nickname."--千康元微销";
		$wx_share['k_dec']="我是".$info['0']['nickname']."，我为千康元代言，赶快加入千康元共享健康财富之旅吧！！！";
		$wx_share['headimg']=$headimgurl;
		$this->assign('wx_share', $wx_share);
		*/
		
		$zhutu= 'data/sale/sale_datu_'.$user_id.'.jpg';	
		if(!file_exists($zhutu)){				
			$this->display('user_sale_code3.dwt');				
		}else{
			$this->assign('zhutu',$zhutu.'?'.time());
			$this->display('user_sale_code2.dwt');				
		}
			
        
		
		
    }
	public function shengcheng(){				
		$user_id=$_SESSION['user_id'];
		$sale_id = $_POST['sale_id'];
		$nicheng = $_POST['nicheng'];
		$tg_1 = $_POST['tg_1'];
		$tg_2 = $_POST['tg_2'];
		
		if($user_id == 0 || $user_id == null || $user_id != $sale_id){			
			$result=array("status"=>0,"info"=>"该用户未生成专属二维码");			
			echo json_encode($result);			
		}else{
			if($nicheng){		//如果有post过来的昵称，说明是个性二维码生成	
				$zhutu= 'data/sale2/sale_datu_'.$user_id.'.jpg';
			}else{
				$zhutu= 'data/sale/sale_datu_'.$user_id.'.jpg';
			}	
					
			if(file_exists($zhutu)){
				$result=array("status"=>1,"info"=>"");
				$result['info']=$zhutu;
				echo json_encode($result);	
			}else{
				$u_info = model('ClipsBase')->get_user_nc_tx($user_id,3);
				$headimgurl = $u_info['headimg'];
				$nickname = $u_info['nicheng'];
				//logg($u_info);
				if(!$headimgurl){
					$headimgurl2="themes/default/images/moren3.png";	
				}elseif(strpos($headimgurl,'http') !== false){
					$thumbname='tx_'.$user_id.'.png';
					$path='./data/big_tx/';
					model('ClipsBase')->put_file_from_url_content($headimgurl,$thumbname,$path);
					$headimgurl2=$path.$thumbname;
				}else{					
					$headimgurl2=$headimgurl;
				}
								
				//画个黑色背景图
				$im = imagecreatetruecolor(640, 780);
				$bgcolor = imagecolorallocate($im, 44, 48, 47);
				imagefill($im, 0, 0, $bgcolor);
				
				//加上白色的主体图
				$image_width = 600;//圆角淡色背景的宽694px
				$image_height = 740;//圆角淡色背景的高368px
				//矩形上面加圆角
				$radius = 10;//圆角的像素，值越大越圆
				$dst_x = 20;//距离白色大背景左边的距离
				$dst_y = 20;//距离白色大背景顶端的距离
				//这里调用函数，绘制淡色的圆角背景，
				$cimage=new ImageCheck();
				$cimage->imagebackgroundmycard($im, $dst_x, $dst_y, $image_width, $image_height, $radius);
				
				
				$round_headimg = $cimage->rounded_corner($headimgurl2 ,160,160,"#FFFFFF",80);
				imagecopymerge($im, $round_headimg, 240, 40, 0, 0, 160, 160, 100);
				
				//生成推荐码
				$re_code=encodeId($user_id);
				$t_re_code=model('ClipsBase')->add_re_code($user_id,$re_code);
				
				//生成扫描推荐
				$qrcode_id = model('ClipsBase')->add_qcode($user_id,$nickname);		//插入到qrcode表的部分数据
				//更新二维码内容到表里
				if($qrcode_id){
					$rs = $this->model->table('wechat_qrcode')
						->field('type, scene_id, expire_seconds, qrcode_url, status')
						->where('id = ' . $qrcode_id)
						->find();
					if (empty($rs['status'])) {
						$result=array("status"=>0,"info"=>"二维码已禁用，请重新启用！");			
						echo json_encode($result);	
					}
					$wechat = $this->get_config();
					$config = array();
					$config['token'] = $wechat['token'];
					$config['appid'] = $wechat['appid'];
					$config['appsecret'] = $wechat['appsecret'];
					$weObj = new Wechat($config);
					if (empty($rs['qrcode_url'])) {
						// 获取二维码ticket
						$ticket = $weObj->getQRCode((int)$rs['scene_id'], $rs['type'], $rs['expire_seconds']);						
						if (empty($ticket)) {
							$result=array("status"=>0,"info"=>"生成二维码错误");			
							echo json_encode($result);
						}
						$data['ticket'] = $ticket['ticket'];
						$data['expire_seconds'] = $ticket['expire_seconds'];
						$data['endtime'] = time() + $ticket['expire_seconds'];
						// 二维码地址
						$qrcode_url = $weObj->getQRUrl($ticket['ticket']);
						$data['qrcode_url'] = $qrcode_url;
						
						$this->model->table('wechat_qrcode')
							->data($data)
							->where('id = ' . $qrcode_id)
							->update();
					} else {
						$qrcode_url = $rs['qrcode_url'];
					}
					
				}
				
				$cimage=new ImageCheck();
				//缩小二维码图片
				$dstimg = $cimage->imagezoom2($qrcode_url,355,355,"#FFFFFF");
				
				//把二维码和底图合并
				imagecopymerge($im, $dstimg, 142, 310, 0, 0, 355, 355, 100);
				
				$image = new Image();
				//写上昵称
				$text="我是 : ".$nickname;
				$font='./data/sale/ttf/msyh.ttf';						
				$image->water4($text, $font, 22, 0,0,0, 185, 230,$im);		//0,0,0为RGB值，185为X的坐标，这里自动居中，所以185无用
				
				//写上专属二维码
				$text="邀请口令：".$t_re_code;
				$font='./data/sale/ttf/msyh.ttf';						
				$image->water4($text, $font, 16, 4,103,196, 225, 260,$im);		//同上
				
				//写上口号
				if($tg_1){
					$text=$tg_1;
				}else{
					$text="移动互联网时代，碎片时间中享受倍增的力量！";
				}	
				$font='./data/sale/ttf/msyh.ttf';						
				$image->water4($text, $font, 16, 133,133,133, 225, 290,$im);		//同上
				
				//写上口号
				if($tg_2){
					$text=$tg_2;
				}else{
					$text="加入我们，创业就是这么简单！";
				}	
				$font='./data/sale/ttf/msyh.ttf';						
				$image->water4($text, $font, 16, 133,133,133, 225, 320,$im);		//同上
				
				
				//写上下面的文字
				$text="长按此图/识别图中二维码/搞定 ";
				$font='./data/sale/ttf/msyh.ttf';						
				$image->water4($text, $font, 16, 0,0,0, 225, 670,$im);		//同上
				
				//写上下面的文字
				$text="开启 : ”                 “之旅   ";
				$font='./data/sale/ttf/msyh.ttf';						
				$image->water4($text, $font, 20, 0,0,0, 225, 710,$im);		//同上
				
				//写上下面的文字
				$text=" 资本花园 ";
				$font='./data/sale/ttf/simhei.ttf';						
				$image->water4($text, $font, 22, 255,0,0, 225, 710,$im);		//同上
				
				imagepng($im,$zhutu); 
				imagedestroy($im);
				
				if(file_exists($zhutu)){
					$result=array("status"=>1,"info"=>"");
					$result['info']=$zhutu.'?'.time();
					echo json_encode($result);
				}else{
					$result=array("status"=>0,"info"=>"");
					$result['info']="生成专属二维码失败，请稍后再试";
					echo json_encode($result);
				}	
				
			}	
		}
		
	}
	
	//重新生成二维码
	public function re_build_sale(){
		$user_id=$_SESSION['user_id'];
		
		/*
		$user_rank = model('ClipsBase')->get_user_rank($user_id);
		if($user_rank==117 ||empty($user_rank)){
			$this->display('user_sale_no.dwt');
			return;
		}
		*/
		if($user_id){
			$thumbname='./data/sale/tx/tx_'.$user_id.'.png';
			$zhutu= './data/sale/sale_datu_'.$user_id.'.jpg';
			unlink($thumbname);
			unlink($zhutu);
			if(!file_exists($thumbname) && !file_exists($zhutu)){
				$url = __URL__."/index.php?m=default&c=erwei&a=to_sale&sale=".$user_id;
				header("Location:".$url);
			}
		}
	}
	
	//生成个性二维码
	public function per_qrcode(){		
		if($_SESSION['user_id']){
			$user_id=$_SESSION['user_id'];
			$this->assign('sale_id',$user_id);
		}else{
			$this->redirect(url('user/login'));
			exit;
		}
		/*
		$user_rank = model('ClipsBase')->get_user_rank($_SESSION['user_id']);
		if($user_rank==117 ||empty($user_rank)){
			$this->display('user_sale_no.dwt');
			return;
		}
		*/
		$user_head_nickname = model('ClipsBase')->get_user_nc_tx($user_id,3);	//同时获取头像和昵称
		$this->assign('user_info', $user_head_nickname);
		$this->display('user_sale_code.dwt');
	}
	
    public function wx_get_token() {
		$cache = new Fcache();
		$token = $cache->get('access_token');
		$wxinfo = $this->get_config();
		if (!$token) {
			$url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$wxinfo['appid'].'&secret='.$wxinfo['appsecre'];
			$res = file_get_contents($url);
			$res = json_decode($res, true);
			$token = $res['access_token'];
			// 注意：这里需要将获取到的token缓存起来（或写到数据库中）
			// 不能频繁的访问https://api.weixin.qq.com/cgi-bin/token，每日有次数限制
			// 通过此接口返回的token的有效期目前为2小时。令牌失效后，JS-SDK也就不能用了。
			// 因此，这里将token值缓存1小时，比2小时小。缓存失效后，再从接口获取新的token，这样
			// 就可以避免token失效。
			// S()是ThinkPhp的缓存函数，如果使用的是不ThinkPhp框架，可以使用你的缓存函数，或使用数据库来保存。
			$cache->add('access_token', $token, 3600);
		}
		return $token;
	}
	public function wx_get_jsapi_ticket(){
		$cache = new Fcache();
		
		$ticket = "";
		do{
			$ticket = $cache->get('wx_ticket');
			if (!empty($ticket)) {
				break;
			}
			$token = $cache->get('access_token');
			if (empty($token)){
				$this->wx_get_token();
			}
			$token = $cache->get('access_token');
			if (empty($token)) {
				//logErr("get access token error.");
				break;
			}
			$url2 = sprintf("https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=%s&type=jsapi",$token);
			$res = file_get_contents($url2);
			$res = json_decode($res, true);
			$ticket = $res['ticket'];
			// 注意：这里需要将获取到的ticket缓存起来（或写到数据库中）
			// ticket和token一样，不能频繁的访问接口来获取，在每次获取后，我们把它保存起来。
			$cache->add('wx_ticket', $ticket, 3600);
		}while(0);
		return $ticket;
	}
	/**   
	* 产生随机字符串   
	*   
	* 产生一个指定长度的随机字符串,并返回给用户   
	*   
	* @access public   
	* @param int $len 产生字符串的位数   
	* @return string   
	*/   
	public function randStr($len=8) {   
		$chars='ABDEFGHJKLMNPQRSTVWXYabdefghijkmnpqrstvwxy23456789'; // characters to build the password from   
		mt_srand((double)microtime()*1000000*getmypid()); // seed the random number generater (must be done)   
		$password='';   
		while(strlen($password)<$len)   
		$password.=substr($chars,(mt_rand()%strlen($chars)),1);   
		return $password;   
	}  
	
	/**
     * 获取默认公众号配置
     *
     * @param string $orgid            
     * @return array
     */
    private function get_config()
    {
        $config = $this->model->table('wechat')
            ->field('id, token, appid, appsecret')
            ->where('default_wx = 1 and status = 1')
            ->find();
        if (empty($config)) {
            $config = array();
        }
        return $config;
    }
	
	public function create_qrcode(){
		if($_POST){			
			$nicheng = $_POST['nicheng'];
			model('ClipsBase')->add_nicheng($this->dowith_sql($nicheng),$_SESSION['user_id']);
	
			
			$zhutu= './data/sale2/sale_datu_'.$_SESSION['user_id'].'.jpg';
			if(file_exists($zhutu)){
				unlink($zhutu);
			}	
			$this->assign('nicheng',$nicheng);
			$this->assign('sale_id',$_POST['sale_id']);
			$this->assign('tg_1',$_POST['tg_1']);
			$this->assign('tg_2',$_POST['tg_2']);
			$this->display('user_sale_code4.dwt');
		}else{
			$sale_id = $_GET['sale'];
			$zhutu= './data/sale2/sale_datu_'.$sale_id.'.jpg';
			$this->assign('zhutu',$zhutu);
			$this->display('user_sale_code5.dwt');
		}		
		
	}
	function dowith_sql($str)
	{
	   $str = str_replace("and","",$str);
	   $str = str_replace("execute","",$str);
	   $str = str_replace("update","",$str);
	   $str = str_replace("count","",$str);
	   $str = str_replace("chr","",$str);
	   $str = str_replace("mid","",$str);
	   $str = str_replace("master","",$str);
	   $str = str_replace("truncate","",$str);
	   $str = str_replace("char","",$str);
	   $str = str_replace("declare","",$str);
	   $str = str_replace("select","",$str);
	   $str = str_replace("create","",$str);
	   $str = str_replace("delete","",$str);
	   $str = str_replace("insert","",$str);
	   $str = str_replace("'","",$str);
	   $str = str_replace(" ","",$str);
	   $str = str_replace("or","",$str);
	   $str = str_replace("=","",$str);
	   $str = str_replace("%20","",$str);
	   //echo $str;
	   return $str;
	}
	
	

}
