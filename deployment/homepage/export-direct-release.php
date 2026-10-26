<?php
if(PHP_SAPI!=='cli')exit(2);require '/var/www/html/core/config/config.inc.php';if($database_server!=='db')exit(2);
$p=new PDO('mysql:host='.$database_server.';dbname='.$dbase.';charset=utf8',$database_user,$database_password,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));$out=array();
$extra=array('form_bim','ppr_form_tpl1','ppr_form_tpl2','ppr_form_tpl_new','new_form_tpl1','ppr_form_tpl_new_pprk','project_form');
foreach(array('site_templates'=>array('modTemplate','templatename','content'),'site_htmlsnippets'=>array('modChunk','name','snippet'),'site_snippets'=>array('modSnippet','name','snippet')) as $table=>$keys){foreach($p->query('SELECT * FROM `'.$table_prefix.$table.'`',PDO::FETCH_ASSOC) as $r){$text=$r[$keys[2]];if(!empty($r['static'])&&!empty($r['static_file'])&&is_file('/var/www/html/'.$r['static_file'])){$text=file_get_contents('/var/www/html/'.$r['static_file']);if($keys[0]==='modSnippet')$text=preg_replace('/^<\?php\s*/','',$text);}
$select=$keys[0]==='modSnippet'?preg_match('/^(?:bitrix24_|roistat_hooks(?:_|$)|Копия roistat_hooks)/u',$r[$keys[1]]):preg_match('~bitrix24_hooks|/assets/components/bitrix24/submit-~',$text)||in_array($r[$keys[1]],$extra,true)||$r[$keys[1]]==='ППР Рассылка';if(!$select)continue;
$out[]=array('class'=>$keys[0],'name'=>$r[$keys[1]],'field'=>$keys[2],'code'=>$text,'static'=>(int)$r['static'],'static_file'=>$r['static_file']);}}
echo json_encode(array('objects'=>$out),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
