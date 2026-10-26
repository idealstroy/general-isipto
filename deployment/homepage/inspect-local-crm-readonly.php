<?php
require '/var/www/html/core/config/config.inc.php';
$pdo=new PDO('mysql:host='.$database_server.';dbname='.$dbase.';charset=utf8',$database_user,$database_password,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$out=array('snippets'=>array(),'forms'=>array());
$out['environment']=array();foreach(getenv() as $k=>$v)if(preg_match('/bitrix|crm|webhook/i',$k))$out['environment'][]=array('name'=>$k,'configured'=>strlen($v)>0);
$out['envFiles']=array();foreach(array('/var/www/html/.env','/var/www/.env','/var/www/html/core/.env') as $f)if(is_file($f))$out['envFiles'][]=$f;
foreach($pdo->query('SELECT name,snippet,static,static_file FROM `'.$table_prefix.'site_snippets`',PDO::FETCH_ASSOC) as $r){
 $s=$r['snippet'];if($r['static'] && is_readable('/var/www/html/'.ltrim($r['static_file'],'/'))) $s=file_get_contents('/var/www/html/'.ltrim($r['static_file'],'/'));
 if(preg_match('/bitrix|webhook|roistat|crm\.lead\.add/i',$s.' '.$r['name']))$out['snippets'][]=array('name'=>$r['name'],'staticFile'=>$r['static_file'],'sha256'=>hash('sha256',$s),'directBitrix'=>(bool)preg_match('/bitrix|crm\.lead\.add/i',$s),'roistat'=>stripos($s,'cloud.roistat.com')!==false);
}
foreach($pdo->query('SELECT name,snippet,static,static_file FROM `'.$table_prefix.'site_htmlsnippets` WHERE name IN (\'form_footer\',\'form_tpl1\',\'footer_new\',\'header_new\',\'home_solution_review\')',PDO::FETCH_ASSOC) as $r){
 $s=$r['snippet'];if($r['static'] && is_readable('/var/www/html/'.ltrim($r['static_file'],'/'))) $s=file_get_contents('/var/www/html/'.ltrim($r['static_file'],'/'));
 preg_match_all('/&(?:hooks|preHooks)=`([^`]*)`/',$s,$m);
 preg_match_all('/<form\b[^>]*>/i',$s,$forms);
 preg_match_all('/\[\[!?AjaxForm\?(?:`[^`]*`|[^`])*?\]\]/',$s,$calls);
 $configs=array();foreach($calls[0] as $call){$c=array();foreach(array('form','snippet','hooks','preHooks','validate') as $k){preg_match('/&'.$k.'=`([^`]*)`/',$call,$v);$c[$k]=isset($v[1])?$v[1]:null;}$configs[]=$c;}
 $out['forms'][]=array('name'=>$r['name'],'hooks'=>$m[1],'formTags'=>$forms[0],'ajaxForms'=>$configs);
}
$out['homeForms']=array();$s=file_get_contents('/var/www/html/assets/templates/home_solution.html');preg_match_all('/\[\[!?AjaxForm\?(?:`[^`]*`|[^`])*?\]\]/',$s,$calls);foreach($calls[0] as $call){$c=array();foreach(array('form','snippet','hooks','preHooks','validate') as $k){preg_match('/&'.$k.'=`([^`]*)`/',$call,$v);$c[$k]=isset($v[1])?$v[1]:null;}$out['homeForms'][]=$c;}
$out['popupTargets']=array();preg_match_all('/<a\b[^>]*btn-modal[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/s',$s,$buttons,PREG_SET_ORDER);foreach($buttons as $b)$out['popupTargets'][]=array('label'=>trim(strip_tags($b[2])),'target'=>$b[1]);
echo json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
