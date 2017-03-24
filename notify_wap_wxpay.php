<?php
/**
 * 微信支付异步响应操作
 */
define('IN_ECTOUCH', true);
$_GET['code'] = base64_encode(serialize(array('code'=>'wxpay', 'type'=>1, 'postStr'=>$GLOBALS["HTTP_RAW_POST_DATA"])));
define('CONTROLLER_NAME', 'Respond');
/* 加载核心文件 */
require ('mobile/include/EcTouch.php');
?>