<?php
$time = microtime();
require_once("sys/config.php");
c()->getTimer()->setBeginTime($time); //修正启动时间
unset($time); //删除时间变量

cfg()->load('config/all.php'); //加载其他配置文件
lib()->load('hook')->add('Hook', new \ULib\Hook())->add(); //加载自定义类
u()->home(array('Home'), array(
	'Error',
	'page_404'
)); //开始加载默认页面