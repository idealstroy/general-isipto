<?php
if(PHP_SAPI!=='cli')exit(2);require '/var/www/html/core/config/config.inc.php';if($database_server!=='db')exit(2);
$p=new PDO('mysql:host='.$database_server.';dbname='.$dbase.';charset=utf8',$database_user,$database_password,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));$out=array();foreach(array('site_templates','site_htmlsnippets','site_snippets') as $t)$out[$t]=$p->query('SELECT * FROM `'.$table_prefix.$t.'`',PDO::FETCH_ASSOC)->fetchAll();echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
