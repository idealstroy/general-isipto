<?php
require '/var/www/html/core/config/config.inc.php';
$p=new PDO('mysql:host='.$database_server.';dbname='.$dbase.';charset=utf8',$database_user,$database_password,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$out=array();foreach(array('footer_new','form_tpl1','form_footer') as $n){$s=$p->prepare('SELECT * FROM `'.$table_prefix.'site_htmlsnippets` WHERE name=?');$s->execute(array($n));$out[$n]=$s->fetch(PDO::FETCH_ASSOC);}
$s=$p->query('SELECT * FROM `'.$table_prefix.'site_snippets` WHERE name=\'bitrix24_hooks\'');$out['existingBitrixSnippet']=$s->fetch(PDO::FETCH_ASSOC);
echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
