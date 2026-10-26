<?php
if(PHP_SAPI!=='cli'||!isset($backup))exit(2);
require '/var/www/html/core/config/config.inc.php';if($database_server!=='db')exit(2);
$p=new PDO('mysql:host='.$database_server.';dbname='.$dbase.';charset=utf8',$database_user,$database_password,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$allowed=array('site_templates'=>true,'site_htmlsnippets'=>true,'site_content'=>true,'site_snippets'=>true);
$restore=$backup['objects'];foreach($backup['legacySnippets'] as $row)$restore[]=array('table'=>'site_snippets','row'=>$row,'field'=>'snippet');
$p->beginTransaction();
try{foreach($restore as $e){if(!isset($allowed[$e['table']]))throw new RuntimeException('Unexpected table');$r=$e['row'];$fields=array($e['field']);if($e['table']==='site_snippets')$fields=array('snippet','static','static_file','description');$sets=array();$values=array();foreach($fields as $f){$sets[]='`'.$f.'`=?';$values[]=$r[$f];}$values[]=$r['id'];$q=$p->prepare('UPDATE `'.$table_prefix.$e['table'].'` SET '.implode(',',$sets).' WHERE id=?');$q->execute($values);}$p->commit();}catch(Exception $e){$p->rollBack();throw $e;}
require '/var/www/html/config.core.php';require MODX_CORE_PATH.'model/modx/modx.class.php';$modx=new modX();$modx->initialize('web');$modx->cacheManager->refresh(array('resource'=>array('contexts'=>array('web')),'db'=>array(),'scripts'=>array()));echo json_encode(array('restoredObjects'=>count($restore),'localOnly'=>true))."\n";
