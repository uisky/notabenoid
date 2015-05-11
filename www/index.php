<?php
function is_developer() {
	return $_SERVER["SERVER_NAME"] == "notabenoid.dev.romakhin.ru";
}

function prr($obj, $title = '') {
	echo "\n<pre>" . ($title != '' ? "<b>{$title}</b>\n" : "") . htmlspecialchars(print_r($obj, true)) . "</pre>\n";
}

function p() {
	return Yii::app()->params;
}

$yii = dirname(__FILE__).'/../yii/framework/yii.php';
$config = dirname(__FILE__) . '/../protected/config/' . (is_developer() ? "dev.php" : "main.php");

if(is_developer()) {
	defined('YII_DEBUG') or define('YII_DEBUG', true);
	defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);
}

require_once($yii);
Yii::createWebApplication($config)->run();
