<?php
// SSH stdin only; SELECT/file hashes only, never bootstrap MODX or contact CRM.
if (PHP_SAPI!=='cli' || !isset($settings)) exit(2);
$root=realpath($settings['targetRoot']);
if ($root!=='/home/i/idealstroy/isipto.ru/public_html') exit(2);
require $root.'/core/config/config.inc.php';
$pdo=new PDO('mysql:host='.$database_server.';dbname='.$dbase.';charset=utf8',$database_user,$database_password,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$spec=$settings['spec'];$baseline=$spec['baseline'];$errors=array();
$q=$pdo->prepare('SELECT * FROM '.$table_prefix.'site_content WHERE id=?');$q->execute(array($baseline['resourceId']));$resource=$q->fetch(PDO::FETCH_ASSOC);
$q=$pdo->prepare('SELECT * FROM '.$table_prefix.'site_templates WHERE templatename=?');$q->execute(array('Аутсорсинг ПТО — управляемая служба'));$newTemplates=$q->fetchAll(PDO::FETCH_ASSOC);
$newId=count($newTemplates)===1?$newTemplates[0]['id']:null;
$newChunks=array('outsourcing_form_cost','outsourcing_form_time','outsourcing_form_footer');
$hashes=array();
foreach (array('site_content','site_templates','site_htmlsnippets','site_tmplvar_templates','site_tmplvars','site_tmplvar_contentvalues','site_snippets','site_plugins','site_plugin_events','system_settings','context','context_setting','media_sources') as $table) {
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,false);
    $q=$pdo->query('SELECT * FROM '.$table_prefix.$table.' ORDER BY 1,2');$hash=hash_init('sha256');
    while ($row=$q->fetch(PDO::FETCH_ASSOC)) {
        if ($table==='site_content' && $row['id']==$baseline['resourceId']) $row['template']=(string)$baseline['templateId'];
        if ($table==='site_templates' && $newId && $row['id']==$newId) continue;
        if ($table==='site_htmlsnippets' && in_array($row['name'],$newChunks,true)) continue;
        if ($table==='site_tmplvar_templates' && $newId && $row['templateid']==$newId) continue;
        hash_update($hash,json_encode($row)."\n");
    }
    $q->closeCursor();$hashes[$table]=hash_final($hash);
}
$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,true);
if ($settings['mode']==='after') {
    if (!$newId || $resource['template']!=$newId) $errors[]='Resource is not using the new dedicated template';
    if ($newId) {
        $q=$pdo->prepare('SELECT id FROM '.$table_prefix.'site_content WHERE template=? ORDER BY id');$q->execute(array($newId));
        if ($q->fetchAll(PDO::FETCH_COLUMN)!==array((string)$baseline['resourceId'])) $errors[]='New template has unexpected consumers';
        $q=$pdo->prepare('SELECT * FROM '.$table_prefix.'site_tmplvar_templates WHERE templateid=? ORDER BY tmplvarid');$q->execute(array($newId));$links=$q->fetchAll(PDO::FETCH_ASSOC);
        foreach ($links as &$link) $link['templateid']=(string)$baseline['templateId'];unset($link);
        if ($links!==$baseline['templateTVs']) $errors[]='TV associations changed';
    }
    foreach ($spec['files'] as $file=>$sha) if (!is_file($root.'/'.$file) || hash_file('sha256',$root.'/'.$file)!==$sha) $errors[]='File mismatch: '.$file;
    foreach ($newChunks as $name) {
        $q=$pdo->prepare('SELECT * FROM '.$table_prefix.'site_htmlsnippets WHERE name=?');$q->execute(array($name));$chunks=$q->fetchAll(PDO::FETCH_ASSOC);
        if (count($chunks)!==1 || empty($chunks[0]['static']) || !isset($spec['files'][$chunks[0]['static_file']]) || hash('sha256',$chunks[0]['snippet'])!==$spec['files'][$chunks[0]['static_file']]) $errors[]='Form chunk mismatch: '.$name;
    }
}
if ($settings['mode']==='rollback') {
    if ($resource['template']!=$baseline['templateId'] || $newId) $errors[]='Original resource binding not restored';
    foreach ($spec['files'] as $file=>$sha) if (file_exists($root.'/'.$file)) $errors[]='Released file remains after rollback: '.$file;
}
echo json_encode(array('mode'=>$settings['mode'],'resourceId'=>(int)$resource['id'],'templateId'=>(int)$resource['template'],'normalizedTableHashes'=>$hashes,'errors'=>$errors,'databaseWrites'=>0,'crmWrites'=>0),JSON_UNESCAPED_UNICODE)."\n";
exit($errors?1:0);
