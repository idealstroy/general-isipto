<?php
if(PHP_SAPI!=='cli')exit(2);
require '/var/www/html/core/config/config.inc.php';
$p=new PDO('mysql:host='.$database_server.';dbname='.$dbase.';charset=utf8',$database_user,$database_password,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$out=array('handlers'=>array(),'references'=>array(),'validationProfiles'=>array());
foreach($p->query('SELECT id,name,snippet,static,static_file FROM `'.$table_prefix.'site_snippets`',PDO::FETCH_ASSOC) as $r){
 if(!preg_match('/roistat_hooks/',$r['name']))continue;$code=$r['static']&&is_file('/var/www/html/'.$r['static_file'])?file_get_contents('/var/www/html/'.$r['static_file']):$r['snippet'];
 $fields=array();foreach(array('CATEGORY_ID','ASSIGNED_BY_ID') as $k)if(preg_match('/[\x27"]'.$k.'[\x27"]\s*=>\s*[\x27"]?([0-9]+)/',$code,$m))$fields[$k]=$m[1];
 $out['handlers'][]=array('id'=>$r['id'],'name'=>$r['name'],'file'=>$r['static_file'],'routing'=>$fields);
}
foreach(array('site_templates'=>array('id','templatename','content'),'site_htmlsnippets'=>array('id','name','snippet'),'site_content'=>array('id','pagetitle','content'),'site_snippets'=>array('id','name','snippet'),'site_plugins'=>array('id','name','plugincode')) as $table=>$keys){
 foreach($p->query('SELECT * FROM `'.$table_prefix.$table.'`',PDO::FETCH_ASSOC) as $r){
  $s=$r[$keys[2]];if(!empty($r['static'])&&!empty($r['static_file'])&&is_file('/var/www/html/'.$r['static_file']))$s=file_get_contents('/var/www/html/'.$r['static_file']);
  preg_match_all('/(?<![A-Za-z0-9_])roistat_hooks(?:_[A-Za-z0-9]+)?(?![A-Za-z0-9_])/',$s,$hits);
  if($hits[0])$out['references'][]=array('table'=>$table,'id'=>$r['id'],'name'=>$r[$keys[1]],'handlers'=>array_count_values($hits[0]),'staticFile'=>isset($r['static_file'])?$r['static_file']:null);
  if($table==='site_templates'||$table==='site_htmlsnippets'){
   preg_match_all('/\[\[!?AjaxForm\?(?:`[^`]*`|[^`])*?\]\]/',$s,$calls);
   foreach($calls[0] as $call){if(!preg_match('/&hooks=`[^`]*(?<![A-Za-z0-9_])roistat_hooks(?![A-Za-z0-9_])[^`]*`/',$call))continue;$config=array();foreach(array('form','validate','fieldNames','submitVar') as $k){preg_match('/&'.$k.'=`([^`]*)`/',$call,$m);$config[$k]=isset($m[1])?$m[1]:null;}$key=json_encode($config);if(!isset($out['validationProfiles'][$key]))$out['validationProfiles'][$key]=array('config'=>$config,'uses'=>0);$out['validationProfiles'][$key]['uses']++;}
  }
 }
}
$out['validationProfiles']=array_values($out['validationProfiles']);
$out['formCompatibility']=array();$seen=array();foreach($out['validationProfiles'] as $profile){$name=$profile['config']['form'];if(!$name||isset($seen[$name]))continue;$seen[$name]=true;$stmt=$p->prepare('SELECT snippet FROM `'.$table_prefix.'site_htmlsnippets` WHERE name=?');$stmt->execute(array($name));$html=$stmt->fetchColumn();$out['formCompatibility'][]=array('name'=>$name,'exists'=>$html!==false,'hasName'=>preg_match('/name=[\x27"]fio[\x27"]/',$html)===1,'hasAgree'=>preg_match('/name=[\x27"]agree[\x27"]/',$html)===1);}
$summary=array('handlers'=>$out['handlers'],'referenceObjects'=>count($out['references']),'baseReferences'=>array_sum(array_map(function($r){return isset($r['handlers']['roistat_hooks'])?$r['handlers']['roistat_hooks']:0;},$out['references'])),'formCompatibility'=>$out['formCompatibility']);
echo json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
