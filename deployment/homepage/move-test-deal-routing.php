<?php
// The site owner requested category 31 and Yana Chernaya (profile ID 3280).
if(PHP_SAPI!=='cli')exit(2);
require '/var/www/html/core/components/bitrix24/client.php';
$urls=array();foreach(file('/run/secrets/isipto-bitrix24.env',FILE_IGNORE_NEW_LINES) as $line){if(preg_match('/^\s*BITRIX24_WEBHOOK_URL\s*=\s*(.*?)\s*$/',preg_replace('/^\xEF\xBB\xBF/','',$line),$m))$urls[]=trim($m[1],"\"'");}
if(count($urls)!==1)exit(2);$base=preg_replace('~/profile\.json$~','/',trim($urls[0]));new IsiptoBitrix24Client($base);
function callTestDeal($method,$params){global $base;
 if(!in_array($method,array('crm.deal.get','crm.item.update'),true)||(int)$params['id']!==255134)throw new RuntimeException('Specific test deal only');
 if($method==='crm.item.update'&&(int)$params['entityTypeId']!==2)throw new RuntimeException('Deal entity only');
 $h=curl_init($base.$method.'.json');curl_setopt_array($h,array(CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($params),CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>15,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS));$raw=curl_exec($h);$status=curl_getinfo($h,CURLINFO_HTTP_CODE);$err=curl_errno($h);curl_close($h);$j=json_decode($raw,true);
 if($err||$status<200||$status>=300||!is_array($j)||array_key_exists('error',$j)||!array_key_exists('result',$j))throw new RuntimeException('Test deal API request not confirmed');return $j['result'];
}
$before=callTestDeal('crm.deal.get',array('id'=>255134));
if(!isset($before['LEAD_ID'],$before['TITLE'])||(int)$before['LEAD_ID']!==8896||$before['TITLE']!=='Заявка с сайта — Проверка 26 октября')throw new RuntimeException('Unexpected test deal identity');
if(!in_array((int)$before['CATEGORY_ID'],array(95,31),true)||!in_array((int)$before['ASSIGNED_BY_ID'],array(25,3280),true))throw new RuntimeException('Deal has other changes; refusing overwrite');
$report=array('checkedUtc'=>gmdate('c'),'before'=>array_intersect_key($before,array_flip(array('ID','TITLE','CATEGORY_ID','STAGE_ID','ASSIGNED_BY_ID','LEAD_ID'))));
echo json_encode(array('before'=>$report['before']),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";flush();
if((int)$before['CATEGORY_ID']!==31||(int)$before['ASSIGNED_BY_ID']!==3280){$ok=callTestDeal('crm.item.update',array('entityTypeId'=>2,'id'=>255134,'fields'=>array('categoryId'=>31,'stageId'=>'C31:NEW','assignedById'=>3280)));if(!is_array($ok))throw new RuntimeException('Update not confirmed');}
$after=callTestDeal('crm.deal.get',array('id'=>255134));$report['after']=array_intersect_key($after,array_flip(array('ID','TITLE','CATEGORY_ID','STAGE_ID','ASSIGNED_BY_ID','LEAD_ID')));$report['verified']=(int)$after['CATEGORY_ID']===31&&(int)$after['ASSIGNED_BY_ID']===3280;
echo json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";if(!$report['verified'])exit(1);
