<?php
$type = $_GET["data"];
$type = str_replace('快递','',$type);
$type = str_replace('快运','',$type);
$type = str_replace('速递','',$type);

if ($type == '中国邮政')
{
	$type = 'EMS';
}
$invoice_sn=preg_replace('/\D/s', '', $type);

//print_r(mb_substr($type,0,2,"UTF-8"));
if(mb_substr($type,0,2,"UTF-8")=="圆通"){
	 $url = 'http://m.kuaidi100.com/query?type=yuantong&id=1&postid=' .$invoice_sn. '&temp='.time();
}elseif(mb_substr($type,0,2,"UTF-8")=="申通"){
	 $url = 'http://m.kuaidi100.com/query?type=shentong&id=1&postid=' .$invoice_sn. '&temp='.time();
}elseif(mb_substr($type,0,2,"UTF-8")=="天天"){
	 $url = 'http://m.kuaidi100.com/query?type=tiantian&id=1&postid=' .$invoice_sn. '&temp='.time();
}
//http://m.kuaidi100.com/query?type=yuantong&postid=804430096496
//$url = 'http://apps.yesnap.com/robot/robot.php?op=express&data=' . urlencode($type) .'&dataType=text';
//print_r($url);
//exit();
$powered = ' ';

// 使用 CURL 模式
if (function_exists('curl_init') == 1)
{
	$curl = curl_init();
	curl_setopt ($curl, CURLOPT_URL, $url);
	curl_setopt ($curl, CURLOPT_HEADER,0);
	curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($curl, CURLOPT_USERAGENT,$_SERVER['HTTP_USER_AGENT']);
	curl_setopt ($curl, CURLOPT_TIMEOUT,10);
	$get_content = curl_exec($curl);
	curl_close ($curl);
}else{
	include("snoopy.php");
	$snoopy = new snoopy();
	$snoopy->referer = 'http://www.google.com/';
	$snoopy->fetch($url);
	$get_content = $snoopy->results;
}
$get_content=json_decode($get_content,true);
if($get_content['status']==200){	
	foreach($get_content['data'] as $k=>$v){
		if($k==0){
			$ky.="<li><b><i class=k_time>".$v['time']."</i><i class=k_res>".$v['context']."</i></b></li>";
		}else{
			$ky.="<li><i class=k_time>".$v['time']."</i><i class=k_res>".$v['context']."</i></li>";
		}	
	}
}

print_r($ky);
//print_r($get_content . '<br/>' . $powered);
exit();
?>