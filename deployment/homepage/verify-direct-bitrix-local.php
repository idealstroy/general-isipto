<?php
if(PHP_SAPI!=='cli')exit(2);
require '/var/www/html/core/config/config.inc.php';if($database_server!=='db')exit(2);
require '/var/www/html/config.core.php';require MODX_CORE_PATH.'model/modx/modx.class.php';$modx=new modX();$modx->initialize('web');
$s=$modx->getObject('modSnippet',array('name'=>'bitrix24_hooks'));
if(!$s||!$s->get('static')||$s->get('source')<1||$s->get('static_file')!=='assets/snippets/bitrix24_hooks.php')throw new RuntimeException('Hook wiring invalid');
$s->getContent();$modx->cacheManager->refresh(array('resource'=>array('contexts'=>array('web')),'db'=>array(),'scripts'=>array()));
$template=$modx->getObject('modTemplate',84);
if(!$template||!$template->get('static')||$template->get('static_file')!=='assets/templates/home_solution.html')throw new RuntimeException('Unexpected homepage template');
$templateContent=$template->getContent();
if(strpos($templateContent,'FormItSaveForm,bitrix24_hooks')===false)throw new RuntimeException('Runtime homepage hook not synchronized');
$modx->cacheManager->refresh(array('resource'=>array('contexts'=>array('web')),'db'=>array(),'scripts'=>array()));
$footer=$modx->getObject('modChunk',array('name'=>'footer_new'))->getContent();
if(substr_count($footer,'&hooks=`FormItSaveForm,bitrix24_hooks`')!==1)throw new RuntimeException('Popup wiring invalid');
$home=file_get_contents('/var/www/html/assets/templates/home_solution.html');
if(substr_count($home,'&hooks=`FormItSaveForm,bitrix24_hooks`')!==1)throw new RuntimeException('Bottom wiring invalid');
$forms=array();foreach(array('form_tpl1','form_footer') as $name){$body=$modx->getObject('modChunk',array('name'=>$name))->getContent();$html=$modx->runSnippet('AjaxForm',array('snippet'=>'FormIt','form'=>$name,'hooks'=>'FormItSaveForm,bitrix24_hooks','validate'=>'fio:required,phone:required','frontend_css'=>''));
 $ok=strpos($html,'name="af_action"')!==false&&strpos($html,'name="agree"')!==false&&strpos($html,'name="csrf_token"')!==false;
 if(!$ok)throw new RuntimeException('Native form render failed');
 $forms[$name]=array('nativeAjaxAction'=>true,'consentPresent'=>true,'csrfFieldPresent'=>true,'sourceSha256'=>hash('sha256',$body));
}
echo json_encode(array('localOnly'=>true,'php'=>PHP_VERSION,'runtimeHomepageSynchronized'=>true,'secretMountedOutsideDocumentRoot'=>is_readable('/run/secrets/isipto-bitrix24.env'),'forms'=>$forms,'homeSha256'=>hash('sha256',$home),'footerSha256'=>hash('sha256',$footer),'crmLeadsCreated'=>0),JSON_PRETTY_PRINT)."\n";
