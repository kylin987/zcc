<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：news.php
 * ----------------------------------------------------------------------------
 * 功能描述：微信通-精品查询
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
if (! defined('IN_ECTOUCH')) {
    die('Deny Access');
}

/**
 * 砸金蛋
 *
 * @author wanglu
 *        
 */
class dzp extends PluginWechatController
{
    // 插件名称
    protected $plugin_name = '';
    // 配置
    protected $cfg = array();

    /**
     * 构造方法
     *
     * @param unknown $cfg            
     */
    public function __construct($cfg = array())
    {
        $name = basename(__FILE__, '.class.php');
        $this->plugin_name = $name;
        $this->cfg = $cfg;
    }

    /**
     * 安装
     */
    public function install()
    {
        // 编辑
        if (! empty($this->cfg['handler']) && is_array($this->cfg['config'])) {
            // url处理
            if (! empty($this->cfg['config']['plugin_url'])) {
                $this->cfg['config']['plugin_url'] = html_out($this->cfg['config']['plugin_url']);
            }
            // 奖品处理
            if (is_array($this->cfg['config']['prize_level']) && is_array($this->cfg['config']['prize_type']) && is_array($this->cfg['config']['prize_count']) && is_array($this->cfg['config']['prize_prob']) && is_array($this->cfg['config']['prize_name'])) {
                foreach ($this->cfg['config']['prize_level'] as $key => $val) {
                    $this->cfg['config']['prize'][] = array(
                        'prize_level' => $val,
						'prize_type' => $this->cfg['config']['prize_type'][$key],
                        'prize_name' => $this->cfg['config']['prize_name'][$key],
                        'prize_count' => $this->cfg['config']['prize_count'][$key],
                        'prize_prob' => $this->cfg['config']['prize_prob'][$key]
                    );
                }
            }
        }
        $this->plugin_display('install', $this->cfg);
    }

    /**
     * 获取数据
     */
    public function show($fromusername, $info)
    {
        $articles = array('type'=>'text', 'content'=>'未启用大转盘');
        // 插件配置
        $config = $this->get_config($this->plugin_name);
        // 页面信息
        if (isset($config['media']) && ! empty($config['media'])) {
            $articles = array();
            // 数据
            $articles['type'] = 'news';
            $articles['content'][0]['Title'] = $config['media']['title'];
            $articles['content'][0]['Description'] = $config['media']['content'];
            // 不是远程图片
            if (! preg_match('/(http:|https:)/is', $config['media']['file'])) {
                $articles['content'][0]['PicUrl'] =  __URL__ . '/' . $config['media']['file'];
            } else {
                $articles['content'][0]['PicUrl'] = $config['media']['file'];
            }
            $articles['content'][0]['Url'] = html_out($config['media']['link']);
            // 积分赠送
            $this->give_point($fromusername, $info);
        }
        
        return $articles;
    }

    /**
     * 积分赠送
     *
     * @param unknown $fromusername            
     * @param unknown $info            
     */
    public function give_point($fromusername, $info)
    {
        if (! empty($info)) {
            // 配置信息
            $config = array();
            $config = unserialize($info['config']);
            // 开启积分赠送
            if (isset($config['point_status']) && $config['point_status'] == 1) {
                $where = 'openid = "' . $fromusername . '" and keywords = "' . $info['command'] . '" and createtime > (UNIX_TIMESTAMP(NOW())- ' . $config['point_interval'] . ')';
                $num = model('Base')->model->table('wechat_point')
                    ->field('createtime')
                    ->where($where)
                    ->order('createtime desc')
                    ->count();
                // 当前时间减去时间间隔得到的历史时间之后赠送的次数
                if ($num < $config['point_num']) {
                    $this->do_point($fromusername, $info, $config['point_value']);
                }
            }
        }
    }
	

    /**
     * 页面显示
     */
    public function html_show()
    {
        // 插件配置
        $config = $this->get_config($this->plugin_name);
        $starttime = strtotime($config['starttime']);
        $endtime = strtotime($config['endtime']);
        // 用户抽奖剩余的次数
        $openid = session('openid');  
		$day_start = strtotime(date("Y-m-d"));     
        $where = 'openid = "' . $openid . '"  and activity_type = "'.$this->plugin_name.'" and yb_type = "free" and dateline > ' . $day_start;
        $num = model('Base')->model->table('wechat_prize')
			->field('dateline')
			->where($where)
			->order('dateline desc')
			->count();			
			
        $config['free_prize_num'] = ($config['free_prize_num'] - $num) < 0 ? 0 : $config['free_prize_num'] - $num;
        // 中奖记录
        $sql = 'SELECT u.nickname, p.prize_name, p.id FROM ' . model('Base')->model->pre . 'wechat_prize p LEFT JOIN ' . model('Base')->model->pre . 'wechat_user u ON p.openid = u.openid where dateline between "' . $starttime . '" and "' . $endtime . '" and p.prize_type = 1 and p.activity_type = "'.$this->plugin_name.'" ORDER BY dateline desc limit 10';
        $list = model('Base')->model->query($sql);
        
        $file = ROOT_PATH . 'plugins/wechat/' . $this->plugin_name . '/view/index.php';
        if (file_exists($file)) {
            require_once ($file);
        }
    }

    /**
     * 行为操作
     */
    public function action()
    {
        // 信息提交
        if (IS_POST) {
            $id = I('post.id');
            $data = I('post.data');
            if (empty($id)) {
                show_message('请选择中奖的奖品', '', '', 'error');
            }
            if (empty($data['phone'])) {
                show_message('请填写手机号', '', '', 'error');
            }
            if (empty($data['address'])) {
                show_message('请填写详细地址', '', '', 'error');
            }
            $winner['winner'] = serialize($data);
            
            model('Base')->model->table('wechat_prize')
                ->data($winner)
                ->where('id = ' . $id)
                ->update();
            show_message('资料提交成功，请等待发放奖品', '继续抽奖', url('wechat/plugin_show', array(
                'name' => $this->plugin_name
            )));
            exit();
        }
        // 获奖用户资料填写页面
        if (! empty($_GET['id']) && ! IS_AJAX) {
            $id = I('get.id');
            $rs = model('Base')->model->table('wechat_prize')
                ->field('winner')
                ->where('openid = "' . session('openid') . '" and id = ' . $id)
                ->getOne();
            if (! empty($rs)) {
                show_message('已经领取', '', '', 'error');
            }
			$user_address=$this->get_address(session('openid'));					
            $file = ROOT_PATH . 'plugins/wechat/' . $this->plugin_name . '/view/user_info.php';
            if (file_exists($file)) {
                require_once ($file);
            }
            exit();
        }
        // 抽奖操作
        if (IS_GET && IS_AJAX) {			
			$arg1=$_GET['ky'];
            $rs = array();
            // 未登录
            $openid = session('openid');
            if (empty($openid)) {
                $rs['status'] = 2;
                $rs['msg'] = '请先登录';
                echo json_encode($rs);
                exit();
            }
            
            // 插件配置
            $config = $this->get_config($this->plugin_name);
            // 活动过期
            $starttime = strtotime($config['starttime']);
            $endtime = strtotime($config['endtime']);
            if (time() < $starttime) {
                $rs['status'] = 2;
                $rs['msg'] = '活动未开始';
                echo json_encode($rs);
                exit();
            }
            if (time() > $endtime) {
                $rs['status'] = 2;
                $rs['msg'] = '活动已结束';
                echo json_encode($rs);
                exit();
            }
            // 超过次数
			$day_start = strtotime(date("Y-m-d"));
			$num = model('Base')->model->table('wechat_prize')
				->where('openid = "' . $openid . '"  and activity_type = "'.$this->plugin_name.'" and yb_type = "free" and dateline > ' . $day_start )
				->count();
			if ($num <= 0) {
				$num = 1;
			} else {
				$num = $num + 1;
			}
            
            if ($num > $config['free_prize_num']) {	                
				if($arg1){
					//$rs['msg'] = '有值'.$arg1;
					$u_yinbi = intval($this->get_yinbi($openid)) - intval($config['xh_yinbi']);
					if($u_yinbi<0){
						$rs['status'] = 2;
						$rs['msg'] = '您的银币不足，无法抽奖';
						echo json_encode($rs);
						exit();
					}else{
						$xh_yinbi=-$config['xh_yinbi'];
						$res = $this->edit_yinbi($openid,$xh_yinbi,"大转盘抽奖消费");
					}
							
				}else{
					$rs['status'] = 3;
                	$rs['msg'] = '免费抽奖次数已用完，是否消耗'.$config['xh_yinbi'].'银币进行抽奖';	
					echo json_encode($rs);
                	exit();				
				}
                
            }
            
            $prize = $config['prize'];
            if (! empty($prize)) {
                $arr = array();
                $prize_name = array();
                // 默认公众号信息
                $wxid = model('Base')->model->table('wechat')
                    ->field('id')
                    ->where('default_wx = 1')
                    ->getOne();
                $prob = 0;
                foreach ($prize as $key => $val) {
                    // 删除数量不足的奖品
                    $count = model('Base')->model->table('wechat_prize')
                        ->where('prize_name = "' . $val['prize_name'] . '" and activity_type = "'.$this->plugin_name.'" and wechat_id = ' . $wxid)
                        ->count();
                    if ($count >= $val['prize_count']) {
                        unset($prize[$key]);
                    } else {
                        $arr[$val['prize_level']] = $val['prize_prob'];
                        $prize_name[$val['prize_level']] = $val['prize_name'];
						$prize_type[$val['prize_level']] = $val['prize_type'];
                    }
                    //添加项的总概率
                    $prob = $prob + $val['prize_prob'];
                }
                //未中奖的概率项
                if($prob < 100){
                	$prob = 100 - $prob;
                	$arr['not'] = $prob;
                }
                //抽奖
                $level = $this->get_rand($arr);
				
                if($level == 'not'){
                	$data['prize_type'] = 0;
                	$rs['msg'] = '没有中奖';
                	$rs['status'] = 0;
                }
                else{
                	$data['prize_type'] = 1;			//写入数据库是否中奖	
					
					
					if($prize_type[$level]==1){		//这里的prize_type和上面的中奖不同，这里是判断银币还是实物
						$rs['msg'] = $prize_name[$level];
						$rs['status'] = 4;
						$rs['level'] = $level;
						$yb_msg="大转盘抽奖喜中".$level."，获得".$prize_name[$level];
						$jl_yinbi = intval($prize_name[$level]);
						$yb_res = $this->edit_yinbi($openid,$jl_yinbi,$yb_msg);
					}else{
						$rs['msg'] = $prize_name[$level];
						$rs['status'] = 1;
						$rs['level'] = $level;
					}
                }
                
                
                $rs['num'] = $config['free_prize_num'] - $num > 0 ? $config['free_prize_num'] - $num : 0;
                // 抽奖记录
                $data['wechat_id'] = $wxid;
                $data['openid'] = $openid;
                $data['prize_name'] = $prize_name[$level];
				if($yb_res){
					$data['issue_status'] = 1;
				}
                $data['dateline'] = time();
                $data['activity_type'] = $this->plugin_name;
				if($arg1){
					$data['yb_type'] = "yinbi";
				}else{
					$data['yb_type'] = "free";
				}
                $id = model('Base')->model->table('wechat_prize')->data($data)->insert();
                 //参与人数增加
                $extend_cfg = model('Base')->model->table('wechat_extend')->where(array('wechat_id'=>$wxid, 'command'=>$this->plugin_name, 'enable'=>1))->field('config')->getOne();
                if($extend_cfg){
                    $cfg_new = unserialize($extend_cfg);
                }
                $cfg_new['people_num'] = $cfg_new['people_num'] + 1;
                $cfg['config'] = serialize($cfg_new);
                model('Base')->model->table('wechat_extend')->where(array('wechat_id'=>$wxid, 'command'=>$this->plugin_name, 'enable'=>1))->data($cfg)->update();
                if ($level != 'not' && !empty($id)) {
                    // 获奖链接
                    $rs['link'] = url('wechat/plugin_action', array(
                        'name' => $this->plugin_name,
                        'id' => $id
                    ));
                    $rs['link'] = str_replace('&amp;', '&', $rs['link']);
                }
            }
            
            echo json_encode($rs);
            exit();
        }
    }

    /**
     * 获取插件配置信息
     *
     * @param string $code            
     * @return multitype:unknown
     */
    private function get_config($code = '')
    {
        // 默认公众号信息
        $config = array();
        $wxid = model('Base')->model->table('wechat')
            ->field('id')
            ->where('default_wx = 1')
            ->getOne();
        if (! empty($wxid)) {
            $plugin_config = model('Base')->model->table('wechat_extend')
                ->field('config')
                ->where('wechat_id = ' . $wxid . ' and command = "' . $code . '" and enable = 1')
                ->getOne();
            if (! empty($plugin_config)) {
                $config = unserialize($plugin_config);
                // 素材
                if (! empty($config['media_id'])) {
                    $media = model('Base')->model->table('wechat_media')
                    ->field('id, title, file, file_name, type, content, add_time, article_id, link')
                    ->where('id = ' . $config['media_id'])
                    ->find();
                    // 单图文
                    if (empty($media['article_id'])) {
                        $media['content'] = strip_tags(html_out($media['content']));
                        $config['media'] = $media;
                    }
                }
                // url处理
                if (! empty($config['plugin_url'])) {
                    $config['plugin_url'] = html_out($config['plugin_url']);
                }
                // 奖品处理
                if (is_array($config['prize_level']) && is_array($config['prize_type']) && is_array($config['prize_count']) && is_array($config['prize_prob']) && is_array($config['prize_name'])) {
                    foreach ($config['prize_level'] as $key => $val) {
                        $config['prize'][] = array(
                            'prize_level' => $val,
							'prize_type' => $config['prize_type'][$key],
                            'prize_name' => $config['prize_name'][$key],
                            'prize_count' => $config['prize_count'][$key],
                            'prize_prob' => $config['prize_prob'][$key]
                        );
                    }
                }
            }
        }
        return $config;
    }	
}
