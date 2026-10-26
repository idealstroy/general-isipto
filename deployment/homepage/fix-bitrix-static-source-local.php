<?php
if(PHP_SAPI!=='cli')exit(2);
require '/var/www/html/core/config/config.inc.php';if($database_server!=='db')exit(2);
require '/var/www/html/config.core.php';require MODX_CORE_PATH.'model/modx/modx.class.php';$modx=new modX();$modx->initialize('web');
$s=$modx->getObject('modSnippet',array('name'=>'bitrix24_hooks'));$original=$modx->getObject('modSnippet',array('name'=>'roistat_hooks'));
if(!$s||!$original||$s->get('static_file')!=='assets/snippets/bitrix24_hooks.php')throw new RuntimeException('Unexpected hook');
$source=(int)$original->get('source');if($source<1)throw new RuntimeException('Expected original filesystem source');
$file='/var/www/html/assets/snippets/bitrix24_hooks.php';if(!is_file($file)||filesize($file)<1000)throw new RuntimeException('Hook source missing');
$s->set('source',$source);$s->set('snippet',preg_replace('/^<\?php\s*/','',file_get_contents($file)));if(!$s->save())throw new RuntimeException('Could not save source');
$modx->cacheManager->refresh(array('resource'=>array('contexts'=>array('web')),'db'=>array(),'scripts'=>array()));
chdir('/var/www/html/assets/components/ajaxform');$s=$modx->getObject('modSnippet',array('name'=>'bitrix24_hooks'));$path=$s->getSourceFile();
if($path!==$file||strlen($s->getContent())<1000)throw new RuntimeException('Connector path still incorrect');
echo json_encode(array('localOnly'=>true,'source'=>$source,'resolvedFromAjaxFormDirectory'=>$path,'hookLoaded'=>true,'crmRequests'=>0))."\n";
