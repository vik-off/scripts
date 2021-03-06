<?php

if(!defined('FS_ROOT'))
	die('access denided (config file)');


// установить уровень сообщений об ошибках (максимальный)
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

// установить текущий часовой пояс
date_default_timezone_set('Europe/Kiev');

// задать кодировку по умолчанию для mb_* функций
mb_internal_encoding("utf8");

$config = array(

	// название сайта
	'site_name' => 'log analyzer',

	// отсеивать дублируемые формы
	'check_form_duplication' => 0,

	// конфигурация подключения к БД
	'db' => array(
		'adapter' => 'PdoMysql',
		'host' => 'localhost',
		'user' => 'root',
		'pass' => '',
		'database' => 'log_analyzer',
		'encoding' => 'utf8',
		'fileLog' => FALSE,
		'profiling' => FALSE,
	),

	// отображать или нет детальные данные об исключениях
	'show_exceptions' => true,
);

return $config;


