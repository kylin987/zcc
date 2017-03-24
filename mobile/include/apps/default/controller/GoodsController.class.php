<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：GoodsControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：商品详情控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class GoodsController extends Common2Controller {

    protected $goods_id;

    /**
     * 构造函数   加载user.php的语言包 并映射到模版
     */
    public function __construct() {
        parent::__construct();
        $this->goods_id = isset($_REQUEST ['id']) ? intval($_REQUEST ['id']) : 0;
    }

    /**
     *  商品详情页
     */
    public function index() {
        // 获得商品的信息
        $goods = model('Goods')->get_goods_info($this->goods_id);
		//logg($goods);
        // 如果没有找到任何记录则跳回到首页
        if ($goods === false) {
            ecs_header("Location: ./\n");
        } else {
            if ($goods ['brand_id'] > 0) {
                $goods ['goods_brand_url'] = url('brand/index', array('id' => $goods ['brand_id']));
            }
            $shop_price = $goods ['shop_price'];
            $linked_goods = model('Goods')->get_related_goods($this->goods_id); 
            $goods ['goods_style_name'] = add_style($goods ['goods_name'], $goods ['goods_name_style']);

            // 购买该商品可以得到多少钱的红包
            if ($goods ['bonus_type_id'] > 0) {
                $time = gmtime();
                $condition = "type_id = '$goods[bonus_type_id]' " . " AND send_type = '" . SEND_BY_GOODS . "' " . " AND send_start_date <= '$time'" . " AND send_end_date >= '$time'";
                $count = $this->model->table('bonus_type')->field('type_money')->where($condition)->getOne();

                $goods ['bonus_money'] = floatval($count);
                if ($goods ['bonus_money'] > 0) {
                    $goods ['bonus_money'] = price_format($goods ['bonus_money']);
                }
            }
            $comments = model('Comment')->get_comment_info($this->goods_id,0);
            $this->assign('goods', $goods);
            $this->assign('comments', $comments);
            $this->assign('goods_id', $goods ['goods_id']);
            $this->assign('promote_end_time', $goods ['gmt_end_time']);
            // 获得商品的规格和属性
            $properties = model('Goods')->get_goods_properties($this->goods_id);
            // 商品属性
            $this->assign('properties', $properties ['pro']);
            // 商品规格
            $this->assign('specification', $properties ['spe']);
            // 相同属性的关联商品
            $this->assign('attribute_linked', model('Goods')->get_same_attribute_goods($properties));
            // 关联商品
            $this->assign('related_goods', $linked_goods);
            // 关联文章
            $this->assign('goods_article_list', model('Goods')->get_linked_articles($this->goods_id));
            // 配件
            $this->assign('fittings', model('Goods')->get_goods_fittings(array($this->goods_id)));
            // 会员等级价格
            $this->assign('rank_prices', model('Goods')->get_user_rank_prices($this->goods_id, $shop_price));
            // 商品相册
            $this->assign('pictures', model('GoodsBase')->get_goods_gallery($this->goods_id));
            // 获取关联礼包
            $package_goods_list = model('Goods')->get_package_goods_list($goods ['goods_id']);
            $this->assign('package_goods_list', $package_goods_list);
            //取得商品优惠价格列表
            $volume_price_list = model('GoodsBase')->get_volume_price_list($goods ['goods_id'], '1');
            // 商品优惠价格区间
            $this->assign('volume_price_list', $volume_price_list);
        }

        // 检查是否已经存在于用户的收藏夹
        if ($_SESSION ['user_id']) {
            $where['user_id'] = $_SESSION ['user_id'];
            $where['goods_id'] = $this->goods_id;
            $rs = $this->model->table('collect_goods')->where($where)->count();
            if ($rs > 0) {
                $this->assign('sc', 1);
            }
        }

        /* 记录浏览历史 */
        if (!empty($_COOKIE ['ECS'] ['history'])) {
            $history = explode(',', $_COOKIE ['ECS'] ['history']);
            array_unshift($history, $this->goods_id);
            $history = array_unique($history);
            while (count($history) > C('history_number')) {
                array_pop($history);
            }
            setcookie('ECS[history]', implode(',', $history), gmtime() + 3600 * 24 * 30);
        } else {
            setcookie('ECS[history]', $this->goods_id, gmtime() + 3600 * 24 * 30);
        }
        // 更新点击次数
        $data = 'click_count = click_count + 1';
        $this->model->table('goods')->data($data)->where('goods_id = ' . $this->goods_id)->update();
           
        // 当前系统时间
        $this->assign('now_time', gmtime());
        $this->assign('sales_count', model('GoodsBase')->get_sales_count($this->goods_id));
        $this->assign('image_width', C('image_width'));
        $this->assign('image_height', C('image_height'));
        $this->assign('id', $this->goods_id);
        $this->assign('type', 0);
        $this->assign('cfg', C('CFG'));
        // 促销信息
        $this->assign('promotion', model('GoodsBase')->get_promotion_info($this->goods_id));
        $this->assign('title', L('goods_detail'));
		//积分是否必用
		$surplus_part = floatval($goods['shop_price'])-floatval($goods['integral']);		
		$ky_info = "支付：(现金 ¥".$surplus_part."元 + 代金券 ".$goods['integral']."张)";
		$this->assign('ky_info', $ky_info);
		//微信分享功能 
		$timestamp = time();
        $wxnonceStr = $this->randStr();
        $wxticket = $this->wx_get_jsapi_ticket();
		if($_SESSION['user_id']){
			$dq_url = __HOST__ . $_SERVER['REQUEST_URI'];
			$dq_url2 = __HOST__ . $_SERVER['REQUEST_URI'] . "&sale=" . $_SESSION['user_id'];
		}else{
			$dq_url = __HOST__ . $_SERVER['REQUEST_URI'];
			$dq_url2 = __HOST__ . $_SERVER['REQUEST_URI'];
		}			
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
		$this->assign('wx_share', $wx_share);
		
        /* 页面标题 */
        $page_info = get_page_title($goods['cat_id'], $goods['goods_name']);
        $this->assign('ur_here', $page_info['ur_here']);
        $this->assign('page_title', $page_info['title']);
        $this->display('goods.dwt');
    }

    /**
     * 商品信息 
     */
    public function info() {
        /* 获得商品的信息 */
        $goods = model('Goods')->get_goods_info($this->goods_id);
        $this->assign('goods', $goods);
        $properties = model('Goods')->get_goods_properties($this->goods_id);  // 获得商品的规格和属性
        $this->assign('properties', $properties['pro']);                      // 商品属性
        $this->assign('specification', $properties['spe']);                   // 商品规格
        $this->assign('title', L('detail_intro'));
        $this->display('goods_info.dwt');
    }

    /**
     * 商品评论
     */
    public function comment_list() {
        $cmt = new stdClass();
        $cmt->id = !empty($_GET['id']) ? intval($_GET['id']) : 0;
        $cmt->type = !empty($_GET['type']) ? intval($_GET['type']) : 0;
        $cmt->page = isset($_GET['page']) && intval($_GET['page']) > 0 ? intval($_GET['page']) : 1;
        $this->assign('comments_info', model('Comment')->get_comment_info($cmt->id, $cmt->type));
        $this->assign('id', $cmt->id);
        $this->assign('type', $cmt->type);
        $this->assign('username', $_SESSION['user_name']);
        $this->assign('email', $_SESSION['email']);
        /* 验证码相关设置 */
        if ((intval(C('captcha')) & CAPTCHA_COMMENT) && gd_version() > 0) {
            $this->assign('enabled_captcha', 1);
            $this->assign('rand', mt_rand());
        }
        $result['message'] = C('comment_check') ? L('cmt_submit_wait') : L('cmt_submit_done');
        $this->assign('title', L('goods_comment'));
        $this->display('goods_comment_list.dwt');
    }

    /**
     * 改变属性、数量时重新计算商品价格
     */
    public function price() {
        //格式化返回数组
        $res = array(
            'err_msg' => '',
            'result' => '',
            'qty' => 1
        );
        // 获取参数
        $attr_id = isset($_REQUEST ['attr']) ? explode(',', $_REQUEST ['attr']) : array();
        $number = (isset($_REQUEST ['number'])) ? intval($_REQUEST ['number']) : 1;
        // 如果商品id错误
        if ($this->goods_id == 0) {
            $res ['err_msg'] = L('err_change_attr');
            $res ['err_no'] = 1;
        } else {
            // 查询
            $condition = 'goods_id =' . $this->goods_id;
            $goods = $this->model->table('goods')->field('goods_name , goods_number ,extension_code')->where($condition)->find();

            // 查询：系统启用了库存，检查输入的商品数量是否有效
// 			if (intval ( C('use_storage') ) > 0 && $goods ['extension_code'] != 'package_buy') {
// 				if ($goods ['goods_number'] < $number) {
// 					$res ['err_no'] = 1;
            //	$res ['err_msg'] = sprintf ( L('stock_insufficiency'), $goods ['goods_name'], $goods ['goods_number'], $goods ['goods_number'] );
// 					$res ['err_max_number'] = $goods ['goods_number'];
// 					die ( json_encode ( $res ) );
// 				}
// 			}
            if ($number <= 0) {
                $res ['qty'] = $number = 1;
            } else {
                $res ['qty'] = $number;
            }
            $shop_price = model('GoodsBase')->get_final_price($this->goods_id, $number, true, $attr_id);
            $res ['result'] = price_format($shop_price * $number);
        }
        die(json_encode($res));
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
}