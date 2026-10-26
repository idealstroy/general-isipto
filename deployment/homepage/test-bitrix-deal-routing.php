<?php
require '/var/www/html/core/components/bitrix24/client.php';require '/var/www/html/core/components/bitrix24/submission.php';
$tests=0;function ensure($ok,$label){global $tests;if(!$ok)throw new RuntimeException($label);$tests++;}
$dir=sys_get_temp_dir().'/isipto-deal-unit-'.getmypid();$url='https://isipto.bitrix24.ru/rest/1/OFFLINE_TOKEN/';
$input=array('name'=>'OFFLINE','phone'=>'+79990000000','email'=>'','page'=>'http://localhost:3000/','reason'=>'TEST');
$routing=array('categoryId'=>31,'stageId'=>'C31:NEW','assignedById'=>777);
$calls=array();$client=new IsiptoBitrix24Client($url,function($url,$params)use(&$calls){$method=basename($url,'.json');$calls[]=$method;
 if($method==='crm.duplicate.findbycomm')$result=array('CONTACT'=>array(44));
 elseif($method==='crm.deal.add'){ensure($params['fields']['CATEGORY_ID']===31,'funnel');ensure($params['fields']['ASSIGNED_BY_ID']===777,'responsible');ensure($params['fields']['STAGE_ID']==='C31:NEW','stage');ensure($params['fields']['CONTACT_IDS']===array(44),'existing contact');$result=555;}
 elseif($method==='crm.deal.get')$result=array('ID'=>555,'CATEGORY_ID'=>31,'ASSIGNED_BY_ID'=>777);
 else throw new RuntimeException('Unexpected method');
 return array('status'=>200,'body'=>json_encode(array('result'=>$result)));});
$receipt=new IsiptoBitrix24Receipt($dir,hash('sha256','success'));$s=new IsiptoBitrix24Submission($client,$routing);ensure($s->deliver($input,$receipt)===555,'accepted ID');ensure($receipt->read()['routingVerified']===true,'verified receipt');ensure($s->deliver($input,$receipt)===555,'confirmed retry');ensure(count(array_filter($calls,function($x){return $x==='crm.deal.add';}))===1,'no duplicate deal');$receipt->close();
$client=new IsiptoBitrix24Client($url,function($url,$params){$method=basename($url,'.json');if($method==='crm.duplicate.findbycomm')$r=array();elseif($method==='crm.contact.add'){$r=45;ensure($params['fields']['ASSIGNED_BY_ID']===777,'new contact owner');}elseif($method==='crm.deal.add')$r=556;else $r=array('ID'=>556,'CATEGORY_ID'=>95,'ASSIGNED_BY_ID'=>25);return array('status'=>200,'body'=>json_encode(array('result'=>$r)));});
$receipt=new IsiptoBitrix24Receipt($dir,hash('sha256','mismatch'));$s=new IsiptoBitrix24Submission($client,$routing);try{$s->deliver($input,$receipt);throw new RuntimeException('Mismatch accepted');}catch(RuntimeException $e){ensure($e->getMessage()==='BITRIX_ROUTING_MISMATCH','wrong route rejected');}ensure($receipt->read()['id']===556,'created ID retained');ensure($receipt->read()['contactId']===45,'contact ID retained');$receipt->close();
$receipt=new IsiptoBitrix24Receipt($dir,hash('sha256','pending'));$receipt->write(array('pending'=>true,'phase'=>'deal'));try{$s->deliver($input,$receipt);throw new RuntimeException('Uncertain request repeated');}catch(RuntimeException $e){ensure($e->getMessage()==='BITRIX_TRANSPORT_UNCERTAIN','uncertain request blocked');}$receipt->close();
try{new IsiptoBitrix24Submission($client,array('categoryId'=>31,'stageId'=>'C31:NEW','assignedById'=>null));throw new RuntimeException('Missing owner accepted');}catch(RuntimeException $e){ensure($e->getMessage()==='BITRIX_ROUTING_UNCONFIGURED','owner required');}
foreach(glob($dir.'/*.json') as $f)unlink($f);rmdir($dir);echo json_encode(array('passed'=>$tests,'outboundRequests'=>0,'crmRecordsCreated'=>0))."\n";
