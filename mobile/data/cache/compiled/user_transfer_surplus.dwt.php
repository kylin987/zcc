<?php echo $this->fetch('library/user_header.lbi'); ?>

  
 <div class="user-transfer-coin">	
    <p class="pull-left count kpd"><?php echo $this->_var['lang']['current_surplus']; ?><b class="ect-colory"><?php echo $this->_var['surplus_amount']; ?></b>（元）</p>
	
    <form name="formEdit" action="<?php echo url('user/transfer_surplus');?>" method="post" onSubmit="return allcheck()">  
  <section class="flow-consignee ect-bg-colorf">
    <ul>	 
	  <li>
        <div class="input-text"><b class="pull-left">手机号：</b><span>
          <input name="user_phone" type="text" id="user_phone" placeholder="请输入对方的手机号"  value="">
          </span><input name="" type="button" id="" value="检测账号" class="checkphone ect-bg" onClick="checkphone()">
        </div>
      </li>	
      <li id="user_tname_note" style="display:none;"></li>
	  <li>
        <div class="input-text"><b class="pull-left">转账金额：</b><span>
          <input name="surplus_number" type="text" id="surplus_number" placeholder="请输入转给对方的金额"  value="">
          </span></div>
      </li>
      
    </ul>
  </section>
  <input name="act" type="hidden" value="surplus" />
  <div class="two-btn ect-padding-tb ect-padding-lr ect-margin-tb text-center">
    <input name="submit" type="submit" value="提交转账" class="btn btn-info ect-bg-colory" />
  </div>
</form>
  </div>
  <footer>
    <nav class="ect-nav2"><?php echo $this->fetch('library/page_menu2.lbi'); ?></nav>
  </footer>
  <div style="padding-bottom:4.2em;"></div>  
</div>
<?php echo $this->fetch('library/search.lbi'); ?>
<?php echo $this->fetch('library/page_footer.lbi'); ?>
<script type="text/javascript">
	function checkphone()
	{	
	  var user_phone = document.getElementById("user_phone").value;	  
	  if(user_phone.length == 0){
	  	alert("请输入对方手机号");
		return; 
	  }
	  $.get('index.php?m=default&c=user&a=select_username', {user_phone:user_phone}, function(data){		
		document.getElementById("user_tname_note").innerHTML = '<span class='+ data.error + '>'+data.content+'</span>';
		document.getElementById("user_tname_note").style.display="block";
	  }, 'json');
	}
	function allcheck(){
		var user_name = document.getElementById("user_name").value;
		var surplus_number = document.getElementById("surplus_number").value;
		var re = /^\d+(\.\d+)?$/;
		if(user_name.length == 0 || surplus_number.length == 0 || !re.test(surplus_number)){
			alert("参数错误，请重新输入");
			return false;
		}
	}
</script>
</body>
</html>