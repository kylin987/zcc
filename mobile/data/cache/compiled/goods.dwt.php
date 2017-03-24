<?php echo $this->fetch('library/page_header.lbi'); ?>
<link rel="stylesheet" href="__PUBLIC__/bootstrap/css/font-eleganti.min.css">
<script type="text/javascript" src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
<script type="text/javascript">
// 微信配置
wx.config({
    debug: false, 
    appId: '<?php echo $this->_var['wx_share']['appid']; ?>', 
    timestamp: '<?php echo $this->_var['wx_share']['timestamp']; ?>', 
    nonceStr: '<?php echo $this->_var['wx_share']['wxnonceStr']; ?>', 
    signature: '<?php echo $this->_var['wx_share']['wxSha1']; ?>',
    jsApiList: ['onMenuShareTimeline', 'onMenuShareAppMessage'] // 功能列表，我们要使用JS-SDK的什么功能
});
// config信息验证后会执行ready方法，所有接口调用都必须在config接口获得结果之后，config是一个客户端的异步操作，所以如果需要在 页面加载时就调用相关接口，则须把相关接口放在ready函数中调用来确保正确执行。对于用户触发时才调用的接口，则可以直接调用，不需要放在ready 函数中。
wx.ready(function(){
    // 获取“分享到朋友圈”按钮点击状态及自定义分享内容接口
    wx.onMenuShareTimeline({
        title: '<?php echo $this->_var['page_title']; ?>', // 分享标题
        link: '<?php echo $this->_var['wx_share']['dq_url2']; ?>',
        imgUrl: "<?php echo $this->_var['goods']['goods_thumb']; ?>" // 分享图标
    });
    // 获取“分享给朋友”按钮点击状态及自定义分享内容接口
    wx.onMenuShareAppMessage({
        title: '<?php echo $this->_var['page_title']; ?>', // 分享标题
        desc: '<?php echo $this->_var['goods']['goods_brief']; ?>', // 分享描述
        link: '<?php echo $this->_var['wx_share']['dq_url2']; ?>',
        imgUrl: '<?php echo $this->_var['goods']['goods_thumb']; ?>', // 分享图标
        type: 'link', // 分享类型,music、video或link，不填默认为link
    });
});
</script>
<style type="text/css">
#k_share i {color: #1bae5d; font-size: 1.5em;line-height: 1.2;}
</style>
<div class="con">
  
  
  <div id="focus" class="focus goods-focus ect-padding-lr ect-margin-tb">
    <div class="hd">
      <ul>
      </ul>
    </div>
    <div class="bd">
      <ul id="Gallery">
        <li><a href="<?php echo $this->_var['goods']['goods_img']; ?>"><img src="<?php echo $this->_var['goods']['goods_img']; ?>" alt="<?php echo $this->_var['goods']['goods_name']; ?>" /></a></li>
        <?php if ($this->_var['pictures']): ?> 
        <?php $_from = $this->_var['pictures']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'picture');$this->_foreach['no'] = array('total' => count($_from), 'iteration' => 0);
if ($this->_foreach['no']['total'] > 0):
    foreach ($_from AS $this->_var['picture']):
        $this->_foreach['no']['iteration']++;
?> 
        <?php if ($this->_foreach['no']['iteration'] > 1): ?>
        <li><a href="<?php echo $this->_var['picture']['img_url']; ?>"><img src="<?php echo $this->_var['picture']['img_url']; ?>" alt="<?php echo $this->_var['picture']['img_desc']; ?>" /></a></li>
        <?php endif; ?> 
        <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?> 
        <?php endif; ?>
      </ul>
    </div>
  </div>
  
  <div class="goods-info ect-padding-tb"> 
    
    <section class="ect-margin-tb ect-margin-lr goods-title">
      <h4 class="title pull-left"><?php echo $this->_var['goods']['goods_style_name']; ?></h4>
      <span class="pull-right text-center <?php if ($this->_var['sc'] == 1): ?>ect-colory<?php endif; ?> ect-padding-lr" onClick="collect(<?php echo $this->_var['goods']['goods_id']; ?>)" id='ECS_COLLECT'> <i style="line-height:1.3" class="fa <?php if ($this->_var['sc'] == 1): ?>fa-heart<?php else: ?>fa-heart-o<?php endif; ?>"></i><br>
      <?php echo $this->_var['lang']['btn_collect']; ?> </span> 
	  <span class="pull-right text-center  ect-padding-lr" onclick="wx_share()" id="k_share"> <i class="social_share_circle k_share"></i><br>
      分享 </span> 
	  </section> 
    <section class="ect-margin-tb ect-margin-lr ">
      <p><span class="pull-left"><?php echo $this->_var['lang']['amount']; ?>：<b class="ect-colory" id="ECS_GOODS_AMOUNT"></b></span><span class="pull-right"><?php echo $this->_var['lang']['sort_sales']; ?>：<?php echo $this->_var['sales_count']; ?> <?php echo $this->_var['lang']['piece']; ?></span></p>
      <p><?php if ($this->_var['goods']['is_promote'] && $this->_var['goods']['gmt_end_time']): ?><?php echo $this->_var['lang']['promote_price']; ?><?php else: ?><?php endif; ?><?php if ($this->_var['goods']['is_promote'] && $this->_var['goods']['gmt_end_time']): ?> 
        <?php echo $this->_var['goods']['promote_price']; ?>
        <?php else: ?> 
       
        <?php endif; ?> <small> <del><?php if ($this->_var['cfg']['SHOW_MARKETPRICE']): ?>市场零售价：<?php echo $this->_var['goods']['market_price']; ?> <?php endif; ?></del></small>
	  </p>
	  <p><?php if ($this->_var['goods']['is_promote'] && $this->_var['goods']['gmt_end_time']): ?><strong id="leftTime" class="price"><?php echo $this->_var['lang']['please_waiting']; ?></strong><?php endif; ?></p>
		 
    </section>
	<section class="ect-margin-tb ect-margin-lr ">
      <!--<p><?php echo $this->_var['ky_info']; ?></p>-->		 
    </section>
    <?php if ($this->_var['promotion']): ?>
    <section class="ect-margin-tb ect-margin-bottom0 ect-padding-tb goods-promotion ect-padding-lr ">
      <h5><b><?php echo $this->_var['lang']['activity']; ?>：</b></h5>
      <p class="ect-border-top ect-margin-tb"> 
        <?php $_from = $this->_var['promotion']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'item');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['item']):
?> 
        <?php if ($this->_var['item']['type'] == "snatch"): ?> 
        <a href="<?php echo $this->_var['item']['url']; ?>" title="<?php echo $this->_var['lang']['snatch']; ?>"><i class="label tbqb"><?php echo $this->_var['lang']['snatch_act']; ?></i> [<?php echo $this->_var['lang']['snatch']; ?>]<i class="pull-right fa fa-angle-right"></i></a>
        <?php elseif ($this->_var['item']['type'] == "group_buy"): ?> 
        <a href="<?php echo $this->_var['item']['url']; ?>" title="<?php echo $this->_var['lang']['group_buy']; ?>"><i class="label tuan"><?php echo $this->_var['lang']['group_buy_act']; ?></i> [<?php echo $this->_var['lang']['group_buy']; ?>]<i class="pull-right fa fa-angle-right"></i></a> 
        <?php elseif ($this->_var['item']['type'] == "auction"): ?> 
        <a href="<?php echo $this->_var['item']['url']; ?>" title="<?php echo $this->_var['lang']['auction']; ?>"><i class="label pm"><?php echo $this->_var['lang']['auction_act']; ?></i> [<?php echo $this->_var['lang']['auction']; ?>]<i class="pull-right fa fa-angle-right"></i></a>
        <?php elseif ($this->_var['item']['type'] == "favourable"): ?> 
        <a href="<?php echo $this->_var['item']['url']; ?>" title="<?php echo $this->_var['lang'][$this->_var['item']['type']]; ?> <?php echo $this->_var['item']['act_name']; ?><?php echo $this->_var['item']['time']; ?>"> 
        <?php if ($this->_var['item']['act_type'] == 0): ?> 
        <i class="label mz"><?php echo $this->_var['lang']['favourable_mz']; ?></i> 
        <?php elseif ($this->_var['item']['act_type'] == 1): ?> 
        <i class="label mj"><?php echo $this->_var['lang']['favourable_mj']; ?></i> 
        <?php elseif ($this->_var['item']['act_type'] == 2): ?> 
        <i class="label zk"><?php echo $this->_var['lang']['favourable_zk']; ?></i> 
        <?php endif; ?><?php echo $this->_var['item']['act_name']; ?> <i class="pull-right fa fa-angle-right"></i></a> 
        <?php endif; ?> 
        <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?> 
      </p>
    </section>
    <?php endif; ?> 
    
    
    <form action="javascript:addToCart(<?php echo $this->_var['goods']['goods_id']; ?>)" method="post" name="ECS_FORMBUY" id="ECS_FORMBUY" >
      <section class="ect-padding-lr ect-padding-tb goods-option">
        
        <div class="goods-num"> <span class="pull-left"><?php echo $this->_var['lang']['number']; ?>：</span> 
          <?php if ($this->_var['goods']['goods_id'] > 0 && $this->_var['goods']['is_gift'] == 0 && $this->_var['goods']['parent_id'] == 0): ?>
          <div class="input-group pull-left wrap"><span class="input-group-addon sup" onClick="changePrice('1')">-</span>
            <input type="text" class="form-contro form-num"  name="number" id="goods_number" autocomplete="off" value="1" onFocus="back_goods_number()"  onblur="changePrice('2')"/>
            <span class="input-group-addon plus" onClick="changePrice('3')">+</span></div>
          <?php else: ?>
          <input type="text" class="form-contro form-num" readonly value="<?php echo $this->_var['goods']['goods_number']; ?> "/>
          <?php endif; ?> 
        </div>
      </section>
	  
	  
	  
	  
      <div class="goods-info ect-padding-tb">
		
		  <section class="user-tab ect-border-bottom0">
			<div id="is-nav-tabs" style="height:3.15em; display:none;"></div>
			
			<ul class="nav nav-tabs text-center">
			  <li class="col-xs-6 active"><a href="#one" role="tab" data-toggle="tab"><?php echo $this->_var['lang']['goods_brief']; ?></a></li>
			  <li class="col-xs-6"><a href="#two" role="tab" data-toggle="tab"><?php echo $this->_var['lang']['goods_attr']; ?></a></li>
			</ul>
			
			<div class="tab-content">
			  <div class="tab-pane tab-info active" id="one"> <?php echo $this->_var['goods']['goods_desc']; ?></div>
			  <div class="tab-pane tab-att" id="two">
				<table width="100%" border="0" cellpadding="3" cellspacing="1" bgcolor="#dddddd">
				  <?php $_from = $this->_var['properties']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'property_group');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['property_group']):
?>
				  <tr>
					<th colspan="2" bgcolor="#FFFFFF"><?php echo htmlspecialchars($this->_var['key']); ?></th>
				  </tr>
				  <?php $_from = $this->_var['property_group']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'property');if (count($_from)):
    foreach ($_from AS $this->_var['property']):
?>
				  <tr>
					<td bgcolor="#FFFFFF" align="left" width="30%" class="f1">[<?php echo htmlspecialchars($this->_var['property']['name']); ?>]</td>
					<td bgcolor="#FFFFFF" align="left" width="70%"><?php echo $this->_var['property']['value']; ?></td>
				  </tr>
				  <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?> 
				  <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
				</table>
			  </div>
			</div>
		  </section>
	  </div>
	  
	  
	  
	  
	  
      <div class="ect-padding-tb goods-submit">
        <div><a type="botton" class="btn btn-info ect-btn-info ect-colorf ect-bg" style="background:#1bae5d none repeat scroll 0 0 !important;" href="javascript:addToCart_quick(<?php echo $this->_var['goods']['goods_id']; ?>)"><?php echo $this->_var['lang']['buy_now']; ?></a></div>
      </div>
      <section class="user-tab ect-border-bottom0" style="display:none;">
        <div id="is-nav-tabs" style="height:3.15em; display:none;"></div>
        
        <ul class="nav nav-tabs text-center">
          <li class="col-xs-4 active"><a href="#one" role="tab" data-toggle="tab"><?php echo $this->_var['lang']['goods_brief']; ?></a></li>
          <li class="col-xs-4"><a href="#two" role="tab" data-toggle="tab"><?php echo $this->_var['lang']['goods_attr']; ?></a></li>
          <li class="col-xs-4"><a href="#three" role="tab" data-toggle="tab"><?php echo $this->_var['lang']['user_comment']; ?>(<?php echo $this->_var['goods']['comment_count']; ?>)</a></li>
        </ul>
        
        <div class="tab-content">
          <div class="tab-pane tab-info active" id="one"> <?php echo $this->_var['goods']['goods_desc']; ?></div>
          <div class="tab-pane tab-att" id="two">
            <table width="100%" border="0" cellpadding="3" cellspacing="1" bgcolor="#dddddd">
              <?php $_from = $this->_var['properties']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'property_group');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['property_group']):
?>
              <tr>
                <th colspan="2" bgcolor="#FFFFFF"><?php echo htmlspecialchars($this->_var['key']); ?></th>
              </tr>
              <?php $_from = $this->_var['property_group']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'property');if (count($_from)):
    foreach ($_from AS $this->_var['property']):
?>
              <tr>
                <td bgcolor="#FFFFFF" align="left" width="30%" class="f1">[<?php echo htmlspecialchars($this->_var['property']['name']); ?>]</td>
                <td bgcolor="#FFFFFF" align="left" width="70%"><?php echo $this->_var['property']['value']; ?></td>
              </tr>
              <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?> 
              <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
            </table>
          </div>
          
        </div>
      </section>
    </form>
  </div>
  
  <footer>
    <nav class="ect-nav2"><?php echo $this->fetch('library/page_menu2.lbi'); ?></nav>
  </footer>
  <div style="padding-bottom:10em;"></div>
</div>
<?php echo $this->fetch('library/search.lbi'); ?> <?php echo $this->fetch('library/page_footer.lbi'); ?> 
<script type="text/javascript" src="__TPL__/js/lefttime.js"></script> 
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function(){Code.photoSwipe('a', '#Gallery');}, false);


/*倒计时*/
var goods_id = <?php echo $this->_var['goods']['goods_id']; ?>;
var goodsattr_style = 1;
var goodsId = <?php echo $this->_var['goods_id']; ?>;

var gmt_end_time = "<?php echo empty($this->_var['goods']['gmt_end_time']) ? '0' : $this->_var['goods']['gmt_end_time']; ?>";
<?php $_from = $this->_var['lang']['goods_js']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'item');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['item']):
?>
var <?php echo $this->_var['key']; ?> = "<?php echo $this->_var['item']; ?>";
<?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
var now_time = <?php echo $this->_var['now_time']; ?>;

var use_how_oos = <?php echo C('use_how_oos');?>;
onload = function(){
  changePrice(2);
  fixpng();
  try {onload_leftTime();}
  catch (e) {}
}
function back_goods_number(){
 var goods_number = document.getElementById('goods_number').value;
  document.getElementById('back_number').value = goods_number;
}
/**
 * 点选可选属性或改变数量时修改商品价格的函数
 */
function changePrice(type)
{
  var qty = document.forms['ECS_FORMBUY'].elements['number'].value;
  if(type == 1){qty--;}
  if(type == 3){qty++;}
  if(qty <=0 ){qty=1;}
  if(!/^[0-9]*$/.test(qty)){qty = document.getElementById('back_number').value;}
  document.getElementById('goods_number').value = qty;
  var attr = getSelectedAttributes(document.forms['ECS_FORMBUY']);
  $.get('<?php echo url("goods/price");?>', {'id':goodsId,'attr':attr,'number':qty}, function(data){
    changePriceResponse(data);
  }, 'json');
}


/**
 * 接收返回的信息
 */
function changePriceResponse(res){
  if (res.err_msg.length > 0){
    alert(res.err_msg);
  } else {
    if (document.getElementById('ECS_GOODS_AMOUNT'))
      document.getElementById('ECS_GOODS_AMOUNT').innerHTML = res.result;
  }
}

/**
 * 接收返回的信息
 */
function changePriceResponse(res){
  if (res.err_msg.length > 0){
    alert(res.err_msg);
  } else {
    if (document.getElementById('ECS_GOODS_AMOUNT'))
      document.getElementById('ECS_GOODS_AMOUNT').innerHTML = res.result;
  }
}

/*判断user-tab是否距顶，距顶悬浮*/
var nav_tabs_top = $(".user-tab .nav-tabs").offset().top;//获取nav-tabs距离顶部的位
function func_scroll(){//定义一个事件效果置
	var documentTop = $(document).scrollTop();//获取滚动条距离顶部距离
	if(nav_tabs_top <= documentTop){
		$(".user-tab").addClass("user-tab-fixed");
		$("#is-nav-tabs").css("display","block");
	}else{
		$(".user-tab").removeClass("user-tab-fixed");
		$("#is-nav-tabs").css("display","none");		
	}
}

window.onscroll = function () {
	 func_scroll();
}
</script> 
<script type="text/javascript">
	TouchSlide({ 
		slideCell:"#picScroll",
		titCell:".hd ul", //开启自动分页 autoPage:true ，此时设置 titCell 为导航元素包裹层
		autoPage:"true", //自动分页
		pnLoop:"false", // 前后按钮不循环
		switchLoad:"_src" //切换加载，真实图片路径为"_src" 
	});
</script>
<script type="text/javascript">
	function wx_share(){
		$("#mcover").css("display","block");    // 分享给好友按钮触动函数
		
	}	
	function weChat(){
		$("#mcover").css("display","none");  // 点击弹出层，弹出层消失
	}	
</script>
<div id="mcover" onclick="weChat()" style="display:none;">
    <img src="__ROOT__/themes/default/images/k_share.png" />
</div>			
</body></html>