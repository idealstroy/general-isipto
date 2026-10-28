<?php
// Native AjaxForm/FormIt regression checks with deliberately missing configuration. No CRM network calls.
if (PHP_SAPI!=='cli')exit(2);
require '/var/www/html/core/config/config.inc.php';if ($database_server!=='db')exit(2);
$_SERVER['REQUEST_METHOD']='POST';$_SERVER['HTTP_HOST']='localhost:3000';$_SERVER['REQUEST_URI']='/assets/components/ajaxform/action.php';
require '/var/www/html/config.core.php';require MODX_CORE_PATH.'model/modx/modx.class.php';$modx=new modX();$modx->initialize('web');
$modx->resource=$modx->getObject('modResource',3075);chdir('/var/www/html/assets/components/ajaxform');
$modx->loadClass('ajaxform',MODX_CORE_PATH.'components/ajaxform/model/ajaxform/',false,true);
$af=new AjaxForm($modx,array('json_response'=>false));$_COOKIE['anti_bot_cookie']='1';
$old=getenv('BITRIX24_ENV_FILE');putenv('BITRIX24_ENV_FILE=/nonexistent-offline-crm-ui-test');
$cases=array(
    'crm_failure_without_honeypot'=>array('fio'=>'OFFLINE UI TEST','phone'=>'+79990000000','agree'=>'on','pageId'=>3075),
    'crm_failure_with_honeypot'=>array('fio'=>'OFFLINE UI TEST','phone'=>'+79990000000','agree'=>'on','website'=>'','pageId'=>3075),
    'invalid_phone'=>array('fio'=>'OFFLINE UI TEST','phone'=>'123','agree'=>'on','pageId'=>3075),
    'missing_consent'=>array('fio'=>'OFFLINE UI TEST','phone'=>'+79990000000','pageId'=>3075),
    'honeypot'=>array('fio'=>'OFFLINE UI TEST','phone'=>'+79990000000','agree'=>'on','website'=>'bot','pageId'=>3075)
);
$results=array();
foreach ($cases as $name=>$fields) {
    foreach (array_keys($modx->placeholders) as $key)if(strpos($key,'fi.')===0)$modx->unsetPlaceholder($key);
    unset($modx->services['formit']);
    $key='offline-crm-ui-'.$name;$_SESSION['AjaxForm'][$key]=array('snippet'=>'FormIt','form'=>'form_tpl1','hooks'=>'bitrix24_hooks','validate'=>'fio:required,phone:required','validationErrorMessage'=>'Validation failed');
    $_POST=$fields;$_REQUEST=$fields;$response=$af->process($key,$fields);unset($_SESSION['AjaxForm'][$key]);
    $ok=is_array($response)&&$response['success']===false;
    if(strpos($name,'crm_failure')===0)$ok=$ok&&empty($response['data'])&&strpos($response['message'],'доставка в CRM не подтверждена')!==false;
    if($name==='invalid_phone')$ok=$ok&&isset($response['data']['phone']);
    if($name==='missing_consent')$ok=$ok&&isset($response['data']['phone']);
    if($name==='honeypot')$ok=$ok&&isset($response['data']['website']);
    $results[$name]=array('passed'=>$ok,'success'=>$response['success'],'errorFields'=>array_keys($response['data']),'generalMessage'=>$response['message']);
}
foreach (array_keys($modx->placeholders) as $key)if(strpos($key,'fi.')===0)$modx->unsetPlaceholder($key);
$modx->setPlaceholder('fi.successMessage','OFFLINE SUCCESS');$success=$af->handleFormIt(array('fields'=>array('phone'=>'+79990000000')));
$results['successful_response_unchanged']=array('passed'=>$success['success']===true);
if($old===false)putenv('BITRIX24_ENV_FILE');else putenv('BITRIX24_ENV_FILE='.$old);
echo json_encode(array('tests'=>$results,'crmRequests'=>0,'savedFormRecords'=>0),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";
foreach($results as $case)if(!$case['passed'])exit(1);
