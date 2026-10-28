<?php
// Read only the two authorized tests; never print webhook, phone or unrelated leads.
if (PHP_SAPI!=='cli') exit(2);
if(empty($testSince)||!strtotime($testSince))exit(2);
require '/var/www/html/core/components/bitrix24/client.php';
$values=array();foreach (file('/run/secrets/isipto-bitrix24.env',FILE_IGNORE_NEW_LINES) as $line) if (preg_match('/^\s*BITRIX24_WEBHOOK_URL\s*=\s*(.*?)\s*$/',$line,$m)) $values[]=trim($m[1],"\"'");
if (count($values)!==1) exit(2);
$base=preg_replace('~/profile\.json$~','/',trim($values[0]));new IsiptoBitrix24Client($base);$result=array();
foreach (array('ТЕСТ АУТСОРСИНГ — попап','ТЕСТ АУТСОРСИНГ — форма') as $name) {
    $fields=array('ID','TITLE','CATEGORY_ID','ASSIGNED_BY_ID','STAGE_ID','DATE_CREATE');
    $params=array('filter'=>array('=TITLE'=>'Заявка с сайта — '.$name,'>=DATE_CREATE'=>$testSince),'order'=>array('ID'=>'DESC'),'select'=>$fields);
    $c=curl_init($base.'crm.deal.list.json');curl_setopt_array($c,array(CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($params),CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>20,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS));
    $raw=curl_exec($c);$err=curl_errno($c);$http=curl_getinfo($c,CURLINFO_HTTP_CODE);curl_close($c);$j=json_decode($raw,true);
    if ($err || !is_array($j) || isset($j['error'])) {$result[]=array('test'=>$name,'readFailed'=>true,'httpStatus'=>$http,'curlCode'=>$err,'apiError'=>isset($j['error'])?$j['error']:null);continue;}
    $deals=array();foreach ($j['result'] as $deal) $deals[]=array_intersect_key($deal,array_flip($fields));
    $result[]=array('test'=>$name,'deals'=>$deals);
}
echo json_encode(array('readOnly'=>true,'tests'=>$result,'crmWrites'=>0),JSON_UNESCAPED_UNICODE)."\n";
