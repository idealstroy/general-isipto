<?php
require '/var/www/html/config.core.php';require MODX_CORE_PATH.'model/modx/modx.class.php';$modx=new modX();$modx->initialize('web');
$t=$modx->getObject('modTemplate',84);$raw=$t->get('content');$file=$t->getSourceFile();
$out=array('cachePath'=>$modx->getCachePath(),'templateSource'=>$file,'databaseHasNewHook'=>strpos($raw,'FormItSaveForm,bitrix24_hooks')!==false,'fileHasNewHook'=>is_readable($file)&&strpos(file_get_contents($file),'FormItSaveForm,bitrix24_hooks')!==false,'templateFileHash'=>is_readable($file)?hash_file('sha256',$file):null);
$contents=$t->getContent();$out['runtimeHasNewHook']=strpos($contents,'FormItSaveForm,bitrix24_hooks')!==false;
$out['resourceCacheFiles']=glob($modx->getCachePath().'resource/web/resources/3075*');
echo json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
