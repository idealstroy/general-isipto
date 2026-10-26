<?php
if(PHP_SAPI!=='cli')exit(2);
require '/var/www/html/core/config/config.inc.php';if($database_server!=='db')exit(2);
$p=new PDO('mysql:host='.$database_server.';dbname='.$dbase.';charset=utf8',$database_user,$database_password,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$out=array('createdUtc'=>gmdate('c'),'objects'=>array(),'formCompatibility'=>array());$formNames=array();
$pattern='/(?<![A-Za-z0-9_])roistat_hooks(?![A-Za-z0-9_])/';
foreach(array('site_templates'=>array('templatename','content'),'site_htmlsnippets'=>array('name','snippet'),'site_content'=>array('pagetitle','content'),'site_propertysets'=>array('name','properties')) as $table=>$keys){
 $exists=$p->query('SHOW TABLES LIKE '.$p->quote($table_prefix.$table))->fetchColumn();if(!$exists)continue;
 foreach($p->query('SELECT * FROM `'.$table_prefix.$table.'`',PDO::FETCH_ASSOC) as $r){$raw=$r[$keys[1]];$runtime=$raw;if(!empty($r['static'])&&!empty($r['static_file'])&&is_file('/var/www/html/'.$r['static_file']))$runtime=file_get_contents('/var/www/html/'.$r['static_file']);
  if(!preg_match($pattern,$runtime)&&!preg_match($pattern,$raw))continue;
  $out['objects'][]=array('table'=>$table,'field'=>$keys[1],'row'=>$r,'runtime'=>$runtime);
  preg_match_all('/\[\[!?AjaxForm\?(?:`[^`]*`|[^`])*?\]\]/',$runtime,$calls);foreach($calls[0] as $call)if(preg_match($pattern,$call)&&preg_match('/&form=`([^`]*)`/',$call,$m))$formNames[$m[1]]=true;
 }
}
foreach(array_keys($formNames) as $name){$q=$p->prepare('SELECT * FROM `'.$table_prefix.'site_htmlsnippets` WHERE name=?');$q->execute(array($name));$r=$q->fetch(PDO::FETCH_ASSOC);if(!$r)throw new RuntimeException('Missing form chunk');$out['formCompatibility'][$name]=array('hasAgree'=>preg_match('/name=[\x27"]agree[\x27"]/',$r['snippet'])===1,'hasName'=>preg_match('/name=[\x27"]fio[\x27"]/',$r['snippet'])===1);
 if(!$out['formCompatibility'][$name]['hasAgree'])$out['objects'][]=array('table'=>'site_htmlsnippets','field'=>'snippet','row'=>$r,'runtime'=>$r['snippet'],'addConsent'=>true);
}
$out['legacySnippets']=$p->query('SELECT * FROM `'.$table_prefix.'site_snippets` WHERE name IN (\'roistat_hooks\',\'Копия roistat_hooks\')')->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
