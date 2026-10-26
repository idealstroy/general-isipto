<?php
require '/var/www/html/config.core.php';require MODX_CORE_PATH.'model/modx/modx.class.php';$modx=new modX();$modx->initialize('web');
chdir('/var/www/html/assets/components/ajaxform');
class DispatchHook {public $errors=array();function getValue($k){return null;}function addError($k,$v){$this->errors[$k]=$v;}}
$hook=new DispatchHook();$result=$modx->runSnippet('bitrix24_hooks',array('hook'=>$hook));
echo json_encode(array('returnedFalse'=>$result===false,'returnedType'=>gettype($result),'errorFields'=>array_keys($hook->errors),'consentGuardRan'=>isset($hook->errors['agree']),'outboundRequests'=>0))."\n";
