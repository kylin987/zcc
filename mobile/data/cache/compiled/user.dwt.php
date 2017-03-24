<?php echo $this->fetch('library/user_header.lbi'); ?>

<div class="user-share-user">
<div class="user-share-info">
	<div class="img k_headimg"><?php if ($this->_var['info']['u_headimg']): ?><img src="<?php echo $this->_var['info']['u_headimg']; ?>"><?php elseif ($this->_var['info']['headimgurl']): ?><img src="<?php echo $this->_var['info']['headimgurl']; ?>"><?php else: ?><img src="../../__TPL__/images/moren.png"/><?php endif; ?></div>
	<div class="k_text"> 
	    <p class="k_nicheng"><?php if ($this->_var['info']['nicheng']): ?><?php echo $this->_var['info']['nicheng']; ?><?php elseif ($this->_var['info']['nickname']): ?><?php echo $this->_var['info']['nickname']; ?><?php else: ?><?php echo $this->_var['info']['username']; ?><?php endif; ?></p>				
		<!--<p class="k_rankname"><a href="<?php echo url('user/edit_password');?>" class="k_hongbao">修改密码</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="<?php echo url('user/logout');?>" class="ect-colorf"><?php echo $this->_var['lang']['label_logout']; ?></a></p>-->
		<p class="k_hongbao"><?php echo $this->_var['rank_name']; ?><?php if ($this->_var['proxy_area']): ?> | <?php echo $this->_var['proxy_area']; ?>总代理<?php endif; ?>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="<?php echo url('user/logout');?>" class="ect-colorf k_rankname"><?php echo $this->_var['lang']['label_logout']; ?></a></p> 

		<p class="k_recode">邀请口令：<?php echo $this->_var['t_re_code']; ?></p>
       
	  <table width="100%" border="0" align="center" cellpadding="0" cellspacing="4" class="k_hongbao" background="__TPL__/images/grzzbj01.png" >
		  <tr>
		    <td width="33%" height="55" align="center" valign="middle" class="k_hongbao"><a href="<?php echo url('user/account_detail');?>" class="k_hongbao">余额</a>(元)<br>
		       <?php echo empty($this->_var['info']['surplus']) ? '0' : $this->_var['info']['surplus']; ?> <!--&nbsp;&nbsp;|&nbsp;&nbsp;<a href="<?php echo url('user/account_deposit');?>" class="k_hongbao">充值</a> | <a href="<?php echo url('user/account_raply');?>" class="k_hongbao">提现</a>--></td>
		    <td width="34%" height="55" align="center" valign="middle" class="k_hongbao">平台市值(元)<br>
	        <?php echo $this->_var['market_value']; ?></td>
		    <td width="33%" height="55" align="center" valign="middle" class="k_hongbao">平台市盈率<br><?php echo $this->_var['pe_ratios']; ?></td>
	      </tr>
	  </table>
		
		<?php if ($this->_var['user_rank_id'] == '1'): ?>
			<?php if ($this->_var['frozen_premium']): ?>
	  <p class="k_hongbao" style="color:#222222"><b><a style="color:#222222" href="<?php echo url('user/account_raply');?>" class="k_hongbao">冻结入股金：</a><?php echo $this->_var['frozen_premium']; ?>(元) | <a style="color:#222222" href="<?php echo url('user/account_raply');?>">提现/转余额</a></b></p>
			<?php else: ?>
	  <p class="k_hongbao"><b><a href="<?php echo url('user/apply_sale');?>" style="color:#222222" >申请成为合伙人</a></b></p>
			<p><?php endif; ?>
            
		<?php elseif ($this->_var['user_rank_id'] == '138' || $this->_var['user_rank_id'] == '144'): ?> </p>
		<p class="k_recode">合伙人每月/份任务：<?php echo $this->_var['assess_value']; ?></p>
	  
	  <table width="100%" border="0" align="center" cellpadding="0" cellspacing="4" class="k_hongbao" background="__TPL__/images/grzzbj01.png" >
		  <tr>
		    <td width="33%" height="55" align="center" valign="middle" class="k_hongbao">平台纯利润(元)<br>
<?php echo $this->_var['pure_profit']; ?></td>
		    <td width="34%" align="center" valign="middle" class="k_hongbao">平台入股金(元)<br>
	        <?php echo $this->_var['all_premium']; ?></td>
		    <td width="33%" align="center" valign="middle" class="k_hongbao">未分配利润(元)<br>
	        <?php echo $this->_var['no_dis_profit']; ?></td>
        </tr>
	  </table>

<?php $_from = $this->_var['user_stock']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'vo');$this->_foreach['user_stock'] = array('total' => count($_from), 'iteration' => 0);
if ($this->_foreach['user_stock']['total'] > 0):
    foreach ($_from AS $this->_var['vo']):
        $this->_foreach['user_stock']['iteration']++;
?>
	  <table width="100%" border="0" align="center" cellpadding="0" cellspacing="4" class="k_hongbao" background="__TPL__/images/grzzbj01.png" >
        <tr>
          <td width="33%" height="55" align="center" valign="middle" class="k_hongbao"><?php if ($this->_var['vo']['is_first']): ?>入股金(初始/元)<?php else: ?>入股金(追/元)<?php endif; ?><br>
            <?php echo $this->_var['vo']['user_premium']; ?></td>
          <td width="34%" align="center" valign="middle" class="k_hongbao">入股时间<br><?php echo $this->_var['vo']['premium_time']; ?></td>
          <td width="33%" align="center" valign="middle" class="k_hongbao">入股金价值(元)<br>
            <?php echo $this->_var['vo']['last_stock']; ?></td>
        </tr>
        
        <tr>
          <td width="33%" height="55" align="center" valign="middle" class="k_hongbao">任务达成(元)<br>
<?php echo $this->_var['vo']['now_assess_value']; ?></td>
          <td width="34%" align="center" valign="middle" class="k_hongbao"><?php if ($this->_var['vo']['is_first']): ?><b><a href="<?php echo url('user/apply_index');?>" style="color:#222222" >合伙人协议</a></b><?php if ($this->_var['frozen_premium']): ?><br>
          <b><a href="<?php echo url('user/account_raply');?>" style="color:#222222" >冻结入股金：<?php echo $this->_var['frozen_premium']; ?>(元)</a></b><?php else: ?><?php if ($this->_var['done_stock']): ?><br><b><a href='<?php echo url('user/add_stock');?>' style="color:#222222">追加入股金</a></b><?php endif; ?><?php endif; ?><?php endif; ?></td>
          <td width="33%" align="center" valign="middle" class="k_hongbao">年度达标还需(元)<br>
            <?php echo $this->_var['vo']['year_assess_value']; ?></td>
        </tr>
      </table>
<?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>      

	  <?php endif; ?>
	</div>
</div>
</div>
<section class="container-fluid user-nav">
  <ul class="row ect-row-nav text-center">
  	<a href="<?php echo url('user/order_list');?>">
    <li class="col-sm-3 col-xs-3"><i><img src="__TPL__/images/grzx/a_zc01.png"></i>
      <p class="text-center"><?php echo $this->_var['lang']['order_list_lnk']; ?></p>
    </li>
    </a> 
	<a href="<?php echo url('user/profile');?>">
    <li class="col-sm-3 col-xs-3"><i><img src="__TPL__/images/grzx/a_zc02.png"></i>
      <p class="text-center"><?php echo $this->_var['lang']['profile']; ?></p>
    </li>
    </a> 
   
	<a href="<?php echo url('user/address_list');?>">
    <li class="col-sm-3 col-xs-3"><i><img src="__TPL__/images/grzx/a_zc03.png"></i>
      <p class="text-center"><?php echo $this->_var['lang']['label_address']; ?></p>
    </li>
    </a>
    	
    	<!--<a href="<?php echo url('user/edit_password');?>">
    <li class="col-sm-3 col-xs-3"><i><img src="__TPL__/images/grzx/a_zc04.png"></i>
      <p class="text-center">修改密码</p>
    </li>
    </a>-->

	<a href="<?php echo url('user/service');?>">
    <li class="col-sm-3 col-xs-3"><i><img src="__TPL__/images/grzx/a_zc05.png"></i>
      <p class="text-center">咨询留言</p>
    </li>
    </a> 
    
	<!--<a href="<?php echo url('user/spree');?>">
    <li class="col-sm-3 col-xs-3"><i class="glyphicon glyphicon-gift"></i>
      <p class="text-center">我的大礼包</p>
    </li>
    </a>-->
	
     <a href="<?php echo url('sale/line');?>">
    <li class="col-sm-3 col-xs-3"><i><img src="__TPL__/images/grzx/a_zc06.png"></i>
      <p class="text-center"><?php echo $this->_var['lang']['wodebuluo']; ?></p>
    </li>
    </a>

	
	<!--<a href="<?php echo url('sale/order_list');?>">
    <li class="col-sm-3 col-xs-3"><i><img src="__TPL__/images/grzx/a_zc07.png"></i>
      <p class="text-center">客户订单</p>
    </li>
    </a>-->
	
	<a href="<?php echo url('erwei/to_sale');?>&sale=<?php echo $this->_var['user_id']; ?>">
    <li class="col-sm-3 col-xs-3"><i><img src="__TPL__/images/grzx/a_zc08.png"></i>
      <p class="text-center">我的二维码</p>
    </li>
    </a>
	  
	<a href="<?php echo url('user/transfer_surplus');?>">
    <li class="col-sm-3 col-xs-3"><i><img src="__TPL__/images/grzx/a_zc09.png"></i>
      <p class="text-center">余额转账</p>
    </li>
    </a>
		
	<a href="<?php echo url('user/account_detail');?>">
    <li class="col-sm-3 col-xs-3"><i><img src="__TPL__/images/grzx/a_zc10.png"></i>
      <p class="text-center">资产管理</p>
    </li>
    </a>	
    
    	<a href="<?php echo url('article/index');?>">
    <li class="col-sm-3 col-xs-3"><i><img src="__TPL__/images/grzx/a_zc11.png"></i>
      <p class="text-center">资讯公告</p>
    </li>
    </a>	

	<a href="<?php echo url('erwei/per_qrcode');?>&sale=<?php echo $this->_var['user_id']; ?>">
    <li class="col-sm-3 col-xs-3"><i><img src="__TPL__/images/grzx/a_zc12.png"></i>
      <p class="text-center">个性二维码</p>
    </li>
    </a>
    
   	<a href="<?php echo url('#');?>&sale=<?php echo $this->_var['user_id']; ?>">
    <li class="col-sm-3 col-xs-3"><i><img src="__TPL__/images/grzx/a_zc13.png"></i>
      <p class="text-center">敬请期待</p>
    </li>
    </a>
 
    	<a href="<?php echo url('#');?>&sale=<?php echo $this->_var['user_id']; ?>">
    <li class="col-sm-3 col-xs-3"><i><img src="__TPL__/images/grzx/a_zc13.png"></i>
      <p class="text-center">敬请期待</p>
    </li>
    </a>


    	<!--<a href="<?php echo url('#');?>">
    <li class="col-sm-3 col-xs-3"><i><img src="__TPL__/images/a_zc03.png"></i>
      <p class="text-center">平台总资产</p>
    </li>
    </a>	

    	<a href="<?php echo url('#');?>">
    <li class="col-sm-3 col-xs-3"><i><img src="__TPL__/images/a_zc04.png"></i>
      <p class="text-center">平台未分配利润</p>
    </li>
    </a>	

    	<a href="<?php echo url('#');?>">
    <li class="col-sm-3 col-xs-3"><i><img src="__TPL__/images/a_zc05.png"></i>
      <p class="text-center">平台入股金总额</p>
    </li>
    </a>	

    	<a href="<?php echo url('#');?>">
    <li class="col-sm-3 col-xs-3"><i><img src="__TPL__/images/a_zc06.png"></i>
      <p class="text-center">平台纯利润</p>
    </li>
    </a>	
    
   <a href="<?php echo url('user/transfer_coin');?>">
    <li class="col-sm-3 col-xs-3"><i><img src="__TPL__/images/a_djq.png"></i>
      <p class="text-center">代金券转账</p>
    </li>
    </a> -->	 
  </ul>
</section>
<!-- <div class="article-list2"> 
  <div id="index_con">
   <p style="text-align:center;">
   <img src="__TPL__/images/indl/W06-1.png" /></p>
	</div>
  <div class="article-list-ol2">
    <div id="J_ItemList">
      <dl class="single_item"> </dl>
      <a href="javascript:;" class="get_more"></a>
    </div>
  </div> -->
  

</div>

  <footer>
    <nav class="ect-nav2"><?php echo $this->fetch('library/page_menu2.lbi'); ?></nav>
  </footer>
  <div style="padding-bottom:5.5em;"></div>
</div>
<?php echo $this->fetch('library/search.lbi'); ?> <?php echo $this->fetch('library/page_footer.lbi'); ?> 
<script type="text/javascript">
	get_asynclist("<?php echo url('article/asynclist2', array('id'=>$this->_var['id'], 'page'=>$this->_var['page'], 'sort'=>$this->_var['sort'], 'keywords'=>$this->_var['keywords']));?>" , '__TPL__/images/loader.gif');
	var notice = '<?php echo $this->_var['notice']; ?>';
	if(notice == '1'){
		alert('尊敬的合伙人您好！您本月业绩尚未达标，请尽快达成！以免影响您的收益！');
	}	
</script>
</body>
</html>
