<?php
if(PHP_SAPI!=='cli')exit(2);require '/var/www/html/core/config/config.inc.php';if($database_server!=='db')exit(2);
require '/var/www/html/config.core.php';require MODX_CORE_PATH.'model/modx/modx.class.php';$modx=new modX();$modx->initialize('web');
$pattern='/(?<![A-Za-z0-9_])roistat_hooks(?![A-Za-z0-9_])/';$remaining=array();$badEncoding=array();
foreach(array('modTemplate','modChunk','modResource') as $class){foreach($modx->getCollection($class) as $o){$content=$o->getContent();if(preg_match($pattern,$content))$remaining[]=array($class,(int)$o->get('id'));if(strpos($content,"\xEF\xBF\xBD")!==false)$badEncoding[]=array($class,(int)$o->get('id'));}}
$consentMissing=array();foreach(array('ppr_form_tpl1','ppr_form_tpl2','ppr_form_tpl_new','new_form_tpl1','ppr_form_tpl_new_pprk','project_form') as $name){$o=$modx->getObject('modChunk',array('name'=>$name));if(strpos($o->getContent(),'name="agree"')===false)$consentMissing[]=$name;}
$aliases=array();foreach(array('roistat_hooks','Копия roistat_hooks') as $name){$o=$modx->getObject('modSnippet',array('name'=>$name));$code=$o->getContent();$aliases[]=array('name'=>$name,'directOnly'=>strpos($code,'runSnippet("bitrix24_hooks"')!==false&&strpos($code,'curl_')===false&&strpos($code,'api.roistat')===false,'static'=>(int)$o->get('static'));}
$modx->cacheManager->refresh(array('resource'=>array('contexts'=>array('web')),'db'=>array(),'scripts'=>array()));
echo json_encode(array('remainingCommonReferences'=>$remaining,'encodingWarnings'=>$badEncoding,'consentMissing'=>$consentMissing,'aliases'=>$aliases,'crmRequests'=>0),JSON_UNESCAPED_UNICODE)."\n";
if(count($remaining)||count($consentMissing)||count($badEncoding))exit(1);
