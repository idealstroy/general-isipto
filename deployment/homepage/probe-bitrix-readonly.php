<?php
require '/var/www/html/core/components/bitrix24/client.php';
$c=null;
try {
 $c=IsiptoBitrix24Client::fromEnvironment();$fields=$c->call('crm.deal.fields',array());
 $required=array();foreach($fields as $k=>$v)if(!empty($v['isRequired'])&&empty($v['isReadOnly']))$required[]=$k;
 echo json_encode(array('authenticated'=>true,'crmSchemaReadable'=>true,'requiredWritableFields'=>$required,'leadCreationTested'=>false))."\n";
}catch(Exception $e){echo json_encode(array('authenticated'=>false,'error'=>$e->getMessage(),'apiCode'=>$c?$c->getLastApiError():null))."\n";exit(1);}
