<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：IndexController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTouch首页控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class IndexController extends CommonController {

    /**
     * 首页信息
     */
    public function index() {
		
        // 设置分销商id
        if (I('sale')){
            session('sale_id',I('sale'));
			cookie('sale_id',I('sale'),3600); 
        }
		
		$nicheng =model('ClipsBase')->get_user_nc_tx($_SESSION['user_id'],2);
		
		//微信分享功能 
		$timestamp = time();
		$wxnonceStr = $this->randStr();
		$wxticket = $this->wx_get_jsapi_ticket();			
		$dq_url = __HOST__ . $_SERVER['REQUEST_URI'];
		$dq_url2 = __HOST__ . $_SERVER['REQUEST_URI'];
					
		$wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s",$wxticket, $wxnonceStr, $timestamp,$dq_url);
		$wxSha1 = sha1($wxOri);
		
		/*
		$test=array();
		$test['time']=$timestamp;
		$test['str']=$wxnonceStr;
		$test['tick']=$wxticket;
		$test['url']=$dq_url;
		$test['wxo']=$wxOri;
		$test['wxSha1']=$wxSha1;
		
		dump($test);
		exit;
		*/
		$wxinfo = $this->get_config();
		
		$wx_share=array();
		$wx_share['appid']=$wxinfo['appid'];
		$wx_share['timestamp']=$timestamp;
		$wx_share['wxnonceStr']=$wxnonceStr;
		$wx_share['dq_url2']=$dq_url2;		
		$wx_share['wxSha1']=$wxSha1;
		$wx_share['k_title']="千康元微销";
		$wx_share['k_dec']="我是".$nicheng."，我为千康元代言，赶快加入千康元共享健康财富之旅吧！！！";
		$wx_share['headimg']=$headimgurl;
		$this->assign('wx_share', $wx_share);
		
		
		
        // 自定义导航栏
        $navigator = model('Common')->get_navigator();
        $this->assign('navigator', $navigator['middle']);
        $this->assign('best_goods', model('Index')->goods_list('best', C('page_size')));
        $this->assign('new_goods', model('Index')->goods_list('new', C('page_size')));
        $this->assign('hot_goods', model('Index')->goods_list('hot', C('page_size')));
        //首页推荐分类
        $cat_rec = model('Index')->get_recommend_res();
        $this->assign('cat_best', $cat_rec[1]);
        $this->assign('cat_new', $cat_rec[2]);
        $this->assign('cat_hot', $cat_rec[3]);
        // 促销活动
        $this->assign('promotion_info', model('GoodsBase')->get_promotion_info());
        // 团购商品
        $this->assign('group_buy_goods', model('Groupbuy')->group_buy_list(C('page_size'),1,'goods_id','ASC'));
        // 获取分类
        $this->assign('categories', model('CategoryBase')->get_categories_tree());
        // 获取品牌
        $this->assign('brand_list', model('Brand')->get_brands($app = 'brand', C('page_size'), 1));
		
		//获取访问数			
		$this->assign('click_number', model('ClipsBase')->add_click(1));
		$this->assign('user_id', $_SESSION['user_id']);
        $this->display('index.dwt');
    }

    /**
     * ajax获取商品
     */
    public function ajax_goods() {
        if (IS_AJAX) {
            $type = I('get.type');
            $start = $_POST['last'];
            $limit = $_POST['amount'];
            $hot_goods = model('Index')->goods_list($type, $limit, $start);
            $list = array();
            // 热卖商品
            if ($hot_goods) {
                foreach ($hot_goods as $key => $value) {
					//$ky = floatval(substr(time(),4,3));
					//$value['sc'] = 	intval($value['sc'])+ $ky + rand(10,99);
                    $this->assign('hot_goods', $value);
                    $list [] = array(
                        'single_item' => ECTouch::view()->fetch('library/asynclist_index.lbi')
                    );
                }
            }
            echo json_encode($list);
            exit();
        } else {
            $this->redirect(url('index'));
        }
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
	public function abc(){
		header ('Content-Type: image/png');
		
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
		
		//加上头像
		$headimgurl2="./data/big_tx/tx_4.png";
		$round_headimg = $cimage->rounded_corner($headimgurl2,$thumbname ,160,160,"#FFFFFF",80);
		imagecopymerge($im, $round_headimg, 240, 40, 0, 0, 160, 160, 100);
		
		
		imagepng($im); 
		imagedestroy($im);
	}
	public function xy(){
		$save_reward = model('ClipsBase')->get_down_stock_reward('1527');
		dump($save_reward);
	}
	//股权处理
	public function day_do(){
		logg("day_do被执行了");
		//第0步，检测是否有需要执行的分红
		$fh_rs = model('ClipsBase')->get_fenhong();
		if(!empty($fh_rs)){
			model('ClipsBase')->change_user_stock('5');
			//执行分红
			foreach ($fh_rs as $k => $v) {
				model('ClipsBase')->do_fenhong($v['id'],$v['fh_value']);
			}
			model('ClipsBase')->change_user_stock('6');		
		}
		//第一步、计算老用户的股份占比，并且重新分配总资产。  占资产数+占比
		model('ClipsBase')->change_user_stock('1');
		
		//查询前一天的订单情况
		//$beginYesterday = mktime(0,0,0,date('m'),date('d')-1,date('Y'))-28800;		//减去8小时，和标准时间对应
		//$endYesterday = mktime(0,0,0,date('m'),date('d'),date('Y'))-1-28800;
		//$all_profit = model('ClipsBase')->get_Yesterday_profit($beginYesterday,$endYesterday);
		/*  以下为临时部分  */
		$endtime = gmtime();
		$all_profit = model('ClipsBase')->get_before_profit($endtime);
		//logg($all_profit);
		$pe_ratios = $this->model->table('shop_config')
            ->field('code, value')
            ->where('code = "pe_ratios"')
            ->find();
		//model('ClipsBase')->add_ac_log($all_profit,$pe_ratios,$endYesterday);
		model('ClipsBase')->add_ac_log($all_profit,$pe_ratios,$endtime);
		
		//再次更新用户的持股数及比例
		model('ClipsBase')->change_user_stock('2');
		
		//更新新用户的股份
		model('ClipsBase')->change_new_user_stock();
		
		//最后更新用户的持股数及比例
		//model('ClipsBase')->change_user_stock('3');	
		
		
	}

	//每月1号执行的考核
	public function month_do(){
		$time = time();		//因考核时间为北京时间，所以这里也用北京时间
		$user_assess = model('ClipsBase')->get_user_assess($time);
		foreach ($user_assess as $k=>$v){
			if($v['now_assess_value'] >= $v['next_assess_value']){
				//处理任务考核进程
				logg("完成");
				model('ClipsBase')->log_task_change($v['s_id'],$v['user_id'],$v['next_assess_value'],$v['now_assess_value']);
			}else{
				//处理用户退级进程
				logg("退级");
				model('ClipsBase')->down_user_lev($v['s_id'],$v['user_id'],$v['user_premium'],$v['premium_time'],$v['next_assess_value'],$v['now_assess_value'],$v['stock'],$v['ktimes']);
			}
		}
	}
	//计算市值
	public function market(){
		//logg('执行了market');
		//查询前一天的订单情况
		$beginYesterday = mktime(0,0,0,date('m'),date('d')-1,date('Y'))-28800;		//减去8小时，和标准时间对应
		$endYesterday = mktime(0,0,0,date('m'),date('d'),date('Y'))-1-28800;
		$all_profit = model('ClipsBase')->get_Yesterday_profit($beginYesterday,$endYesterday);
		$pe_ratios = $this->model->table('shop_config')
            ->field('code, value')
            ->where('code = "pe_ratios"')
            ->find();
		model('ClipsBase')->add_ac_log($all_profit,$pe_ratios,$endYesterday);
		
	}
	public function min_do(){
		/*
		//第一任务 处理那些有银币，没有double_time的用户
		$no_double_user_info = model('ClipsBase')->get_double_user_info();	//获取没有翻倍时间并且银币不等于0的用户		
		if($no_double_user_info){
			foreach($no_double_user_info as $k=>$v){
				$first_pay_points_time = model('ClipsBase')->get_user_first_pay_points($v['user_id']);		
				model('ClipsBase')->update_user_double_time($v['user_id'],$first_pay_points_time);		
			}
		}
		//第一临时任务 处理那些有double_time，但是没有next_double_time的用户
		$linshi_double_user_info = model('ClipsBase')->get_double_user_info_linshi();
		//dump($linshi_double_user_info);
		if($linshi_double_user_info){
			logg("奇怪，在临时任务里");
			logg($linshi_double_user_info);
			foreach($linshi_double_user_info as $k=>$v){					
				model('ClipsBase')->update_user_double_time($v['user_id'],$v['double_time']);		
			}
		}
			
		//第二任务 处理翻倍
		$time = gmtime();
		$double_user_info =	model('ClipsBase')->get_double_user_info2($time);	//获取有翻倍时间并且翻倍时间小于当前时间并且银币不等于0的用户		
		$i=0;
		$pay_p=0;		
		if($double_user_info){			
			foreach($double_user_info as $k=>$v){			
				$result = model('ClipsBase')->log_account_change($v['user_id'],0,0,0,$v['pay_points']*($v['next_double_beishu']-1),"代金券翻倍");				
				model('ClipsBase')->log_double_pay_points($v['user_id'],$v['pay_points'],$v['next_double_beishu']);
				$i++;	
				$pay_p+=$v['pay_points']*($v['next_double_beishu']-1);				
			}
			$info = "翻倍了".$i."人，增加了".$pay_p."代金券";
		}	
		//logg($info);
		//print($info);
		
		//第三任务 判断是否500万
		$user_arr = model('ClipsBase')->get_spree_user();
		if($user_arr){
			foreach($user_arr as $k=>$v){
				$s_id = model('ClipsBase')->exchange_spree($v['user_id']);
				if($s_id){
					model('ClipsBase')->log_account_change($v['user_id'],0,0,0,-5000000,"满足500万自动兑换大礼包");
				}
			}
		}
		
		*/
		
		//第四任务 检测并判断用户任务完成情况
		/*
		
		*/
		//第五任务 检测用户是否完成了年度任务
		$rs = model('ClipsBase')->get_all_year_user();
		if(!empty($rs)){
			foreach ($rs as $k => $v) {
				if($v['ktimes']==1){
					//如果是第一笔入股，那提升等级
					model('ClipsBase')->change_user_rank($v['user_id'],'144');
					//获取他的下级存储的奖励
					$save_reward = model('ClipsBase')->get_down_stock_reward($v['user_id']);
					foreach($save_reward as $key=>$value){
						//执行对自己的奖励
						model('ClipsBase')->do_stock_reward($v['user_id'],$value['user_id']);
						//修改存储奖励为已奖励
						model('ClipsBase')->change_stock_value($value['s_id'],'',1,'');
					}
					//执行给上级的奖励
					model('ClipsBase')->do_jiangli($v['s_id'],$v['user_id']);
				}else{
					//执行给自己的奖励
					model('ClipsBase')->do_jiangli2($v['s_id'],$v['user_id']);
				}
				
				//更新该股的信息
				model('ClipsBase')->change_stock_value($v['s_id'],1,'','');
				//更新用户信息，让用户可以追加股份
				model('ClipsBase')->change_user_done_stock($v['user_id'],$v['ktimes']);
			}
		}
	}
}
