<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
		<title>刮刮卡</title>
		<link href="<?php echo __ADDONS__;?>/wechat/ggk/view/css/activity-style.css" rel="stylesheet" type="text/css">
	</head>

	<body data-role="page" class="activity-scratch-card-winning">
		<script src="<?php echo __PUBLIC__;?>/js/jquery.min.js"></script>
		<script src="<?php echo __ADDONS__;?>/wechat/ggk/view/js/wScratchPad.js" type="text/javascript"></script>
		<div class="main">
			<div class="cover">
				<img src="<?php echo __ADDONS__;?>/wechat/ggk/view/images/activity-scratch-card-bannerbg.png">
				<div id="prize">我要刮奖</div>
				<div id="scratchpad"></div>
			</div>
			<div class="content">
				<div class="boxcontent boxwhite">
					<div class="box">
						<div class="title-brown">奖项设置</div>
						<div class="Detail">
							<?php
                            if($config['prize']){
                                foreach($config['prize'] as $k=>$v){
	                        ?>
	                        <p><?php echo $v['prize_level'];?>：<?php echo $v['prize_name'];?>，共<span class="total"><?php echo $v['prize_count'];?></span>份。</p>
	                        <?php
	                            }
	                        }
	                        ?>
						</div>
					</div>
				</div>
				<div class="boxcontent boxwhite">
					<div class="box">
						<div class="title-brown">活动说明</div>
						<div class="Detail">
							<p>剩余抽奖次数：<span id="num"><?php echo $config['free_prize_num'];?></span></p>
							<p><?php echo $config['description'];?></p>
						</div>
					</div>
				</div>
				<div class="boxcontent boxwhite">
					<div class="box">
						<div class="title-brown">中奖记录</div>
						<div class="Detail">
							<?php
			                	if(!empty($list)){
			                    	foreach($list as $key=>$val){
			                ?>
			                    	<p><?php echo $val['nickname'];?> 获得奖品 ：<?php echo $val['prize_name'];?></p>
			                <?php
			                    	}
			                	}
			                	else{
			                ?>
			                	<p>暂无获奖记录</p>
			                <?php
			                	}
			                ?>
						</div>
					</div>
				</div>
			</div>
			<div style="clear:both;">
			</div>
		</div>
		<script type="text/javascript">
			$(function() {
				var ISWeixin = !!navigator.userAgent.match(/MicroMessenger/i); //wp手机无法判断
		        if(!ISWeixin){
		            var rd_url = location.href.split('#')[0];  // remove hash
		            var oauth_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri='+encodeURIComponent(rd_url) + '&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect';
		            location.href = oauth_url;
		            return false;
		        }
				var isLucky = false, level = "谢谢参与";
				
				$.getJSON('<?php echo url('wechat/plugin_action', array('name'=>'ggk'))?>', {act: 'draw'}, function(result){
					//alert(result);
					var str = JSON.stringify(result);
					//alert(str);
					if(result.status == 2){
						$("#scratchpad").wScratchPad('enabled');
						alert(result.msg);
						return false;
					}					
					else if(result.status == 3){						
						if(confirm(result.msg)){			 
							$.getJSON('<?php echo url('wechat/plugin_action', array('name'=>'ggk'))?>', {ky: 'zf',act: 'draw'}, function(result2){								
								if(result2.status == 2){
									$("#scratchpad").wScratchPad('enabled');
									alert(result2.msg);
									return false;
								}
								else if(result2.status == 1){
									isLucky = true;
									level = result2.level;
									$("#prize").html(level);
									
								}else{
									$("#prize").html("谢谢参与");
								}
								var yb = "yinbi";
								guaka(result2,yb);
							});				 
						}else{
						  //$("#prize").html("谢谢参与");						  
						  return false;			 
						} 
					}
					else if(result.status == 1){
						isLucky = true;
						level = result.level;
						$("#prize").html(level);
						guaka(result);
					}else{
						$("#prize").html("谢谢参与");
						guaka(result);
					}
					//alert(str);
					//alert("abc");
					
					
					 
				});
			});
			function guaka(result,yb){								
				$("#scratchpad").wScratchPad({
					width: 150,
					height: 40,
					color: "#a9a9a7",  //覆盖的刮刮层的颜色
					scratchDown: function(e, percent) {						
						$(this.canvas).css('margin-right', $(this.canvas).css('margin-right') == "0px" ? "1px" : "0px");
						if(percent > 23){
							$("#scratchpad").wScratchPad('clear');						
							if(yb){
								var url = '<?php echo url('wechat/plugin_action', array('name'=>'ggk','yb'=>'yinbi'))?>'
							}else{
								var url = '<?php echo url('wechat/plugin_action', array('name'=>'ggk'))?>'
							}
							
							$.getJSON(url, {act:'do', prize_type:result.prize_type, prize_name:result.msg, prize_level:result.level, type:result.type}, function(data){
								
								
								if(data.status == 1){
									var msg = "恭喜中了" + result.level + "\r\n" + "快去领奖吧";
									if(data.link && confirm(msg)){
										location.href = data.link;
										return false;
									}
									else{
										location.reload();
										return false;
									}
								}else if(data.status == 4){
									var msg = "恭喜中了" + result.level + result.msg + "，已发放至您的账户";
									alert(msg);
									location.reload();
									return false;
								}
								else if(data.status == 0){
									if(confirm(result.msg + "\r\n再来一次")){
										location.reload();
										return false;
									}
								}
							});
						}
					}
				});
			}
		</script>
		
	</body>

</html>