<?php
require '/var/www/html/core/config/config.inc.php';
$p=new PDO('mysql:host='.$database_server.';dbname='.$dbase.';charset=utf8',$database_user,$database_password,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
foreach(array('site_plugins'=>'plugincode','site_snippets'=>'snippet','site_htmlsnippets'=>'snippet') as $t=>$col){
 foreach($p->query('SELECT name,`'.$col.'` AS code FROM `'.$table_prefix.$t.'`',PDO::FETCH_ASSOC) as $r){
  if(!preg_match('/csrf_token|recaptcha_token|anti_bot_cookie|af_complete/',$r['code']))continue;
  $s=preg_replace('~https?://[^\s\x27"<>]+~','[URL_REDACTED]',$r['code']);
  $lines=array();foreach(explode("\n",$s) as $line)if(!preg_match('/secret|password|token\s*=\s*[\x27"]/i',$line)&&preg_match('/csrf|SESSION|recaptcha|anti_bot|af_complete|spasibo|response.success|response\.success|agree/i',$line))$lines[]=substr($line,0,600);
  echo json_encode(array('type'=>$t,'name'=>$r['name'],'securityLines'=>$lines),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
 }
}
