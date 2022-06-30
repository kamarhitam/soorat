<?php
define('APP_NAME', 'my-app');
define('DEVELOPMENT_ENVIRONMENT', true);
define('ROOT', realpath(dirname(__FILE__)));
define('DS', DIRECTORY_SEPARATOR);
define('NG', '');
define('APPS', 'apps');
define('ASSETS', 'assets');
define('LIBS', 'libs');
define('INS', 'ins');
define('PLUGINS', 'plugins');
define('SECTION', 'Section');
define('TEMPLATE', 'Template');
define('DIALOG', 'Dialog');
define('CACHE', 'cache');
define('TMP', 'tmp');
define('COOKIES', 'cookies');
define('CURLCOOKIE', 'curlcookie');
define('MODEL', 'Model');
define('VIEW', 'View');
define('CONTROLLER', 'Controller');
define('CONFIG', 'Config');
define('LAYOUT', 'Layout');
define('DATABASE', 'Database');
define('SCRIPT', 'Script');
define('UPLOADS', 'uploads');
define('GALLERY', 'gallery');
define('PROFILE', 'profile');

date_default_timezone_set('Asia/Jakarta');
@ini_set('max_execution_time', 48000);
ini_set('memory_limit', '-1');

include (ROOT . DS . APPS . DS . "Bootstrap.php");
new Bootstrap();
