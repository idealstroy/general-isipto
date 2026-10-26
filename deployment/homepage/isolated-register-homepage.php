<?php
// This fixture is deliberately unable to run against either live or normal local DB.
if (PHP_SAPI !== 'cli' || getenv('ISIPTO_ISOLATED_AUDIT') !== '1') exit(2);
require '/var/www/html/core/config/config.inc.php';
if ($database_server !== 'isipto-audit-db-20261022') { fwrite(STDERR,"Wrong database\n"); exit(2); }
define('MODX_API_MODE',true);
require '/var/www/html/config.core.php';
require MODX_CORE_PATH.'model/modx/modx.class.php';
$modx=new modX();$modx->initialize('web');
$spec=json_decode(file_get_contents('/audit-release/release-spec.json'),true);
$home=$modx->getObject('modResource',(int)$modx->getOption('site_start'));
if(!$home || (int)$home->get('template')!==33) throw new Exception('Unexpected original homepage');
$previous=(int)$home->get('template');
foreach(array(array('modChunk',$spec['chunk'],'name','snippet'),array('modTemplate',$spec['template'],'templatename','content')) as $definition){
 list($class,$details,$nameField,$contentField)=$definition;
 if($modx->getObject($class,array($nameField=>$details['name'])))throw new Exception('Fixture already registered');
 $object=$modx->newObject($class);$object->fromArray(array($nameField=>$details['name'],'static'=>1,'static_file'=>$details['static_file'],'source'=>0,$contentField=>''));
 if(!$object->save())throw new Exception('Could not register '.$class);
 if($class==='modTemplate')$newTemplate=(int)$object->get('id');else $newChunk=(int)$object->get('id');
}
$count=0;
foreach($modx->getCollection('modTemplateVarTemplate',array('templateid'=>$previous)) as $binding){$copy=$modx->newObject('modTemplateVarTemplate');$copy->set('tmplvarid',$binding->get('tmplvarid'));$copy->set('templateid',$newTemplate);$copy->set('rank',$binding->get('rank'));if(!$copy->save())throw new Exception('TV binding failed');$count++;}
$home->set('template',$newTemplate);$home->set('editedon',time());if(!$home->save())throw new Exception('Could not assign template');
$modx->cacheManager->refresh(array('resource'=>array('contexts'=>array('web')),'db'=>array(),'scripts'=>array()));
echo json_encode(array('php'=>PHP_VERSION,'home'=>(int)$home->get('id'),'previousTemplate'=>$previous,'newTemplate'=>$newTemplate,'newChunk'=>$newChunk,'tvBindings'=>$count,'database'=>'ISOLATED_FIXTURE_ONLY'))."\n";
