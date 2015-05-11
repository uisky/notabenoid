<?php

// This is the configuration for yiic console application.
// Any writable CConsoleApplication properties can be configured here.
return array(
	'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
	'name'=>'Notabenoid.Org',
	"language" => "ru",
	"sourceLanguage" => "en",

	"import"=>array(
		"application.models.*",
		"application.components.*",
		"ext.yii-mail.YiiMailMessage",
	),

	'components'=>array(
		"db" => array(
			"connectionString" => "pgsql:host=localhost;dbname=notabenoid",
			"username" => "notabenoid",
			"password" => "",
			"charset" => "utf8",

			"emulatePrepare" => true,
			"schemaCachingDuration" => 60 * 30,
			"enableProfiling" => true,
		),
		"mail" => array(
			"class" => "ext.yii-mail.YiiMail",
			"transportType" => "php",
			"viewPath" => "application.views.email",
			"logging" => false,
			"dryRun" => false,
		),
		"langs" => array(
			"class" => "application.components.Langs",
		),
		"parser" => array(
			"class" => "application.components.Parser",
		),
	),

	'params' => [
		'domain' => 'notabenoid.org',
		'adminEmail' => 'support@notabenoid.org',
		"commentEmail" => "comment@notabenoid.org",
		"systemEmail" => "no-reply@notabenoid.org",
	],
);