<?php
if (PHP_SAPI!=='cli' || !isset($settings)) exit(2);
$root=realpath($settings['targetRoot']);if ($root!=='/home/i/idealstroy/isipto.ru/public_html') exit(2);
define('MODX_BASE_PATH',$root.'/');require $root.'/core/components/bitrix24/client.php';
$client=null;
try {$client=IsiptoBitrix24Client::fromEnvironment();$fields=$client->call('crm.deal.fields',array());echo json_encode(array('readOnly'=>true,'authentication'=>'OK','fieldsCount'=>count($fields),'crmWrites'=>0))."\n";}
catch (Exception $e) {echo json_encode(array('readOnly'=>true,'authentication'=>'FAILED','errorCode'=>$e->getMessage(),'apiError'=>$client?$client->getLastApiError():null,'crmWrites'=>0))."\n";}
