<?php echo $this->fetch('library/user_header.lbi'); ?>
<ul class="nav nav-tabs" role="tablist">
    <li><a href="<?php echo url('User/account_detail');?>" ><?php echo $this->_var['lang']['add_surplus_log']; ?></a></li>	
	 <!--<li><a href="<?php echo url('User/account_detail3');?>" ><?php echo $this->_var['lang']['add_sliv_log']; ?></a></li>-->
	 <!--<li><a href="<?php echo url('User/account_detail2');?>" >消费余额</a></li>-->
	 <li><a href="<?php echo url('User/account_deposit');?>" ><?php echo $this->_var['lang']['surplus_type_0']; ?></a></li>
	 <li><a href="<?php echo url('User/account_raply');?>" ><?php echo $this->_var['lang']['surplus_type_1']; ?></a></li>
	 <li class="active"><a href="<?php echo url('User/account_log');?>" ><?php echo $this->_var['lang']['view_application']; ?></a></li>
	 <li><a href="<?php echo url('User/stock_log');?>" >市值奖励</a></li>
  </ul>
 <div class="user-account-detail">
  	<ul class=" ect-bg-colorf">
     <?php $_from = $this->_var['account_log']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'item');if (count($_from)):
    foreach ($_from AS $this->_var['item']):
?>
    	<li>
        	<p class="title"><span class="pull-left"><?php echo $this->_var['item']['add_time']; ?></span> <span class="pull-right"><?php echo $this->_var['item']['amount']; ?></span></p>
            <p class="content"><span class="remark pull-left"><?php echo $this->_var['lang']['process_notic']; ?>:<?php echo $this->_var['item']['user_note']; ?></span> <span class="pull-right text-right type"><?php echo $this->_var['item']['type']; ?></span>
			
			</p>
			<p class="content" style="height:7em;"><span class="remark pull-left" style="right:5em;"><?php echo $this->_var['lang']['admin_notic']; ?>:<?php if ($this->_var['item']['admin_note']): ?><?php echo $this->_var['item']['admin_note']; ?><?php else: ?>N/A<?php endif; ?></span> <span class="pull-right text-right type"><?php echo $this->_var['lang']['is_paid']; ?>:<?php echo $this->_var['item']['pay_status']; ?></p>
			<p class="content"><?php echo $this->_var['lang']['handle']; ?>：<?php echo $this->_var['item']['handle']; ?>&nbsp;&nbsp;<?php if (( $this->_var['item']['is_paid'] == 0 && $this->_var['item']['process_type'] == 1 ) || $this->_var['item']['handle']): ?>
			<a href="<?php echo url('user/cancel',array('id'=>$this->_var['item']['id']));?>" onclick="if (!confirm('<?php echo $this->_var['lang']['confirm_remove_account']; ?>')) return false;" class="btn btn-default"><?php echo $this->_var['lang']['is_cancel']; ?></a>
              <?php endif; ?>
			  </p>
        </li>
<?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
    </ul>
    <p class="pull-right count" style="background-color: bisque;"><?php echo $this->_var['lang']['current_surplus']; ?><b class="ect-colory"><?php echo $this->_var['surplus_amount']; ?></b></p>
  </div>
    <?php echo $this->fetch('library/page.lbi'); ?>
	<footer>
    <nav class="ect-nav2"><?php echo $this->fetch('library/page_menu2.lbi'); ?></nav>
  </footer>
  <div style="padding-bottom:7.2em;"></div>
</div>
<?php echo $this->fetch('library/search.lbi'); ?>
<?php echo $this->fetch('library/page_footer.lbi'); ?>
</body>
</html>