<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ArticleControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：文章控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class ArticleController extends CommonController {

    private $size = 10;
    private $page = 1;
    private $cat_id = 0;
    private $keywords = '';

    public function __construct() {
        parent::__construct();
        $this->cat_id = intval(I('get.id'));
    }

    /* ------------------------------------------------------ */

    //-- 文章分类
    /* ------------------------------------------------------ */
    public function index() {
        $cat_id = intval(I('get.id'));
        $this->assign('article_categories', model('Article')->article_categories_tree($cat_id)); //文章分类树
        $this->display('article_cat.dwt');
    }

    /* ------------------------------------------------------ */

    //-- 文章列表
    /* ------------------------------------------------------ */
    public function art_list() {
        $this->parameter();
        $this->assign('keywords', $this->keywords);
        $this->assign('id', $this->cat_id);
		$this->assign('user_id', $_SESSION['user_id']);
        $artciles_list = model('ArticleBase')->get_cat_articles($this->cat_id, $this->page, $this->size, $this->keywords);
        $count = model('ArticleBase')->get_article_count($this->cat_id, $this->keywords);
        $this->pageLimit(url('art_list', array('id' => $this->cat_id)), $this->size);
        $this->assign('pager', $this->pageShow($count));
        $this->assign('artciles_list', $artciles_list);
        $this->display('article_list.dwt');
    }

    /**
     * 文章列表异步加载
     */
    public function asynclist() {
        $this->parameter();
        $asyn_last = intval(I('post.last')) + 1;
        $this->size = I('post.amount');
        $this->page = ($asyn_last > 0) ? ceil($asyn_last / $this->size) : 1;
        $list = model('ArticleBase')->get_cat_articles($this->cat_id, $this->page, $this->size, $this->keywords);
        $id = ($this->page - 1) * $this->size + 1;
        foreach ($list as $key => $value) {
            $this->assign('id', $id);			
            $this->assign('article', $value);
            $sayList [] = array(
                'single_item' => ECTouch::view()->fetch('library/asynclist_info.lbi')
            );
            $id++;
        }
        die(json_encode($sayList));
        exit();
    }
	
	/**
     * 会员中心——文章列表异步加载
     */
    public function asynclist2() {
        $this->parameter();
        $asyn_last = intval(I('post.last')) + 1;
        $this->size = I('post.amount');
        $this->page = ($asyn_last > 0) ? ceil($asyn_last / $this->size) : 1;
        $list = model('ArticleBase')->get_cat_articles($this->cat_id, $this->page, $this->size, $this->keywords);
        $id = ($this->page - 1) * $this->size + 1;
        foreach ($list as $key => $value) {
            $this->assign('id', $id);
			$this->assign('user_id', $_SESSION['user_id']);
            $this->assign('article', $value);
            $sayList [] = array(
                'single_item' => ECTouch::view()->fetch('library/asynclist_info2.lbi')
            );
            $id++;
        }
        die(json_encode($sayList));
        exit();
    }

    /* ------------------------------------------------------ */
    //-- 文章详情
    /* ------------------------------------------------------ */
    public function info() {
        /* 文章详情 */
        $article_id = intval(I('get.aid'));
        $article = model('Article')->get_article_info($article_id);
        $this->assign('article', $article);
		
		//获取用户信息
		$user_id = intval(I('get.sale'));
		//$user_rank = model('ClipsBase')->get_user_rank($user_id);

		
		/* 读者信息 */			
		//$userinfo['headimg'] = model('ClipsBase')->get_user_nc_tx($user_id,1);
		//$userinfo['nicheng'] = model('ClipsBase')->get_user_nc_tx($user_id,2);
		//$userinfo['re_code'] = model('ClipsBase')->add_re_code($user_id);
		
		$share_img = '';
		$mobile_qr = 'data/sale2/sale_datu_'.$user_id.'.jpg';
		$mobile_qr2 = 'data/sale/sale_datu_'.$user_id.'.jpg';
		if(file_exists($mobile_qr)){
			$share_img = $mobile_qr;
		}else{
			$share_img = $mobile_qr2;
		}
		$userinfo['share_img']=$share_img;
		$this->assign('share', 1);
		$this->assign('userinfo', $userinfo);
		
		
		
        $this->display('article_info.dwt');
    }

    /* ------------------------------------------------------ */
    //-- 微信图文详情
    /* ------------------------------------------------------ */
    public function wechat_news_info() {
        /* 文章详情 */
        $news_id = intval(I('get.id'));
        $data = $this->model->table('wechat_media')->field('title, content')->where('id = ' . $news_id)->find();
        $data['content'] = htmlspecialchars_decode($data['content']);
        $this->assign('article', $data);
        $this->display('article_info.dwt');
    }

    /**
     * 处理参数便于搜索商品信息
     */
    private function parameter() {
        $this->assign('show_asynclist', C('show_asynclist'));
        // 如果分类ID为0，则返回总分类页
        $page_size = C('article_number');
        $this->size = intval($page_size) > 0 ? intval($page_size) : $this->size;
        $this->page = I('request.page') ? intval(I('request.page')) : 1;
        $this->cat_id = intval(I('request.id'));
        $this->keywords = I('request.keywords');
    }

}