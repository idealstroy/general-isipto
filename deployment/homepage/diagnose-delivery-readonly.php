<?php
if (PHP_SAPI !== 'cli') exit(2);
$root='/home/i/idealstroy/isipto.ru/public_html';
require $root.'/core/config/config.inc.php';
$pdo=new PDO('mysql:host='.$database_server.';dbname='.$dbase.';charset=utf8',$database_user,$database_password,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$out=array('checkedAtUtc'=>gmdate('c'),'php'=>PHP_VERSION,'allow_url_fopen'=>(bool)ini_get('allow_url_fopen'));
$out['records']=$pdo->query('SELECT id,form,context_key,date FROM `'.$table_prefix.'formit_forms` ORDER BY id DESC LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
foreach($out['records'] as &$r) $r['dateUtc']=gmdate('c',(int)$r['date']);
unset($r);
$src=file_get_contents($root.'/assets/snippets/roistat_hooks.php');
$out['hook']=array('sha256'=>hash('sha256',$src),'usesRoistat'=>strpos($src,'cloud.roistat.com')!==false,'checksJsonResponse'=>strpos($src,'json_decode')!==false,'hasPhoneSequenceCheck'=>preg_match('/12345|preg_match.{0,100}phone/s',$src)===1);
$out['errorLog']=array();
foreach(array('core/cache/logs/error.log','error_log') as $rel){
 $f=$root.'/'.$rel;if(!is_readable($f))continue;
 $h=fopen($f,'rb');fseek($h,max(0,filesize($f)-262144));$tail=stream_get_contents($h);fclose($h);
 $matches=array();foreach(explode("\n",$tail) as $line){
  if(!preg_match('/roistat|leads\/add|file_get_contents|AjaxForm|FormIt/i',$line))continue;
  preg_match('/\[([^\]]+)\]/',$line,$stamp);
  $matches[]=array('timestamp'=>isset($stamp[1])?$stamp[1]:null,'roistat'=>stripos($line,'roistat')!==false,'networkFailure'=>preg_match('/failed|SSL|timed out|HTTP request failed|Forbidden|Unauthorized|resolve|Connection/i',$line)===1,'formit'=>stripos($line,'FormIt')!==false);
 }
 $out['errorLog'][$rel]=array('modifiedUtc'=>gmdate('c',filemtime($f)),'matchingEntries'=>array_slice($matches,-25));
}
$errno=0;$error='';$sock=@stream_socket_client('ssl://cloud.roistat.com:443',$errno,$error,10);
$out['roistatTlsReachable']=(bool)$sock;if($sock)fclose($sock);
echo json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
