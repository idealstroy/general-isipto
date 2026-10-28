<?php
// Narrow, CLI-only patch of the existing shared hook and AjaxForm adapter. No CRM calls or secret changes.
if(PHP_SAPI!=='cli'||PHP_VERSION_ID<70300)exit(2);
$action=isset($argv[1])?$argv[1]:'';$root='/home/i/idealstroy/isipto.ru/public_html';$stage=realpath(__DIR__);
if(!in_array($action,array('inspect','apply','verify','rollback'),true)||strpos($stage,'/home/i/idealstroy/_codex_releases/crm-ui-')!==0)exit(2);
umask(0077);$spec=json_decode(file_get_contents($stage.'/patch.json'),true);if(!$spec||!preg_match('/^crm-ui-[0-9TZ]+$/',$spec['id']))exit(2);
$paths=array('assets/snippets/bitrix24_hooks.php','core/components/ajaxform/model/ajaxform/ajaxform.class.php');
if(array_keys($spec['files'])!==$paths)exit(2);
require $root.'/core/config/config.inc.php';
$pdo=new PDO('mysql:host='.$database_server.';dbname='.$dbase.';charset=utf8',$database_user,$database_password,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$backup=dirname($root).'/.isipto-crm-fixes/'.$spec['id'];$journal=$backup.'/state.json';$state=is_file($journal)?json_decode(file_get_contents($journal),true):null;
function row(){global $pdo,$table_prefix;$q=$pdo->query("SELECT * FROM {$table_prefix}site_snippets WHERE name='bitrix24_hooks'");$r=$q->fetchAll(PDO::FETCH_ASSOC);if(count($r)!==1||empty($r[0]['static'])||$r[0]['static_file']!=='assets/snippets/bitrix24_hooks.php')throw new Exception('Unexpected shared handler');return $r[0];}
function digest($r){return hash('sha256',json_encode($r));}
function writeJournal(){global $state,$journal;$tmp=$journal.'.tmp';if(file_put_contents($tmp,json_encode($state,JSON_UNESCAPED_UNICODE),LOCK_EX)===false||!chmod($tmp,0600)||!rename($tmp,$journal))throw new Exception('Private journal failed');}
function target($f){global $root,$paths;if(!in_array($f,$paths,true))throw new Exception('Unsafe target');$p=$root.'/'.$f;if(is_link($p)||realpath($p)!==$p)throw new Exception('Unexpected symlink');return $p;}
function retainGroup($p,$g){clearstatcache(true,$p);return filegroup($p)===$g||chgrp($p,$g);}
function install($source,$dest,$mode){global $spec,$root;$tmp=$dest.'.'.$spec['id'].'.partial';if(file_exists($tmp))throw new Exception('Unexpected partial');
    if(!copy($source,$tmp)||hash_file('sha256',$tmp)!==hash_file('sha256',$source)||!retainGroup($tmp,filegroup($root.'/index.php'))||!chmod($tmp,$mode)||!rename($tmp,$dest)){
        if(is_file($tmp)&&!is_link($tmp)&&(fileowner($tmp)===posix_geteuid()||fileowner($tmp)===fileowner($root.'/index.php')))unlink($tmp);throw new Exception('Atomic install failed');
    }
}
function refresh(){global $root;define('MODX_API_MODE',true);require $root.'/config.core.php';require_once MODX_CORE_PATH.'model/modx/modx.class.php';$modx=new modX();$modx->initialize('web');$result=array();$modx->cacheManager->refresh(array('resource'=>array('contexts'=>array('web')),'db'=>array(),'scripts'=>array()),$result);foreach($result as $key=>$ok)if($ok===false){$partition=$modx->getOption('cache_'.$key.'_key',null,$key);if(file_exists($modx->cacheManager->getCachePath().$partition))throw new Exception('Cache refresh failed');}}
function rollback($partial=false){global $state,$spec,$paths,$backup,$pdo,$table_prefix;
    if(!$state||!isset($state['snippetBefore']))throw new Exception('Rollback snapshot missing');
    foreach($paths as $f){$sha=hash_file('sha256',target($f));if($sha!==$spec['files'][$f]['before']&&$sha!==$spec['files'][$f]['after'])throw new Exception('Later file edits; manual rollback required');}
    $r=row();$expected=$state['snippetBefore'];$expected['snippet']=preg_replace('/^<\?php\s*/','',file_get_contents(__DIR__.'/payload/'.$paths[0]));
    if(digest($r)!==digest($state['snippetBefore'])&&digest($r)!==digest($expected))throw new Exception('Later handler edits');
    foreach($paths as $f)install($backup.'/files/'.$f,target($f),$state['modes'][$f]);
    $q=$pdo->prepare('UPDATE '.$table_prefix.'site_snippets SET snippet=? WHERE id=?');$q->execute(array($state['snippetBefore']['snippet'],$r['id']));
    refresh();$state['phase']='rolled-back';writeJournal();
}
try{
    if($action==='rollback'){rollback();echo "{\"phase\":\"rolled-back\"}\n";exit;}
    foreach($paths as $f)if(!is_file($stage.'/payload/'.$f)||hash_file('sha256',$stage.'/payload/'.$f)!==$spec['files'][$f]['after'])throw new Exception('Payload mismatch');
    $r=row();
    if($action==='verify'){
        foreach($paths as $f)if(hash_file('sha256',target($f))!==$spec['files'][$f]['after'])throw new Exception('Published file mismatch');
        $expected=$state['snippetBefore'];$expected['snippet']=preg_replace('/^<\?php\s*/','',file_get_contents($stage.'/payload/'.$paths[0]));if(digest($r)!==digest($expected))throw new Exception('Published shared handler mismatch');
        echo json_encode(array('phase'=>'verified','sharedSnippetId'=>(int)$r['id'],'webhookChanged'=>false,'crmCalls'=>0))."\n";exit;
    }
    foreach($paths as $f)if(hash_file('sha256',target($f))!==$spec['files'][$f]['before'])throw new Exception('Unapproved existing file difference: '.$f);
    if($action==='inspect'){echo json_encode(array('mode'=>'READ_ONLY','ready'=>true,'files'=>count($paths),'sharedSnippetId'=>(int)$r['id'],'crmCalls'=>0))."\n";exit;}
    if($state)throw new Exception('Existing patch journal');
    if(is_link(dirname($backup))||is_link($backup))throw new Exception('Unsafe private backup');
    if(!is_dir($backup)&&!mkdir($backup,0700,true))throw new Exception('Private backup creation failed');
    $state=array('phase'=>'backing-up','createdUtc'=>gmdate('c'),'snippetBefore'=>$r,'modes'=>array());
    foreach($paths as $f){$dest=$backup.'/files/'.$f;if(!is_dir(dirname($dest))&&!mkdir(dirname($dest),0700,true))throw new Exception('Backup directory failed');if(!copy(target($f),$dest)||hash_file('sha256',$dest)!==$spec['files'][$f]['before']||!chmod($dest,0600))throw new Exception('Verified file backup failed');$state['modes'][$f]=fileperms(target($f))&0777;}
    writeJournal();$state['phase']='applying';writeJournal();
    foreach($paths as $f)install($stage.'/payload/'.$f,target($f),$state['modes'][$f]);
    if(digest(row())!==digest($r))throw new Exception('Handler changed concurrently');
    $content=preg_replace('/^<\?php\s*/','',file_get_contents($stage.'/payload/'.$paths[0]));$q=$pdo->prepare('UPDATE '.$table_prefix.'site_snippets SET snippet=? WHERE id=?');$q->execute(array($content,$r['id']));
    refresh();$state['phase']='applied';writeJournal();
    echo json_encode(array('phase'=>'applied','files'=>2,'sharedSnippetId'=>(int)$r['id'],'newHandlers'=>0,'webhookChanged'=>false,'crmCalls'=>0,'rollbackAvailable'=>true))."\n";
}catch(Throwable $e){$rolled=false;if($state&&$state['phase']==='applying'){try{rollback(true);$rolled=true;}catch(Throwable $other){fwrite(STDERR,"Rollback requires inspection\n");}}
    fwrite(STDERR,json_encode(array('error'=>$e->getMessage(),'automaticallyRolledBack'=>$rolled))."\n");exit(1);
}
