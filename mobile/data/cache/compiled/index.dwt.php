<?php echo $this->fetch('library/page_header.lbi'); ?>
<link rel="stylesheet" href="__TPL__/css/my.css">
<div class="con"> 
  <header class="ect-header">
  	<a href="__ROOT__/" class="pull-left ect-icon ect-icon-logo"></a>
	<div class="ect-header-div">
		<a href="<?php echo url('category/top_all');?>" class="pull-right ect-icon ect-icon-cate1"></a>
		<div class="ect-btn-search-div pull-right">
			<button class="btn btn-default ect-text-left ect-btn-search" onClick="javascript:openSearch();"><i class="fa fa-search"></i>&nbsp;<?php echo $this->_var['lang']['no_keywords']; ?></button>
		</div>		
	</div>   
  </header>
    
  <div id="focus" class="focus">
    <div class="hd">
      <ul></ul>
    </div>
    <div class="bd">
      <?php 
$k = array (
  'name' => 'ads',
  'id' => '1',
  'num' => '9',
);
echo $this->_echash . $k['name'] . '|' . serialize($k) . $this->_echash;
?>
    </div>
  </div>  
    
 <div>  <nav class="container-fluid">
    <ul class="row ect-row-nav">
      <?php $_from = $this->_var['navigator']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'nav');if (count($_from)):
    foreach ($_from AS $this->_var['nav']):
?> 
      <a href="<?php echo $this->_var['nav']['url']; ?>">
      <li class="col-sm-3 col-xs-3"><i><img src="<?php echo $this->_var['nav']['pic']; ?>" ></i>
        <p class="text-center"><?php echo $this->_var['nav']['name']; ?></p>
      </li>
      </a> 
      <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
    </ul>
  </nav></div> 
 <!--<div align="center" class="red">公告：代金券本期50天3倍，预计下期50天2倍！</div>-->
  <!--<div class="protitle"></div>-->
  <?php echo $this->fetch('library/recommend_hot.lbi'); ?>
  <!--<div id="index_con">
    <p style="text-align:center;">
   <img src="__TPL__/images/indl/W06.png" /></p>
	</div>-->
  <footer>
    <nav class="ect-nav2"><?php echo $this->fetch('library/page_menu2.lbi'); ?></nav>
  </footer>
  <div align="center" class="red">平台访问：<?php echo $this->_var['click_number']; ?>人次</div>
  <div style="padding-bottom:5.5em;"></div>
</div>
<?php echo $this->fetch('library/search.lbi'); ?>
<?php echo $this->fetch('library/page_footer.lbi'); ?> 
<script type="text/javascript">
get_asynclist("<?php echo url('index/ajax_goods', array('type'=>'best'));?>" , '__TPL__/images/loader.gif');
</script>
