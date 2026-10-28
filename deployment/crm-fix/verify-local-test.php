<?php
if(PHP_SAPI!=='cli')exit(2);
require '/var/www/html/core/components/bitrix24/client.php';
$values=array();foreach(file('/run/secrets/isipto-bitrix24.env',FILE_IGNORE_NEW_LINES) as $line)if(preg_match('/^\s*(?:export\s+)?BITRIX24_WEBHOOK_URL\s*=\s*(.*?)\s*$/',$line,$m))$values[]=trim($m[1],"\"'");if(count($values)!==1)exit(2);
$base=preg_replace('~/profile\.json$~','/',trim($values[0]));new IsiptoBitrix24Client($base);
$fields=array('ID','TITLE','CATEGORY_ID','ASSIGNED_BY_ID','STAGE_ID','DATE_CREATE','COMMENTS');
$params=array('filter'=>array('=TITLE'=>'Заявка с сайта — ТЕСТ ЛОКАЛЬНОЙ ФОРМЫ — восстановление CRM','>=DATE_CREATE'=>'2026-10-28T05:37:00+00:00'),'select'=>$fields,'order'=>array('ID'=>'DESC'));
$h=curl_init($base.'crm.deal.list.json');curl_setopt_array($h,array(CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($params),CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>20,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS));
$raw=curl_exec($h);$error=curl_errno($h);$http=curl_getinfo($h,CURLINFO_HTTP_CODE);curl_close($h);$j=json_decode($raw,true);
if($error||!is_array($j)||isset($j['error'])){echo json_encode(array('readOnly'=>true,'readFailed'=>true,'httpStatus'=>$http,'apiError'=>isset($j['error'])?$j['error']:null))."\n";exit(1);}
$deals=array();foreach($j['result'] as $row){$local=strpos(isset($row['COMMENTS'])?$row['COMMENTS']:'','http://localhost:3000/')!==false;unset($row['COMMENTS']);$row['localSourceRecorded']=$local;$deals[]=$row;}
echo json_encode(array('readOnly'=>true,'deals'=>$deals,'crmWrites'=>0),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";
