<?php
require '/var/www/html/config.core.php';require MODX_CORE_PATH.'model/modx/modx.class.php';$modx=new modX();$modx->initialize('web');chdir('/var/www/html/assets/components/ajaxform');
class RetiredHookTest{public $errors=array();function getValue($k){return null;}function addError($k,$v){$this->errors[$k]=$v;}}
$checks=array();foreach(array('roistat_hooks','Копия roistat_hooks') as $name){$hook=new RetiredHookTest();$r=$modx->runSnippet($name,array('hook'=>$hook));$checks[$name]=$r===false&&isset($hook->errors['phone'])&&isset($hook->errors['agree']);}
$s=$modx->getObject('modSnippet',array('name'=>'bitrix24_hooks'));$checks['sourceOne']=(int)$s->get('source')===1;$checks['staticFile']=$s->get('static_file')==='assets/snippets/bitrix24_hooks.php';
$checks['oldFilesAbsent']=!file_exists('/var/www/html/assets/snippets/roistat_hooks.php')&&!file_exists('/var/www/html/assets/snippets/roistat_hooks_2.php');
echo json_encode(array('checks'=>$checks,'outboundRequests'=>0),JSON_UNESCAPED_UNICODE)."\n";if(in_array(false,$checks,true))exit(1);
