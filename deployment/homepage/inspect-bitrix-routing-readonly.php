<?php
// CLI-only investigation of the specific test lead. No CRM writes and no secret/phone output.
if(PHP_SAPI!=='cli')exit(2);
require '/var/www/html/core/components/bitrix24/client.php';
$lines=file('/run/secrets/isipto-bitrix24.env',FILE_IGNORE_NEW_LINES);$urls=array();
foreach($lines as $line){$line=preg_replace('/^\xEF\xBB\xBF/','',$line);if(preg_match('/^\s*(?:export\s+)?BITRIX24_WEBHOOK_URL\s*=\s*(.*?)\s*$/',$line,$m))$urls[]=trim($m[1],"\"'");}
if(count($urls)!==1)exit(2);$base=preg_replace('~/profile\.json$~','/',trim($urls[0]));new IsiptoBitrix24Client($base);
function readCrm($method,$params){global $base;
 if(!in_array($method,array('crm.lead.get','crm.status.list','crm.deal.list','crm.category.get','user.get','user.search','crm.deal.fields'),true))throw new RuntimeException('Read-only method required');
 $c=curl_init($base.$method.'.json');curl_setopt_array($c,array(CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($params),CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>15,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS));
 $body=curl_exec($c);$status=(int)curl_getinfo($c,CURLINFO_HTTP_CODE);$failed=curl_errno($c);curl_close($c);$j=json_decode($body,true);
 if($failed||!is_array($j))return array('readError'=>'TRANSPORT_ERROR','http'=>$status);
 if(array_key_exists('error',$j))return array('readError'=>preg_match('/^[A-Za-z0-9_]{1,100}$/D',(string)$j['error'])?$j['error']:'API_ERROR','http'=>$status);
 return isset($j['result'])?$j['result']:array();
}
if(getenv('ISIPTO_ROUTING_PREFLIGHT')){
 $users=readCrm('user.search',array('FILTER'=>array('NAME'=>'Яна','LAST_NAME'=>'Черная','ACTIVE'=>'Y')));$safeUsers=array();
 if(isset($users['readError']))$safeUsers=$users;else foreach($users as $u)$safeUsers[]=array_intersect_key($u,array_flip(array('ID','NAME','LAST_NAME','ACTIVE')));
 $stages=readCrm('crm.status.list',array('filter'=>array('ENTITY_ID'=>'DEAL_STAGE_31')));$safeStages=array();foreach($stages as $s)if(is_array($s)&&isset($s['STATUS_ID']))$safeStages[]=array_intersect_key($s,array_flip(array('STATUS_ID','NAME','SEMANTICS','SORT')));
 $fields=readCrm('crm.deal.fields',array());$required=array();foreach($fields as $k=>$f)if(is_array($f)&&!empty($f['isRequired'])&&empty($f['isReadOnly']))$required[]=$k;
 echo json_encode(array('users'=>$safeUsers,'category31Stages'=>$safeStages,'dealRequiredFields'=>$required),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";exit;
}
$lead=readCrm('crm.lead.get',array('id'=>8896));$out=array('checkedUtc'=>gmdate('c'),'lead'=>array(),'linkedDeals'=>array(),'responsibleUsers'=>array());
if(isset($lead['readError'])){$out['lead']=$lead;}else{foreach(array('ID','TITLE','ASSIGNED_BY_ID','STATUS_ID','STATUS_SEMANTIC_ID','OPENED','DATE_CREATE','DATE_MODIFY','CREATED_BY_ID','CATEGORY_ID') as $k)if(isset($lead[$k]))$out['lead'][$k]=$lead[$k];}
$statuses=readCrm('crm.status.list',array('filter'=>array('ENTITY_ID'=>'STATUS')));foreach($statuses as $s)if(is_array($s)&&isset($s['STATUS_ID'])&&isset($lead['STATUS_ID'])&&$s['STATUS_ID']===$lead['STATUS_ID'])$out['lead']['statusName']=$s['NAME'];
$deals=readCrm('crm.deal.list',array('filter'=>array('LEAD_ID'=>8896),'select'=>array('ID','TITLE','ASSIGNED_BY_ID','CATEGORY_ID','STAGE_ID','LEAD_ID')));
$userIds=array();if(isset($lead['ASSIGNED_BY_ID']))$userIds[$lead['ASSIGNED_BY_ID']]=true;
if(isset($deals['readError']))$out['linkedDeals']=$deals;else foreach($deals as $d){$out['linkedDeals'][]=$d;if(isset($d['ASSIGNED_BY_ID']))$userIds[$d['ASSIGNED_BY_ID']]=true;}
$out['dealCategories']=array();$seenCategories=array();foreach($out['linkedDeals'] as $d){if(!is_array($d)||!isset($d['CATEGORY_ID'])||isset($seenCategories[$d['CATEGORY_ID']]))continue;$seenCategories[$d['CATEGORY_ID']]=true;$cat=readCrm('crm.category.get',array('entityTypeId'=>2,'id'=>(int)$d['CATEGORY_ID']));$item=isset($cat['category'])?array_intersect_key($cat['category'],array_flip(array('id','name','entityTypeId'))):$cat;$stages=readCrm('crm.status.list',array('filter'=>array('ENTITY_ID'=>'DEAL_STAGE_'.$d['CATEGORY_ID'])));foreach($stages as $st)if(is_array($st)&&isset($st['STATUS_ID'])&&$st['STATUS_ID']===$d['STAGE_ID'])$item['currentStageName']=$st['NAME'];$out['dealCategories'][]=$item;}
foreach(array_keys($userIds) as $id){$users=readCrm('user.get',array('ID'=>$id));if(isset($users['readError'])){$out['responsibleUsers'][]=array('ID'=>$id,'readError'=>$users['readError']);continue;}foreach($users as $u){if(!isset($u['ID'])||(int)$u['ID']!==(int)$id)continue;$out['responsibleUsers'][]=array_intersect_key($u,array_flip(array('ID','NAME','SECOND_NAME','LAST_NAME','ACTIVE')));}}
$category=readCrm('crm.category.get',array('entityTypeId'=>2,'id'=>31));$out['previousDealCategory31']=isset($category['category'])?array_intersect_key($category['category'],array_flip(array('id','name','entityTypeId'))):$category;
echo json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
