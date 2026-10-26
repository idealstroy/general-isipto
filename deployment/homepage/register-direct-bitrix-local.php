<?php
if(PHP_SAPI!=='cli')exit(2);
require '/var/www/html/core/config/config.inc.php';if($database_server!=='db')exit('Local database required');
require '/var/www/html/config.core.php';require MODX_CORE_PATH.'model/modx/modx.class.php';
$modx=new modX();$modx->initialize('web');
$chunk=$modx->getObject('modChunk',array('name'=>'footer_new'));if(!$chunk||$chunk->get('static'))throw new RuntimeException('Unexpected popup chunk');
$old=$chunk->getContent();$new=str_replace('&hooks=`FormItSaveForm,roistat_hooks`','&hooks=`FormItSaveForm,bitrix24_hooks`',$old,$count);
if($count!==1)throw new RuntimeException('Expected exactly one original popup hook');
$s=$modx->getObject('modSnippet',array('name'=>'bitrix24_hooks'));if($s)throw new RuntimeException('Snippet already exists; inspect before overwriting');
$s=$modx->newObject('modSnippet');$s->set('name','bitrix24_hooks');$s->set('description','Direct Bitrix24 delivery; server-only secret; confirmed lead ID required');$s->set('static',1);$s->set('source',$modx->getObject('modSnippet',array('name'=>'roistat_hooks'))->get('source'));$s->set('static_file','assets/snippets/bitrix24_hooks.php');$s->set('snippet',preg_replace('/^<\?php\s*/','',file_get_contents('/var/www/html/assets/snippets/bitrix24_hooks.php')));
if(!$s->save())throw new RuntimeException('Could not register hook');
$chunk->setContent($new);if(!$chunk->save()){ $s->remove();throw new RuntimeException('Could not update popup chunk'); }
$modx->cacheManager->refresh(array('resource'=>array('contexts'=>array('web')),'db'=>array(),'scripts'=>array()));
echo json_encode(array('localOnly'=>true,'newSnippetId'=>$s->get('id'),'popupHook'=>'bitrix24_hooks','bottomHook'=>'bitrix24_hooks','formsUnchanged'=>true))."\n";
