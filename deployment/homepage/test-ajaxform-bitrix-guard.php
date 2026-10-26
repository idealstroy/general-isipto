<?php
// Exercise AjaxForm -> FormIt -> static hook from the connector directory. No consent, no CRM call.
$_SERVER['REQUEST_METHOD']='POST';$_SERVER['HTTP_HOST']='localhost:3000';$_SERVER['REQUEST_URI']='/assets/components/ajaxform/action.php';
require '/var/www/html/config.core.php';require MODX_CORE_PATH.'model/modx/modx.class.php';$modx=new modX();$modx->initialize('web');
chdir('/var/www/html/assets/components/ajaxform');$modx->resource=$modx->getObject('modResource',3075);
$modx->loadClass('ajaxform',MODX_CORE_PATH.'components/ajaxform/model/ajaxform/',false,true);$af=new AjaxForm($modx,array('json_response'=>false));
$key='offline-consent-guard';$_SESSION['AjaxForm'][$key]=array('snippet'=>'FormIt','form'=>'form_footer','hooks'=>'bitrix24_hooks','validate'=>'fio:required,phone:required','validationErrorMessage'=>'Validation failed');
$_POST=array('fio'=>'OFFLINE GUARD','phone'=>'+79990000000','website'=>'','pageId'=>3075);$_REQUEST=$_POST;
$result=$af->process($key,$_POST);unset($_SESSION['AjaxForm'][$key]);
$ok=is_array($result)&&$result['success']===false&&isset($result['data']['phone']);
echo json_encode(array('ajaxFormReturnedError'=>$ok,'returnedFieldErrors'=>isset($result['data'])?array_keys($result['data']):array(),'crmRequests'=>0,'formRecordsCreated'=>0))."\n";
if(!$ok)exit(1);
