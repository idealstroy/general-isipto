<?php
// Read-only comparison of effective MODX source, not a second form handler.
if (PHP_SAPI!=='cli') exit(2);
$root='/home/i/idealstroy/isipto.ru/public_html';require $root.'/core/config/config.inc.php';
$pdo=new PDO('mysql:host='.$database_server.';dbname='.$dbase.';charset=utf8',$database_user,$database_password,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
function selectRows($table,$where,$values=array()) {global $pdo,$table_prefix;$q=$pdo->prepare('SELECT * FROM '.$table_prefix.$table.' WHERE '.$where);$q->execute($values);return $q->fetchAll(PDO::FETCH_ASSOC);}
function effective($row,$field) {
    global $root;
    if (empty($row['static']))return $row[$field];
    $base='';if (!empty($row['source'])) {
        $s=selectRows('media_sources','id=?',array($row['source']));if(count($s)!==1)throw new Exception('Source missing');
        $p=json_decode($s[0]['properties'],true);if(!is_array($p))$p=@unserialize($s[0]['properties'],array('allowed_classes'=>false));
        if (isset($p['basePath']['value']))$base=$p['basePath']['value'];
    }
    if ($base!=='')throw new Exception('Unexpected source base');
    $file=realpath($root.'/'.$row['static_file']);if (!$file || strpos($file,$root.'/')!==0 || !is_readable($file))throw new Exception('Static source unavailable');
    return file_get_contents($file);
}
function formCalls($code,$owner) {
    $out=array();preg_match_all('/\[\[!?AjaxForm\?([\s\S]*?)\]\]/',$code,$matches);
    foreach($matches[1] as $call){$item=array('owner'=>$owner);foreach(array('form','snippet','hooks') as $key){preg_match('/&'.$key.'\s*=\s*`([^`]*)`/',$call,$m);$item[$key]=isset($m[1])?$m[1]:null;}$out[]=$item;}
    return $out;
}
function scanChunks($code,$visited=array()) {
    $calls=array();preg_match_all('/\[\[\$([a-zA-Z0-9_-]+)/',$code,$m);
    foreach(array_unique($m[1]) as $name){if(isset($visited[$name]))continue;$visited[$name]=true;
        $rows=selectRows('site_htmlsnippets','name=?',array($name));if(count($rows)!==1)continue;
        $body=effective($rows[0],'snippet');$calls=array_merge($calls,formCalls($body,'chunk:'.$name));
        if(count($visited)<40)$calls=array_merge($calls,scanChunks($body,$visited));
    }
    return $calls;
}
$homeSettings=selectRows('context_setting','context_key=? AND `key`=?',array('web','site_start'));
if (!$homeSettings)$homeSettings=selectRows('system_settings','`key`=?',array('site_start'));
if(count($homeSettings)!==1)throw new Exception('Ambiguous homepage');
$home=selectRows('site_content','id=?',array($homeSettings[0]['value']));
$outsource=selectRows('site_content','uri=? AND context_key=? AND deleted=0',array('autsorsing-pto/','web'));
if(count($home)!==1||count($outsource)!==1)throw new Exception('Ambiguous resource');
$pages=array();
foreach(array('homepage'=>$home[0],'outsourcing-current'=>$outsource[0]) as $name=>$resource){
    $templates=selectRows('site_templates','id=?',array($resource['template']));if(count($templates)!==1)throw new Exception('Template missing');
    $t=$templates[0];$body=effective($t,'content');
    $pages[$name]=array('resourceId'=>(int)$resource['id'],'templateId'=>(int)$t['id'],'templateFile'=>$t['static_file'],'calls'=>array_merge(formCalls($body,'template:'.$t['templatename']),scanChunks($body)));
}
$hook=selectRows('site_snippets','name=?',array('bitrix24_hooks'));if(count($hook)!==1)throw new Exception('Duplicate common handler');
$code=effective($hook[0],'snippet');
$handler=array('name'=>'bitrix24_hooks','snippetId'=>(int)$hook[0]['id'],'staticFile'=>$hook[0]['static_file'],'sha256'=>hash('sha256',$code),'usesSharedClient'=>strpos($code,'components/bitrix24/client.php')!==false,'usesSharedConfiguration'=>strpos($code,'IsiptoBitrix24Client::fromEnvironment()')!==false,'crmFailureAttachedToPhone'=>strpos($code,"\$hook->addError('phone', 'Заявка сохранена на сайте, но доставка в CRM не подтверждена")!==false);
$shared=array();foreach(array('core/components/bitrix24/client.php','core/components/bitrix24/submission.php','core/components/bitrix24/routing.php','core/components/bitrix24/routes.php') as $file)$shared[$file]=hash_file('sha256',$root.'/'.$file);
$secretFile=getenv('BITRIX24_ENV_FILE');$override=(bool)$secretFile;
if(!$secretFile){$secretFile='/run/secrets/isipto-bitrix24.env';if(!is_readable($secretFile))$secretFile=dirname($root).'/.isipto-secrets/bitrix24.env';}
$envConfiguration=array('cliOverridePresent'=>$override,'secretOutsidePublicRoot'=>is_readable($secretFile)&&strpos(realpath($secretFile),$root.'/')!==0,'sourceReadable'=>is_readable($secretFile),'webEnvOverrides'=>array());
foreach(array('.htaccess','.user.ini','php.ini','core/config/config.inc.php') as $relative){$file=$root.'/'.$relative;if(is_readable($file)&&strpos(file_get_contents($file),'BITRIX24_ENV_FILE')!==false)$envConfiguration['webEnvOverrides'][]=$relative;}
$logs=array();
foreach(array('core/cache/logs/error.log','error_log') as $relative){$file=$root.'/'.$relative;if(!is_readable($file))continue;$h=fopen($file,'rb');fseek($h,max(0,filesize($file)-524288));$tail=stream_get_contents($h);fclose($h);
    foreach(explode("\n",$tail) as $line) if(preg_match('/Delivery not confirmed:\s*([A-Z0-9_]+)(?:\s*\(([A-Z0-9_]+)\))?/',$line,$m)){
        preg_match('/^\[([^\]]+)\]/',$line,$date);$logs[]=array('log'=>$relative,'timestamp'=>isset($date[1])?$date[1]:null,'error'=>$m[1],'apiError'=>isset($m[2])?$m[2]:null);
    }
}
$receipts=array();$files=glob($root.'/core/cache/bitrix24-delivery/*.json');
if($files){usort($files,function($a,$b){return filemtime($b)-filemtime($a);});foreach(array_slice($files,0,500) as $file){$state=json_decode(file_get_contents($file),true);if(!is_array($state)||empty($state['time']))continue;$date=gmdate('Y-m-d',(int)$state['time']);if(!isset($receipts[$date]))$receipts[$date]=array('accepted'=>0,'notConfirmed'=>0);$ok=!empty($state['routingVerified'])&&isset($state['id']);$receipts[$date][$ok?'accepted':'notConfirmed']++;}}
require $root.'/core/components/bitrix24/client.php';$client=null;
try{$client=IsiptoBitrix24Client::fromEnvironment();$client->call('crm.deal.fields',array());$auth=array('status'=>'OK');}
catch(Exception $e){$auth=array('status'=>'FAILED','error'=>$e->getMessage(),'apiError'=>$client?$client->getLastApiError():null);}
echo json_encode(array('checkedUtc'=>gmdate('c'),'readOnly'=>true,'pages'=>$pages,'handler'=>$handler,'sharedFileHashes'=>$shared,'configuration'=>$envConfiguration,'recentDeliveryErrors'=>array_slice($logs,-12),'recentReceiptCountsByUtcDate'=>$receipts,'sharedClientReadOnlyAuth'=>$auth,'submittedForms'=>0,'databaseWrites'=>0,'crmWrites'=>0),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";
