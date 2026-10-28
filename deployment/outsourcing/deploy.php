<?php
/** CLI-only page publication. No CRM calls, no full database import, no shared-template edits. */
if (PHP_SAPI !== 'cli' || PHP_VERSION_ID < 70300) { fwrite(STDERR,"Unsupported CLI environment\n"); exit(2); }
$action = isset($argv[1]) ? $argv[1] : '';
$root = isset($argv[2]) ? realpath($argv[2]) : false;
if (!in_array($action, array('audit','inspect','apply','rollback'), true) || !$root || !is_file($root.'/config.core.php')) { fwrite(STDERR,json_encode(array('error'=>'Invalid action or site root','action'=>$action,'root'=>$root))."\n"); exit(2); }
if (function_exists('posix_geteuid') && posix_geteuid() === 0) exit(2);
umask(0077);
require $root.'/core/config/config.inc.php';
$pdo = new PDO('mysql:host='.$database_server.';dbname='.$dbase.';charset=utf8', $database_user, $database_password, array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$release = realpath(__DIR__); $state = null; $spec = null; $modx = null;
$templateName = 'Аутсорсинг ПТО — управляемая служба';
$templatePath = 'assets/templates/outsourcing-solution.html';
$chunkFiles = array('outsourcing_form_cost'=>'assets/chunks/outsourcing-pto/form-cost.html','outsourcing_form_time'=>'assets/chunks/outsourcing-pto/form-time.html','outsourcing_form_footer'=>'assets/chunks/outsourcing-pto/form-footer.html');
$paths = array_merge(array($templatePath), array_values($chunkFiles), array('images/outsourcing-pto/result-clock.png','images/outsourcing-pto/pto-ai-monitor.png','images/outsourcing-pto/form-portrait-extended.png','images/outsourcing-pto/schedule-portrait-hd.png'));

function rows($table, $where, $values=array(), $order='id') {
    global $pdo,$table_prefix;
    $q=$pdo->prepare('SELECT * FROM `'.$table_prefix.$table.'` WHERE '.$where.' ORDER BY '.$order); $q->execute($values); return $q->fetchAll(PDO::FETCH_ASSOC);
}
function digest($value) { return hash('sha256',json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)); }
function filePath($relative) {
    global $root,$paths;
    if (!in_array($relative,$paths,true)) throw new RuntimeException('Path is not in the page allowlist');
    $p=$root.'/'.$relative; $parent=dirname($p);
    while (!file_exists($parent)) $parent=dirname($parent);
    $real=realpath($parent);
    if (is_link($p) || !$real || ($real!==$root && strpos($real,$root.'/')!==0)) throw new RuntimeException('Unsafe file parent');
    // Reject symlinks even if they point back inside the document root.
    for ($a=dirname($p); $a!==$root; $a=dirname($a)) if (is_link($a)) throw new RuntimeException('Symlink ancestor');
    return $p;
}
function sourceBase($id) {
    if (!$id) return '';
    $r=rows('media_sources','id=?',array($id));
    if (count($r)!==1 || $r[0]['class_key']!=='sources.modFileMediaSource') throw new RuntimeException('Unsupported file source');
    $props=json_decode($r[0]['properties'],true);
    if (!is_array($props)) $props=@unserialize($r[0]['properties'],array('allowed_classes'=>false));
    if (!is_array($props)) throw new RuntimeException('Unsupported media source properties');
    return isset($props['basePath']['value']) ? $props['basePath']['value'] : '';
}
function elementInfo($row,$field) {
    global $root;
    $code=$row[$field]; $effective=null;
    if (!empty($row['static'])) {
        if (sourceBase($row['source'])!=='') throw new RuntimeException('Static source is not rooted at the site');
        $relative=$row['static_file'];
        if (strpos($relative,'..')!==false || strpos($relative,'\\')!==false || substr($relative,0,1)==='/') throw new RuntimeException('Unsafe existing static path');
        $effective=realpath($root.'/'.$relative);
        if (!$effective || strpos($effective,$root.'/')!==0 || !is_readable($effective)) throw new RuntimeException('Missing static dependency');
        $code=file_get_contents($effective);
    }
    return array('rowHash'=>digest($row),'codeHash'=>hash('sha256',$code),'static'=>(int)$row['static'],'staticFile'=>$row['static_file'],'source'=>(int)$row['source']);
}
function targetRows() {
    $rs=rows('site_content','uri=? AND context_key=? AND deleted=0',array('autsorsing-pto/','web'));
    if (count($rs)!==1) throw new RuntimeException('Ambiguous outsourcing resource');
    $ts=rows('site_templates','id=?',array($rs[0]['template']));
    if (count($ts)!==1) throw new RuntimeException('Template missing');
    return array($rs[0],$ts[0]);
}
function audit() {
    global $root,$pdo,$table_prefix,$paths,$templateName,$chunkFiles;
    list($r,$t)=targetRows();
    $tv=rows('site_tmplvar_templates','templateid=?',array($t['id']),'tmplvarid');
    $tvValues=rows('site_tmplvar_contentvalues','contentid=?',array($r['id']));
    $deps=array();
    foreach (array('AjaxForm','FormIt','FormItSaveForm','bitrix24_hooks') as $name) {
        $a=rows('site_snippets','name=?',array($name));
        if (count($a)!==1) throw new RuntimeException('Missing or duplicate form handler: '.$name);
        $deps['snippet:'.$name]=elementInfo($a[0],'snippet');
    }
    foreach (array('header_new','footer_new','form_tpl1') as $name) {
        $a=rows('site_htmlsnippets','name=?',array($name));
        if (count($a)!==1) throw new RuntimeException('Missing or duplicate shared chunk: '.$name);
        $deps['chunk:'.$name]=elementInfo($a[0],'snippet');
    }
    $fileHashes=array(); foreach ($paths as $p) { $f=filePath($p); $fileHashes[$p]=is_file($f)?hash_file('sha256',$f):null; }
    $collisions=array('template'=>count(rows('site_templates','templatename=?',array($templateName))));
    foreach ($chunkFiles as $name=>$file) $collisions[$name]=count(rows('site_htmlsnippets','name=?',array($name)));
    $consumers=rows('site_content','template=?',array($t['id']));
    $list=array(); foreach ($consumers as $consumer) $list[]=array('id'=>(int)$consumer['id'],'context'=>$consumer['context_key'],'uri'=>$consumer['uri']);
    $engines=$pdo->query("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('{$table_prefix}site_content','{$table_prefix}site_templates','{$table_prefix}site_htmlsnippets','{$table_prefix}site_tmplvar_templates') ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_ASSOC);
    return array('root'=>$root,'resourceId'=>(int)$r['id'],'resourceHash'=>digest($r),'templateId'=>(int)$t['id'],'template'=>elementInfo($t,'content'),'templateTVs'=>$tv,'resourceTVHash'=>digest($tvValues),'dependencies'=>$deps,'files'=>$fileHashes,'nameCollisions'=>$collisions,'consumers'=>$list,'engines'=>$engines);
}
function bootstrap() {
    global $modx,$root;
    if ($modx) return;
    define('MODX_API_MODE',true); require $root.'/config.core.php'; require_once MODX_CORE_PATH.'model/modx/modx.class.php';
    $modx=new modX(); $modx->initialize('web');
}
function refreshCache() {
    global $modx,$root;
    bootstrap();
    $results=array(); $modx->cacheManager->refresh(array('resource'=>array('contexts'=>array('web')),'db'=>array(),'scripts'=>array()),$results);
    foreach ($results as $partition=>$ok) if ($ok===false) {
        $key=$modx->getOption('cache_'.$partition.'_key',null,$partition);
        if (file_exists($modx->cacheManager->getCachePath().$key)) throw new RuntimeException('Cache refresh failed: '.$partition);
    }
    // Preserve the hosting web-PHP group access, as in the validated homepage deployment.
    $cache=realpath($root.'/core/cache');
    if ($cache!==$root.'/core/cache') throw new RuntimeException('Unexpected cache directory');
    $group=filegroup($root.'/index.php'); $uid=posix_geteuid();
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cache,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST,RecursiveIteratorIterator::CATCH_GET_CHILD);
    foreach ($it as $e) if (!$e->isLink() && $e->getOwner()===$uid) {
        if ((filegroup($e->getPathname())!==$group && !chgrp($e->getPathname(),$group)) || !chmod($e->getPathname(),$e->isDir()?0770:0660)) throw new RuntimeException('Cache permissions failed');
    }
}
function saveJournal() {
    global $state,$journal;
    $tmp=$journal.'.tmp';
    if (file_put_contents($tmp,json_encode($state,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),LOCK_EX)===false || !chmod($tmp,0600) || !rename($tmp,$journal)) throw new RuntimeException('Private journal write failed');
}
function insertRow($table,$row) {
    global $pdo,$table_prefix;
    $keys=array_keys($row); $q=$pdo->prepare('INSERT INTO `'.$table_prefix.$table.'` (`'.implode('`,`',$keys).'`) VALUES ('.implode(',',array_fill(0,count($keys),'?')).')');
    $q->execute(array_values($row)); return (int)$pdo->lastInsertId();
}
function rootSource() {
    foreach (rows('media_sources','class_key=?',array('sources.modFileMediaSource')) as $s) if (sourceBase($s['id'])==='') return $s['id'];
    throw new RuntimeException('Root filesystem source not found');
}
function retainGroup($file,$group) {
    clearstatcache(true,$file);
    return filegroup($file)===$group || chgrp($file,$group);
}
function makeDirectory($dir) {
    global $root,$state;
    if (is_dir($dir)) return;
    if (strpos($dir,$root.'/')!==0) throw new RuntimeException('Unsafe directory');
    makeDirectory(dirname($dir));
    $state['createdDirectories'][]=$dir; saveJournal();
    if (!mkdir($dir,0770) || !retainGroup($dir,filegroup($root.'/index.php')) || !chmod($dir,0770)) throw new RuntimeException('Directory permissions failed');
}
function rollbackPage($partial=false) {
    global $state,$pdo,$table_prefix,$spec,$root,$templateName,$templatePath,$chunkFiles,$journal;
    if (!$state || !isset($state['resourceBefore'])) throw new RuntimeException('No private rollback state');
    $templates=rows('site_templates','templatename=?',array($templateName));
    if (count($templates)>1) throw new RuntimeException('Ambiguous rollback template');
    $t=$templates?$templates[0]:null;
    if ($t && ($t['static_file']!==$templatePath || hash('sha256',$t['content'])!==$spec['files'][$templatePath])) throw new RuntimeException('Template changed since release');
    if ($t) foreach (rows('site_content','template=?',array($t['id'])) as $r) if ($r['id']!=$state['resourceBefore']['id']) throw new RuntimeException('Template used by another page');
    $r=rows('site_content','id=?',array($state['resourceBefore']['id']));
    if (count($r)!==1) throw new RuntimeException('Rollback resource missing');
    $current=$r[0]; $old=$state['resourceBefore'];
    $currentTemplate=$current['template']; $current['template']=$old['template'];
    if (digest($current)!==digest($old) || ($currentTemplate!=$old['template'] && (!$t || $currentTemplate!=$t['id']))) throw new RuntimeException('Later resource changes; manual rollback required');
    foreach ($chunkFiles as $name=>$file) {
        $a=rows('site_htmlsnippets','name=?',array($name));
        if (count($a)>1 || ($a && ($a[0]['static_file']!==$file || hash('sha256',$a[0]['snippet'])!==$spec['files'][$file]))) throw new RuntimeException('Later chunk changes; manual rollback required');
    }
    foreach ($spec['files'] as $f=>$sha) { $p=filePath($f); if (is_file($p) && hash_file('sha256',$p)!==$sha) throw new RuntimeException('Later file changes; manual rollback required'); }
    $q=$pdo->prepare('UPDATE '.$table_prefix.'site_content SET template=? WHERE id=? AND template=?'); $q->execute(array($old['template'],$old['id'],$currentTemplate));
    foreach ($chunkFiles as $name=>$file) { $q=$pdo->prepare('DELETE FROM '.$table_prefix.'site_htmlsnippets WHERE name=? AND static_file=?'); $q->execute(array($name,$file)); }
    if ($t) {
        $q=$pdo->prepare('DELETE FROM '.$table_prefix.'site_tmplvar_templates WHERE templateid=?');$q->execute(array($t['id']));
        $q=$pdo->prepare('DELETE FROM '.$table_prefix.'site_templates WHERE id=?');$q->execute(array($t['id']));
    }
    foreach ($spec['files'] as $f=>$sha) { $p=filePath($f); if (is_file($p) && !unlink($p)) throw new RuntimeException('Could not remove released file'); }
    foreach (array_reverse($state['createdDirectories']) as $dir) if (is_dir($dir) && count(scandir($dir))===2 && !rmdir($dir)) throw new RuntimeException('Could not remove empty release directory');
    refreshCache(); $state['phase']='rolled-back';$state['rollbackUtc']=gmdate('c');saveJournal();
}
try {
    if ($action==='audit') { echo json_encode(array('mode'=>'READ_ONLY','phpCli'=>PHP_VERSION,'snapshot'=>audit(),'crmWrites'=>0),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n"; exit; }
    if ($release===$root || strpos($release,$root.'/')===0 || is_link(__DIR__)) throw new RuntimeException('Release must be outside public_html');
    $spec=json_decode(file_get_contents($release.'/release.json'),true);
    if (!$spec || !preg_match('/^outsourcing-[0-9TZ-]+$/',$spec['id']) || $spec['targetRoot']!==$root || array_keys($spec['files'])!==$paths) throw new RuntimeException('Invalid release or target');
    $backup=dirname($root).'/.isipto-release-backups/'.$spec['id']; $journal=$backup.'/state.json';
    if (is_link(dirname($backup)) || is_link($backup)) throw new RuntimeException('Unsafe backup directory');
    if (is_file($journal)) $state=json_decode(file_get_contents($journal),true);
    if ($action==='rollback') { rollbackPage(); echo json_encode(array('phase'=>'rolled-back','crmWrites'=>0))."\n";exit; }
    if (audit()!==$spec['baseline']) throw new RuntimeException('Hosting changed since the read-only audit; rebuild baseline after review');
    foreach ($spec['files'] as $file=>$sha) {
        if (!is_file($release.'/payload/'.$file) || hash_file('sha256',$release.'/payload/'.$file)!==$sha) throw new RuntimeException('Payload mismatch: '.$file);
        if (file_exists(filePath($file))) throw new RuntimeException('Target file already exists: '.$file);
    }
    foreach ($spec['baseline']['nameCollisions'] as $count) if ($count!==0) throw new RuntimeException('Target MODX name already exists');
    $body=file_get_contents($release.'/payload/'.$templatePath);
    if (strpos($body,'_preview/')!==false || substr_count($body,'&hooks=`FormItSaveForm,bitrix24_hooks`')!==3) throw new RuntimeException('Invalid form wiring');
    rootSource();
    if ($action==='inspect') { echo json_encode(array('mode'=>'READ_ONLY','readyForApplyAfterFreshBackup'=>true,'files'=>count($paths),'resourceId'=>$spec['baseline']['resourceId'],'otherTemplateConsumers'=>count($spec['baseline']['consumers'])-1,'phpCli'=>PHP_VERSION,'crmWrites'=>0))."\n";exit; }
    if ($state) throw new RuntimeException('Existing release journal; refusing repeated apply');
    $offline=isset($argv[3]) && $argv[3]==='--offline-rehearsal';
    if ($offline) {
        if ($database_server!=='isipto-direct-rehearsal-db' || $root!=='/var/www/html') throw new RuntimeException('Offline flag is restricted to the isolated fixture');
    } else {
        $receipt=is_file($release.'/backup-confirmation.json')?json_decode(file_get_contents($release.'/backup-confirmation.json'),true):null;
        if (!$receipt || empty($receipt['fullFilesBackupVerified']) || empty($receipt['fullDatabaseBackupVerified']) || $receipt['targetRoot']!==$root || $receipt['releaseId']!==$spec['id'] || !isset($receipt['verifiedUtc']) || time()-strtotime($receipt['verifiedUtc'])>86400 || strtotime($receipt['verifiedUtc'])>time()+300 || !preg_match('/^[a-f0-9]{64}$/',$receipt['filesSha256']) || !preg_match('/^[a-f0-9]{64}$/',$receipt['databaseSha256'])) throw new RuntimeException('Fresh verified full hosting backups required');
    }
    list($r,$t)=targetRows();
    if (!is_dir($backup) && !mkdir($backup,0700,true)) throw new RuntimeException('Private backup creation failed');
    if (!chmod($backup,0700)) throw new RuntimeException('Backup permissions failed');
    $state=array('phase'=>'backed-up','createdUtc'=>gmdate('c'),'resourceBefore'=>$r,'templateBefore'=>$t,'tvLinksBefore'=>$spec['baseline']['templateTVs'],'createdDirectories'=>array());saveJournal();
    $state['phase']='applying';saveJournal();
    foreach ($spec['files'] as $f=>$sha) {
        $p=filePath($f);makeDirectory(dirname($p));$tmp=$p.'.'.$spec['id'].'.partial';
        if (file_exists($tmp)) throw new RuntimeException('Unexpected existing partial file: '.$f);
        if (!copy($release.'/payload/'.$f,$tmp) || hash_file('sha256',$tmp)!==$sha || !retainGroup($tmp,filegroup($root.'/index.php')) || !chmod($tmp,0660) || !rename($tmp,$p)) {
            if (is_file($tmp) && !is_link($tmp) && (fileowner($tmp)===posix_geteuid() || fileowner($tmp)===fileowner($root.'/index.php'))) unlink($tmp);
            throw new RuntimeException('Atomic file install failed: '.$f);
        }
    }
    $source=rootSource();unset($t['id']);$t['templatename']=$templateName;$t['static']=1;$t['static_file']=$templatePath;$t['source']=$source;$t['content']=$body;
    $id=insertRow('site_templates',$t);$state['newTemplateId']=$id;saveJournal();
    foreach ($state['tvLinksBefore'] as $link) { unset($link['id']);$link['templateid']=$id;insertRow('site_tmplvar_templates',$link); }
    foreach ($chunkFiles as $name=>$file) insertRow('site_htmlsnippets',array('name'=>$name,'description'=>'Форма страницы Аутсорсинг ПТО','snippet'=>file_get_contents($release.'/payload/'.$file),'static'=>1,'static_file'=>$file,'source'=>$source));
    // Switch only after every dependency exists; conditional update also works on legacy MyISAM.
    if (digest(rows('site_content','id=?',array($r['id']))[0])!==digest($r)) throw new RuntimeException('Resource edited during installation');
    $q=$pdo->prepare('UPDATE '.$table_prefix.'site_content SET template=? WHERE id=? AND template=?');$q->execute(array($id,$r['id'],$r['template']));
    if ($q->rowCount()!==1) throw new RuntimeException('Resource binding changed');
    refreshCache();$state['phase']='applied';$state['appliedUtc']=gmdate('c');saveJournal();
    echo json_encode(array('phase'=>'applied','resourceId'=>(int)$r['id'],'newTemplateId'=>$id,'rollbackAvailable'=>true,'databaseImported'=>false,'crmWrites'=>0))."\n";
} catch (Throwable $e) {
    $rolledBack=false;
    if ($state && $state['phase']==='applying') { try { rollbackPage(true);$rolledBack=true; } catch (Throwable $rollbackError) { fwrite(STDERR,"Automatic rollback incomplete; inspect private journal\n"); } }
    fwrite(STDERR,json_encode(array('error'=>$e->getMessage(),'automaticallyRolledBack'=>$rolledBack),JSON_UNESCAPED_UNICODE)."\n");exit(1);
}
