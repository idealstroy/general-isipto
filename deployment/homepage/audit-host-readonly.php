<?php
// Stream through SSH stdin. Never upload to document root or bootstrap MODX.
$root='/home/i/idealstroy/isipto.ru/public_html';
if (!is_dir($root) && is_dir('/var/www/html')) $root='/var/www/html';
$out=array('phpCli'=>PHP_VERSION,'root'=>$root,'files'=>array(),'links'=>array(),'errors'=>array(),'phpDirectives'=>array());
foreach(array($root.'/.htaccess',dirname($root).'/.htaccess',dirname(dirname($root)).'/.htaccess',$root.'/.user.ini') as $f) {
 if(is_readable($f)){foreach(file($f) as $line) if(preg_match('/AddHandler|SetHandler|php[_ -]?version|application\/x-httpd|FcgidWrapper/i',$line)) $out['phpDirectives'][]=array('file'=>$f,'line'=>trim($line));}
}
if (!getenv('ISIPTO_AUDIT_DB_ONLY') && !getenv('ISIPTO_AUDIT_RELATIONS_ONLY')) {
$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::LEAVES_ONLY,RecursiveIteratorIterator::CATCH_GET_CHILD);
foreach($it as $file){$rel=substr($file->getPathname(),strlen($root)+1);if($file->isLink()){$out['links'][$rel]=readlink($file->getPathname());continue;}if(!$file->isFile())continue;$size=$file->getSize();
 // Cache/session files are inherently volatile; inventory them, but do not hash.
 $volatile=preg_match('~^(core/(cache|cache1)/|.*(?:/sessions/))~',$rel);
 $hash=$volatile?null:@hash_file('sha256',$file->getPathname());
 if($hash===false)$out['errors'][]='Unreadable: '.$rel;
 $out['files'][$rel]=array('size'=>$size,'sha256'=>$hash,'volatile'=>(bool)$volatile);
}
}
require $root.'/core/config/config.inc.php';
$pdo=new PDO('mysql:host='.$database_server.';dbname='.$dbase.';charset=utf8',$database_user,$database_password,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$out['database']=array('tables'=>array(),'objects'=>array());
$out['database']['serverVersion']=$pdo->query('SELECT VERSION()')->fetchColumn();
$out['database']['timeZone']=$pdo->query('SELECT @@session.time_zone AS sessionZone, @@system_time_zone AS systemZone')->fetch(PDO::FETCH_ASSOC);
$out['database']['relations']=array();
$available=$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach(array('context','context_setting','media_sources','media_sources_contexts','site_plugin_events','property_set','element_property_sets','site_tmplvar_access','site_tmplvar_resourcegroups','site_template_access') as $name){
 if(!in_array($table_prefix.$name,$available))continue;
 $query=$name==='context_setting'?'SELECT *, CAST(UNIX_TIMESTAMP(editedon) AS CHAR) AS _auditUtcEditedon FROM `'.$table_prefix.$name.'`':'SELECT * FROM `'.$table_prefix.$name.'`';
 $hashes=array();foreach($pdo->query($query,PDO::FETCH_ASSOC) as $row){if($name==='context_setting'){$row['editedon']=$row['_auditUtcEditedon'];unset($row['_auditUtcEditedon']);}$hashes[]=hash('sha256',json_encode($row));}sort($hashes,SORT_STRING);
 $out['database']['relations'][$name]=array('rows'=>count($hashes),'sha256'=>hash('sha256',implode('\n',$hashes)));
}
$out['database']['contextSettings']=array();
foreach($pdo->query('SELECT *, CAST(UNIX_TIMESTAMP(editedon) AS CHAR) AS _auditUtcEditedon FROM `'.$table_prefix.'context_setting`',PDO::FETCH_ASSOC) as $row){$item=array('fieldHashes'=>array(),'editedon'=>$row['editedon'],'utcEditedon'=>$row['_auditUtcEditedon']);$row['editedon']=$row['_auditUtcEditedon'];unset($row['_auditUtcEditedon']);foreach($row as $key=>$value)$item['fieldHashes'][$key]=hash('sha256',json_encode($value));if(in_array($row['key'],array('site_url','base_url','http_host','site_start','cache_path','error_page','friendly_urls')))$item['publicValue']=$row['value'];$out['database']['contextSettings'][$row['context_key'].':'.$row['key']]=$item;}
$homeId=$pdo->query('SELECT value FROM `'.$table_prefix.'context_setting` WHERE context_key=\'web\' AND `key`=\'site_start\'')->fetchColumn();
if(!$homeId)$homeId=$pdo->query('SELECT value FROM `'.$table_prefix.'system_settings` WHERE `key`=\'site_start\'')->fetchColumn();
$out['database']['homepage']=$pdo->query('SELECT id,template,editedon,context_key,uri FROM `'.$table_prefix.'site_content` WHERE id='.(int)$homeId)->fetch(PDO::FETCH_ASSOC);
if(getenv('ISIPTO_AUDIT_RELATIONS_ONLY')){echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";exit;}
foreach($pdo->query('SHOW TABLE STATUS') as $r)$out['database']['tables'][]=array('name'=>$r['Name'],'engine'=>$r['Engine'],'rows'=>$r['Rows'],'bytes'=>$r['Data_length']+$r['Index_length']);
$sets=array('site_templates'=>array('id','templatename','static','static_file','source'),'site_htmlsnippets'=>array('id','name','static','static_file','source'),'site_snippets'=>array('id','name','static','static_file'),'site_plugins'=>array('id','name','disabled'),'site_tmplvars'=>array('id','name'),'site_tmplvar_templates'=>array('tmplvarid','templateid','rank'),'site_content'=>array('id','template','context_key','uri','published','deleted'));
foreach($sets as $table=>$visible){$rows=$pdo->query('SELECT * FROM `'.$table_prefix.$table.'`');$result=array();while($row=$rows->fetch(PDO::FETCH_ASSOC)){$item=array();foreach($visible as $key)$item[$key]=isset($row[$key])?$row[$key]:null;$item['sha256']=hash('sha256',json_encode($row));$item['fieldHashes']=array();foreach($row as $key=>$value)$item['fieldHashes'][$key]=hash('sha256',json_encode($value));$result[]=$item;}$out['database']['objects'][$table]=$result;}
$out['database']['settings']=array();foreach($pdo->query('SELECT `key`,value FROM `'.$table_prefix.'system_settings`') as $r){$out['database']['settings'][$r['key']]=hash('sha256',$r['value']);if(in_array($r['key'],array('site_start','site_url','base_url','friendly_urls','blog'))) $out['database']['publicSettings'][$r['key']]=$r['value'];}
$out['database']['tvValues']=array();foreach($pdo->query('SELECT tmplvarid,contentid,value FROM `'.$table_prefix.'site_tmplvar_contentvalues` ORDER BY contentid,tmplvarid') as $r)$out['database']['tvValues'][$r['contentid'].':'.$r['tmplvarid']]=hash('sha256',$r['value']);
echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
