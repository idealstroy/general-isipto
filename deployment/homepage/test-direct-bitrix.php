<?php
require '/var/www/html/core/components/bitrix24/client.php';
$tests=0;
function check($value,$label){global $tests;if(!$value)throw new RuntimeException('FAIL '.$label);$tests++;}
function rejects($fn,$expected){try{$fn();}catch(RuntimeException $e){check($e->getMessage()===$expected,$expected);return;}throw new RuntimeException('Expected exception '.$expected);}
$url='https://isipto.bitrix24.ru/rest/1/FAKE_OFFLINE_TOKEN/';
$calls=0;
$client=new IsiptoBitrix24Client($url.'profile.json',function($u,$p)use(&$calls){$calls++;check(substr($u,-17)==='crm.deal.add.json','method URL');check($p['fields']['TITLE']==='OFFLINE TEST','field mapping');return array('status'=>200,'body'=>'{"result":42}');});
check($client->call('crm.deal.add',array('fields'=>array('TITLE'=>'OFFLINE TEST')))===42,'numeric result');check($calls===1,'no retry');
foreach(array('http://isipto.bitrix24.ru/rest/1/a/','https://example.org/rest/1/a/',$url.'?key=x','https://u:p@isipto.bitrix24.ru/rest/1/a/',$url.'crm.lead.add.json') as $bad)rejects(function()use($bad){new IsiptoBitrix24Client($bad);},'BITRIX_CONFIG_INVALID');
foreach(array(array(200,'{"error":"ACCESS_DENIED"}','BITRIX_API_REJECTED'),array(401,'{"error":"INVALID_CREDENTIALS"}','BITRIX_API_REJECTED'),array(200,'{"error":""}','BITRIX_API_REJECTED'),array(502,'bad gateway','BITRIX_TRANSPORT_UNCERTAIN'),array(200,'not json','BITRIX_TRANSPORT_UNCERTAIN')) as $case){$c=new IsiptoBitrix24Client($url,function()use($case){return array('status'=>$case[0],'body'=>$case[1]);});rejects(function()use($c){$c->call('crm.deal.add',array('fields'=>array()));},$case[2]);}
// Test hook validation and deduplication without permitting outbound calls.
define('MODX_CORE_PATH','/var/www/html/core/');
class modX {const LOG_LEVEL_INFO=3;const LOG_LEVEL_ERROR=1;public function log($l,$m){}}
class TestHook {public $v;public $errors=array();function __construct($v){$this->v=$v;}function getValue($k){return isset($this->v[$k])?$this->v[$k]:null;}function addError($k,$m){$this->errors[$k]=$m;}}
function runHook($v,$form=null){$hook=new TestHook($v);$modx=new modX();if($form!==null){$formit=new stdClass();$formit->config=array('form'=>$form);}$ok=include '/var/www/html/assets/snippets/bitrix24_hooks.php';return array($ok,$hook->errors);}
$_SESSION=array();$_COOKIE=array('anti_bot_cookie'=>'1');putenv('BITRIX24_ENV_FILE=/nonexistent-offline-test');
$valid=array('fio'=>'OFFLINE','phone'=>'+79990000000','agree'=>'on','page'=>'http://localhost:3000/','reason'=>'TEST');
$v=$valid;unset($v['agree']);$r=runHook($v);check($r[0]===false&&isset($r[1]['agree']),'consent required');
foreach(array('website','firstname','lastname','name') as $trap){$v=$valid;$v[$trap]='bot';$r=runHook($v);check($r[0]===false&&isset($r[1]['website']),'honeypot '.$trap);}
$v=$valid;$v['phone']='123';$r=runHook($v);check($r[0]===false&&isset($r[1]['phone']),'phone validation');
$r=runHook($valid);check($r[0]===false,'missing config fails closed');
$fp=hash('sha256',implode('|',array('deal',31,3280,'OFFLINE','79990000000','','http://localhost:3000/','TEST')));
$_SESSION['bitrix24_delivery'][$fp]=array('time'=>time(),'id'=>42,'routingVerified'=>true);$r=runHook($valid);check($r[0]===true,'successful duplicate suppressed');
$_SESSION['bitrix24_delivery'][$fp]=array('time'=>time());$r=runHook($valid);check($r[0]===false,'uncertain duplicate not resent');
$callback=$valid;unset($callback['fio']);$callbackFp=hash('sha256',implode('|',array('deal',31,3280,'Обратный звонок','79990000000','','http://localhost:3000/','TEST')));$_SESSION['bitrix24_delivery'][$callbackFp]=array('time'=>time(),'id'=>42,'routingVerified'=>true);
$r=runHook($callback,'form_phone_tpl');check($r[0]===true,'phone-only native callback remains supported');$r=runHook($callback,'form_tpl1');check($r[0]===false&&isset($r[1]['phone']),'named forms still require name');
$dir=sys_get_temp_dir().'/isipto-bitrix-unit-'.getmypid();$key=hash('sha256','OFFLINE');$receipt=new IsiptoBitrix24Receipt($dir,$key);$receipt->write(array('time'=>1,'pending'=>true));check($receipt->read()['pending']===true,'pending receipt persisted');
rejects(function()use($dir,$key){new IsiptoBitrix24Receipt($dir,$key);},'BITRIX_RECEIPT_UNAVAILABLE');$receipt->write(array('time'=>2,'id'=>42));$receipt->close();$r=new IsiptoBitrix24Receipt($dir,$key);check($r->read()['id']===42,'accepted receipt persisted');$r->close();unlink($dir.'/'.$key.'.json');rmdir($dir);
echo json_encode(array('passed'=>$tests,'outboundRequests'=>0,'crmLeadsCreated'=>0))."\n";
