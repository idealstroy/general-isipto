<?php
if(PHP_SAPI!=='cli'||!isset($backup))exit(2);
set_exception_handler(function($e){file_put_contents('php://stderr','Migration stopped: '.$e->getMessage()."\n");exit(1);});
require '/var/www/html/core/config/config.inc.php';if($database_server!=='db')exit(2);
require '/var/www/html/config.core.php';require MODX_CORE_PATH.'model/modx/modx.class.php';$modx=new modX();$modx->initialize('web');
$classes=array('site_templates'=>'modTemplate','site_htmlsnippets'=>'modChunk','site_content'=>'modResource');
$pattern='/(?<![A-Za-z0-9_])roistat_hooks(?![A-Za-z0-9_])/';
$originalConsent=$modx->getObject('modChunk',array('name'=>'form_tpl1'))->getContent();
if(!preg_match('/<div\b[^>]*class="form-footer__checkbox"[^>]*>.*?<\/div>/s',$originalConsent,$m))throw new RuntimeException('Native consent component not found');$consent=$m[0];
$prepared=array();
foreach($backup['objects'] as $entry){
 if(!isset($classes[$entry['table']]))throw new RuntimeException('Unexpected object table');
 $obj=$modx->getObject($classes[$entry['table']],(int)$entry['row']['id']);if(!$obj)throw new RuntimeException('Missing original object');
 // Check DB code before getContent can synchronize a static element.
 $actual=$obj->get($entry['field']);if($actual!==$entry['row'][$entry['field']]&&$actual!==$entry['runtime'])throw new RuntimeException('Object changed since backup: '.$entry['table'].' #'.$entry['row']['id']);
 $text=$entry['runtime'];$updated=preg_replace($pattern,'bitrix24_hooks',$text);
 if(!empty($entry['addConsent'])){
  if(strpos($updated,'name="agree"')!==false||strpos($updated,"name='agree'")!==false)throw new RuntimeException('Consent already changed');
  if(preg_match('/<(?:input|button)\b[^>]*\btype=[\x27"]submit[\x27"][^>]*>/i',$updated,$submit,PREG_OFFSET_CAPTURE)){
   $at=$submit[0][1];$updated=substr($updated,0,$at).$consent."\n".substr($updated,$at);
  }else{
   if(stripos($updated,'</form>')===false)throw new RuntimeException('No form closing tag');
   $updated=preg_replace('/<\/form>/i',$consent."\n</form>",$updated,1);
  }
 }
 $prepared[]=array($obj,$updated);
}
$aliases=array();foreach($backup['legacySnippets'] as $row){$s=$modx->getObject('modSnippet',(int)$row['id']);if(!$s||$s->get('snippet')!==$row['snippet'])throw new RuntimeException('Legacy hook changed since backup');$aliases[]=$s;}
// Everything is validated before the first mutation. DB backups allow exact rollback.
if(!empty($checkOnly)){echo json_encode(array('preflight'=>true,'objects'=>count($prepared),'aliases'=>count($aliases)))."\n";exit;}
$changed=0;foreach($prepared as $item){$item[0]->setContent($item[1]);if(!$item[0]->save())throw new RuntimeException('Could not update original hook reference');$changed++;}
$aliasCode='/* Compatibility for previously opened forms. No Roistat delivery code. */' . "\n"
 . 'if (!isset($hook)) return false;' . "\n"
 . '$properties = array("hook" => $hook);' . "\n"
 . 'if (isset($formit)) $properties["formit"] = $formit;' . "\n"
 . 'return $modx->runSnippet("bitrix24_hooks", $properties);';
foreach($aliases as $s){$s->set('static',0);$s->set('static_file','');$s->set('snippet',$aliasCode);$s->set('description','Compatibility alias to direct Bitrix24; former Roistat delivery removed');if(!$s->save())throw new RuntimeException('Could not retire legacy hook');}
$new=$modx->getObject('modSnippet',array('name'=>'bitrix24_hooks'));$new->getContent();
$modx->cacheManager->refresh(array('resource'=>array('contexts'=>array('web')),'db'=>array(),'scripts'=>array()));
echo json_encode(array('localOnly'=>true,'objectsUpdated'=>$changed,'compatibilityAliases'=>count($aliases),'roistatRequests'=>0,'crmRecordsCreated'=>0))."\n";
