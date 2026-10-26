<?php
require '/var/www/html/core/config/config.inc.php';
$p=new PDO('mysql:host='.$database_server.';dbname='.$dbase.';charset=utf8',$database_user,$database_password,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$out=array('checkedUtc'=>gmdate('c'),'records'=>array(),'deliveryEvents'=>array(),'receipts'=>array());
foreach($p->query('SELECT id,form,context_key,date FROM `'.$table_prefix.'formit_forms` ORDER BY id DESC LIMIT 8',PDO::FETCH_ASSOC) as $r){$r['utc']=gmdate('c',(int)$r['date']);$out['records'][]=$r;}
$log='/var/www/html/core/cache/logs/error.log';if(is_readable($log)){
 $h=fopen($log,'rb');fseek($h,max(0,filesize($log)-524288));$tail=stream_get_contents($h);fclose($h);
 foreach(explode("\n",$tail) as $line){if(strpos($line,'[bitrix24]')!==false){preg_match('/\[([^\]]+)\]/',$line,$t);$safe=substr($line,strpos($line,'[bitrix24]'));$out['deliveryEvents'][]=array('timestamp'=>isset($t[1])?$t[1]:null,'event'=>$safe);}}
 $out['deliveryEvents']=array_slice($out['deliveryEvents'],-30);
}
foreach(glob('/var/www/html/core/cache/bitrix24-delivery/*.json') as $f){$r=json_decode(file_get_contents($f),true);$out['receipts'][]=array('modifiedUtc'=>gmdate('c',filemtime($f)),'state'=>$r);}
$out['hookReferences']=array();foreach($p->query('SELECT id,name,snippet,static,static_file FROM `'.$table_prefix.'site_snippets` WHERE name=\'bitrix24_hooks\'',PDO::FETCH_ASSOC) as $s){$out['hookReferences'][]=array('id'=>$s['id'],'static'=>$s['static'],'file'=>$s['static_file'],'cachedContainsReceipt'=>strpos($s['snippet'],'IsiptoBitrix24Receipt')!==false);}
$out['homepage']=$p->query('SELECT id,template FROM `'.$table_prefix.'site_content` WHERE id=3075')->fetch(PDO::FETCH_ASSOC);
$out['testFields']=array();foreach($p->query('SELECT id,`values` FROM `'.$table_prefix.'formit_forms` WHERE id IN (596003,596004)',PDO::FETCH_ASSOC) as $r){
 $v=json_decode($r['values'],true);$f=array('id'=>$r['id'],'readableJson'=>is_array($v));
 if(is_array($v)){
  $f['keys']=array_keys($v);$f['expectedTestName']=in_array(isset($v['Имя'])?$v['Имя']:(isset($v['fio'])?$v['fio']:null),array('Новый тест','Новый тест 2'),true);
  foreach(array('agree','website','firstname','lastname','name','csrf_token','recaptcha_token') as $k)$f[$k]=array('present'=>array_key_exists($k,$v),'empty'=>empty($v[$k]));
 }
 $out['testFields'][]=$f;
}
session_start();$out['recentSessionHooks']=array();foreach($p->query('SELECT access,data FROM `'.$table_prefix.'session` ORDER BY access DESC LIMIT 6',PDO::FETCH_ASSOC) as $r){
 $_SESSION=array();$decoded=@session_decode($r['data']);$configs=array();if($decoded && isset($_SESSION['AjaxForm']))foreach($_SESSION['AjaxForm'] as $v)$configs[]=array('form'=>isset($v['form'])?$v['form']:null,'hooks'=>isset($v['hooks'])?$v['hooks']:null);
 $out['recentSessionHooks'][]=array('access'=>$r['access'],'bitrixConfigs'=>substr_count($r['data'],'FormItSaveForm,bitrix24_hooks'),'roistatConfigs'=>substr_count($r['data'],'FormItSaveForm,roistat_hooks'),'configs'=>$configs);
}$_SESSION=array();session_abort();
$out['template']=$p->query('SELECT id,static,static_file FROM `'.$table_prefix.'site_templates` WHERE id=84')->fetch(PDO::FETCH_ASSOC);
echo json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
