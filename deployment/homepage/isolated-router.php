<?php
// Emulate the production HTTPS reverse proxy only inside the isolated fixture.
if (getenv('ISIPTO_ISOLATED_AUDIT') !== '1') { http_response_code(404); exit; }
$_SERVER['HTTPS']='on';$_SERVER['SERVER_PORT']=443;$_SERVER['SERVER_NAME']='isipto.ru';$_SERVER['HTTP_HOST']='isipto.ru';
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if($path!=='/' && is_file('/var/www/html'.$path) && substr($path,-4)!=='.php')return false;
if($path!=='/'&&$path!=='/index.php'&&!isset($_GET['q'])){$_GET['q']=ltrim($path,'/');$_REQUEST['q']=$_GET['q'];}
require '/var/www/html/index.php';
