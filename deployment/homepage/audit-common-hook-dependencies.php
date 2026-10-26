<?php
if(PHP_SAPI!=='cli')exit(2);
require '/var/www/html/core/config/config.inc.php';if($database_server!=='db')exit(2);
$p=new PDO('mysql:host='.$database_server.';dbname='.$dbase.';charset=utf8',$database_user,$database_password,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$scan=array('property_set'=>array('id','name','properties'),'site_templates'=>array('id','templatename','properties'),'site_htmlsnippets'=>array('id','name','properties'),'site_plugins'=>array('id','name','plugincode'),'site_snippets'=>array('id','name','snippet'),'system_settings'=>array('key','key','value'),'context_setting'=>array('key','key','value'));
$found=array();foreach($scan as $table=>$keys){if(!$p->query('SHOW TABLES LIKE '.$p->quote($table_prefix.$table))->fetchColumn())continue;foreach($p->query('SELECT * FROM `'.$table_prefix.$table.'`',PDO::FETCH_ASSOC) as $r){if(preg_match('/(?<![A-Za-z0-9_])roistat_hooks(?![A-Za-z0-9_])/',$r[$keys[2]]))$found[]=array('table'=>$table,'id'=>$r[$keys[0]],'name'=>$r[$keys[1]]);}}
echo json_encode(array('additionalDependencies'=>$found),JSON_UNESCAPED_UNICODE)."\n";
