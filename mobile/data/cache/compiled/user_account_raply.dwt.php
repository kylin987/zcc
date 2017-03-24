<?php echo $this->fetch('library/user_header.lbi'); ?>
<ul class="nav nav-tabs" role="tablist">
    <li><a href="<?php echo url('User/account_detail');?>" ><?php echo $this->_var['lang']['add_surplus_log']; ?></a></li>	
	 <!--<li><a href="<?php echo url('User/account_detail3');?>" ><?php echo $this->_var['lang']['add_sliv_log']; ?></a></li>-->
	 <!--<li><a href="<?php echo url('User/account_detail2');?>" >消费余额</a></li>-->
	 <li><a href="<?php echo url('User/account_deposit');?>" ><?php echo $this->_var['lang']['surplus_type_0']; ?></a></li>
	 <li class="active"><a href="<?php echo url('User/account_raply');?>" ><?php echo $this->_var['lang']['surplus_type_1']; ?></a></li>
	 <li><a href="<?php echo url('User/account_log');?>" ><?php echo $this->_var['lang']['view_application']; ?></a></li>
	 <li><a href="<?php echo url('User/stock_log');?>" >市值奖励</a></li>
  </ul>
  <?php if ($this->_var['raply_info']): ?><div class="raply_info"><?php echo $this->_var['raply_info']; ?></div><?php endif; ?>
<form action="<?php echo url('user/act_account');?>" method="post" name="theForm" >
  <div class="ect-bg-colorf flow-consignee">
    <ul class="o-info">
	  <?php if ($this->_var['frozen_premium']): ?>
	  <li class="ect-radio">
        <input name="raply_type" type="radio" id="raply_type0" checked="checked" value="0" onclick="selectRaply(this)">
        <label for="raply_type0">余额提现<i></i></label>
        &nbsp;
        <input name="raply_type" type="radio" id="raply_type1" value="1"  onclick="selectRaply(this)">
        <label for="raply_type1">入股金提现(¥<?php echo $this->_var['frozen_premium']; ?>元)<i></i></label>
        &nbsp;
		<input name="raply_type" type="radio" id="raply_type2" value="2"  onclick="selectRaply(this)">
        <label for="raply_type2">入股金转余额(¥<?php echo $this->_var['frozen_premium']; ?>元)<i></i></label>
        &nbsp;
      </li>
	  <?php endif; ?>	
      <li>
        <div class="input-text"><b class="pull-left"><?php echo $this->_var['lang']['repay_money']; ?>：</b><span>
          <input name="amount" id="amount" placeholder="填写金额最小不得少于1000元" type="text" class="inputBg_touch" value="" />
          </span></div>
      </li>
     
      <li class="input-text"><b class="pull-left"><?php echo $this->_var['lang']['process_notic']; ?>：</b>
        <textarea name="user_note" placeholder="<?php echo $this->_var['lang']['process_notic']; ?>" type="text"><?php echo htmlspecialchars($this->_var['order']['user_note']); ?></textarea>
      </li>
    </ul>
	
  </div>
  <p style="line-height:2em;">特别说明：<br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;1、需要代扣20%劳务税费/交易规费，咨询热线：0371-55698545。<br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2、冻结入股金需要转入余额请在“备注信息”中注明！</p>
  <div class="two-btn ect-padding-tb ect-padding-lr ect-margin-tb text-center">
  <input type="hidden" name="surplus_type" value="1" />
  <input type="hidden" id="frozen_premium" value="<?php echo $this->_var['frozen_premium']; ?>" />
  <input type="hidden" name="is_wanzheng" value="<?php echo $this->_var['raply_info']; ?>" />
    <input type="submit" name="submit"  class="btn btn-info ect-bg-colory"  value="<?php echo $this->_var['lang']['submit_request']; ?>"/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <input type="reset" name="submit"  class="btn btn-info ect-bg-colory"  value="<?php echo $this->_var['lang']['button_reset']; ?>"/>
	
  </div>
  
</form>
<p class="pull-right count"><?php echo $this->_var['lang']['current_surplus']; ?><b class="ect-colory"><?php echo $this->_var['surplus_amount']; ?></b>（元）</p>
<footer>
    <nav class="ect-nav2"><?php echo $this->fetch('library/page_menu2.lbi'); ?></nav>
  </footer>
  <div style="padding-bottom:7.2em;"></div>
</div>


<?php echo $this->fetch('library/search.lbi'); ?> <?php echo $this->fetch('library/page_footer.lbi'); ?> 
<script type="text/javascript" src="__PUBLIC__/js/region.js"></script> 
<script type="text/javascript" src="__PUBLIC__/js/shopping_flow.js"></script> 
<script type="text/javascript">
	region.isAdmin = false;
	<?php $_from = $this->_var['lang']['flow_js']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'item');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['item']):
?>
	var <?php echo $this->_var['key']; ?> = "<?php echo $this->_var['item']; ?>";
	<?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
	
	onload = function() {
	      if (!document.all)
	      {
	        document.forms['theForm'].reset();
	      }
	}
	
	
	function selectRaply(obj){
		var selectRaply = null;
		if (selectedShipping == obj)
		  {
			return;
		  }
		  else
		  {
			selectedShipping = obj;
		}
		var theForm = obj.form;
		for (i=0; i<theForm.elements.length; i++) {  
			if (theForm[i].checked) {  
				if(theForm[i].value == 0){
					document.getElementById("amount").value="";
					document.getElementById("amount").readOnly=false;
				}else{
					var frozen_premium = document.getElementById("frozen_premium").value;
					document.getElementById("amount").value=frozen_premium;
					document.getElementById("amount").readOnly=true;
				}
			}  
		} 
		
	}
</script>
</body></html>