<!-- <?php if ($this->_var['full_page']): ?> -->
<?php echo $this->fetch('pageheader.htm'); ?>
<script type="text/javascript" src="../js/calendar.php?lang=<?php echo $this->_var['cfg_lang']; ?>"></script>
<link href="../js/calendar/calendar.css" rel="stylesheet" type="text/css" />
<?php echo $this->smarty_insert_scripts(array('files'=>'../js/utils.js,listtable.js')); ?>
<div class="form-div">
<?php if ($_GET['auid']): ?>
<?php echo $this->_var['lang']['show_affiliate_orders']; ?>
<?php else: ?>
<form action="user_account_log.php?act=list">
  <?php echo $this->_var['lang']['sch_stats']['info']; ?>
  <a href="user_account_log.php?act=list">全部</a>
  <a href="user_account_log.php?act=list&ky=0">资金</a>
  <a href="user_account_log.php?act=list&ky=1">等级积分</a>
  <a href="user_account_log.php?act=list&ky=2">银币</a>
  <a href="user_account_log.php?act=list&ky=3">冻结入股金</a>
<?php echo $this->_var['lang']['sch_order']; ?>
<?php echo $this->_var['lang']['start_date']; ?>&nbsp;
    <input name="start_date" type="text" id="start_date" size="15" value='<?php echo $this->_var['start_date']; ?>' readonly="readonly" /><input name="selbtn1" type="button" id="selbtn1" onclick="return showCalendar('start_date', '%Y-%m-%d', false, false, 'selbtn1');" value="<?php echo $this->_var['lang']['btn_select']; ?>" class="button_new"/>&nbsp;&nbsp;
    <?php echo $this->_var['lang']['end_date']; ?>&nbsp;
    <input name="end_date" type="text" id="end_date" size="15" value='<?php echo $this->_var['end_date']; ?>' readonly="readonly" /><input name="selbtn2" type="button" id="selbtn2" onclick="return showCalendar('end_date', '%Y-%m-%d', false, false, 'selbtn2');" value="<?php echo $this->_var['lang']['btn_select']; ?>" class="button_new"/>&nbsp;&nbsp;
	<input type="submit" name="submit" value="查询" class="button_new" />
<input type="hidden" name="act" value="list" />
<input name="user_id" type="text" id="user_id" size="15"><input type="submit" value="<?php echo $this->_var['lang']['button_search']; ?>" class="button_new" />
</form>
<?php endif; ?>
</div>
<form method="post" action="" name="listForm">
<div class="list-div" id="listDiv">
<!-- <?php endif; ?> -->
<table cellspacing='1' cellpadding='3'>
	<tr>
      <th colspan="2" style="background:#999">转账时间</th>
	  <th colspan="4" style="background:#999">变更人信息</th>
	  <th colspan="3" style="background:#999">资金变动</th>	  
      <th colspan="3" style="background:#999">其他变动</th>
	  <th colspan="2" style="background:#999">备注</th>
    </tr>
<tr>
  <th width="3%">编号</th>
  <th width="10%">时间</th>
  
  <th width="4%">用户ID</th>
  <th width="4%">真实姓名</th>
  <th width="7%">电话</th>
  <th width="5%">用户等级</th>

  <th width="5%">自有余额</th>
  <th width="5%">分红余额</th>
  <th width="5%">总额</th>
  
  <th width="4%">等级积分</th>
  <th width="4%">银币</th>
  <th width="4%">冻结入股金</th>
  <th width="4%">类型</th>
  <th width="12%">备注</th>
</tr>
<!-- <?php $_from = $this->_var['logdb']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'val');if (count($_from)):
    foreach ($_from AS $this->_var['val']):
?> -->
<tr>
  <td align="center"><?php echo $this->_var['val']['log_id']; ?></td>
  <td align="center"><?php echo $this->_var['val']['change_time']; ?></td>
  
  <td align="center"><?php echo $this->_var['val']['user_id']; ?></td>
  <td align="center"><?php echo $this->_var['val']['tname']; ?></td>
  <td align="center"><?php echo $this->_var['val']['mobile_phone']; ?></td>
  <td align="center"><?php echo $this->_var['val']['rankname']; ?></td>

  <td align="center"><?php echo $this->_var['val']['own_money']; ?></td>
  <td align="center"><?php echo $this->_var['val']['divi_money']; ?></td>
  <td align="center"><font color='red'><?php echo $this->_var['val']['user_money']; ?></font></td>
  
  <td align="center"><?php echo $this->_var['val']['rank_points']; ?></td>
  <td align="center"><?php echo $this->_var['val']['pay_points']; ?></td>
  <td align="center"><?php echo $this->_var['val']['frozen_premium']; ?></td>
  <td align="center"><?php echo $this->_var['lang']['change_type'][$this->_var['val']['change_type']]; ?></td> 
  
  <td align="center"><?php echo $this->_var['val']['change_desc']; ?></td>

</tr>
    <!-- <?php endforeach; else: ?> -->
    <tr><td class="no-records" colspan="10"><?php echo $this->_var['lang']['no_records']; ?></td></tr>
<!-- <?php endif; unset($_from); ?><?php $this->pop_vars();; ?> -->
</table>
  <table cellpadding="4" cellspacing="0">
    <tr>
      <td align="right"><?php echo $this->fetch('page.htm'); ?></td>
    </tr>
  </table>
<!-- <?php if ($this->_var['full_page']): ?> -->
</div>
</form>
<script type="Text/Javascript" language="JavaScript">
listTable.recordCount = <?php echo $this->_var['record_count']; ?>;
listTable.pageCount = <?php echo $this->_var['page_count']; ?>;

<?php $_from = $this->_var['filter']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'item');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['item']):
?>
listTable.filter.<?php echo $this->_var['key']; ?> = '<?php echo $this->_var['item']; ?>';
<?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>

<!--  -->
onload = function()
{
  // 开始检查订单
  startCheckOrder();
  getDownUrl();
}
function getList()
{
    var frm =  document.forms['TimeInterval'];
    listTable.filter['start_date'] = frm.elements['start_date'].value;
    listTable.filter['end_date'] = frm.elements['end_date'].value;
    listTable.filter['page'] = 1;
    listTable.loadList();
    //getDownUrl();
}
function getDownUrl()
{
  var aTags = document.getElementsByTagName('A');
  for (var i = 0; i < aTags.length; i++)
  { 
    if (aTags[i].href.indexOf('download') >= 0)
    {
      if (listTable.filter['start_date'] == "")
      {
        var frm =  document.forms['TimeInterval'];
        listTable.filter['start_date'] = frm.elements['start_date'].value;
        listTable.filter['end_date'] = frm.elements['end_date'].value;
      }
      aTags[i].href = "user_account_log.php?act=download&start_date=" + listTable.filter['start_date'] + "&end_date=" + listTable.filter['end_date'];
    }
  }
}
<!--  -->
</script>
<?php echo $this->fetch('pagefooter.htm'); ?>
<!-- <?php endif; ?> -->