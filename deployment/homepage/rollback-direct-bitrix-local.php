<?php
// Executed by rollback-direct-bitrix-local.mjs with private backup data, never from HTTP.
if(PHP_SAPI!=='cli'||!isset($backup))exit(2);
require '/var/www/html/core/config/config.inc.php';if($database_server!=='db')exit(2);
require '/var/www/html/config.core.php';require MODX_CORE_PATH.'model/modx/modx.class.php';$modx=new modX();$modx->initialize('web');
$chunk=$modx->getObject('modChunk',array('name'=>'footer_new'));$expected=str_replace('&hooks=`FormItSaveForm,roistat_hooks`','&hooks=`FormItSaveForm,bitrix24_hooks`',$backup['footer_new']['snippet']);
if(!$chunk||$chunk->getContent()!==$expected)throw new RuntimeException('Popup has other changes; refusing rollback');
$chunk->setContent($backup['footer_new']['snippet']);if(!$chunk->save())throw new RuntimeException('Rollback failed');
// Keep the inactive snippet/file and delivery receipts until the rollback is verified; no data is deleted.
$modx->cacheManager->refresh(array('resource'=>array('contexts'=>array('web')),'db'=>array(),'scripts'=>array()));
echo "Original local popup hook restored\n";
