<?php
// Local-only, transactional registration. No form submissions or hosting access.
if (PHP_SAPI !== 'cli') exit(2);
require '/var/www/html/core/config/config.inc.php';
if ($database_server !== 'db') exit(2);
define('MODX_API_MODE', true);
require '/var/www/html/config.core.php';
require_once MODX_CORE_PATH.'model/modx/modx.class.php';
$modx = new modX();
$modx->initialize('web');
$pdo = new PDO('mysql:host='.$database_server.';dbname='.$dbase.';charset=utf8', $database_user, $database_password, array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
function insertRow($pdo, $table, $row) {
    $keys = array_keys($row);
    $q = $pdo->prepare('INSERT INTO '.$table.' (`'.implode('`,`', $keys).'`) VALUES ('.implode(',', array_fill(0,count($keys),'?')).')');
    $q->execute(array_values($row));
    return $pdo->lastInsertId();
}
$templateId = null; $chunks = array();
$pdo->beginTransaction();
try {
    $r = $pdo->query("SELECT * FROM {$table_prefix}site_content WHERE id=3812 FOR UPDATE")->fetch(PDO::FETCH_ASSOC);
    if (!$r || $r['uri'] !== 'autsorsing-pto/' || $r['context_key'] !== 'web' || $r['template'] != 36) throw new Exception('Unexpected target resource');
    $t = $pdo->query("SELECT * FROM {$table_prefix}site_templates WHERE id=36")->fetch(PDO::FETCH_ASSOC);
    if (!$t || $t['static_file'] !== 'assets/templates/outsourcing.html') throw new Exception('Unexpected original template');
    $name = 'Аутсорсинг ПТО — управляемая служба';
    $q = $pdo->prepare('SELECT id FROM '.$table_prefix.'site_templates WHERE templatename=?');
    $q->execute(array($name));
    if ($q->fetch()) throw new Exception('Target template already exists; refusing duplicate');
    unset($t['id']);
    $t['templatename'] = $name;
    $t['static_file'] = 'assets/templates/outsourcing-solution.html';
    $t['static'] = 1;
    $t['content'] = file_get_contents('/var/www/html/'.$t['static_file']);
    if (!$t['content'] || strpos($t['content'], '_preview/') !== false) throw new Exception('Invalid production template');
    $templateId = insertRow($pdo, $table_prefix.'site_templates', $t);
    $links = $pdo->query("SELECT * FROM {$table_prefix}site_tmplvar_templates WHERE templateid=36")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($links as $link) { unset($link['id']); $link['templateid']=$templateId; insertRow($pdo,$table_prefix.'site_tmplvar_templates',$link); }
    $chunks = array();
    foreach (array('cost','time','footer') as $type) {
        $chunkName = 'outsourcing_form_'.$type;
        $q=$pdo->prepare('SELECT id FROM '.$table_prefix.'site_htmlsnippets WHERE name=?');
        $q->execute(array($chunkName));
        if ($q->fetch()) throw new Exception('Chunk already exists: '.$chunkName);
        $file = 'assets/chunks/outsourcing-pto/form-'.$type.'.html';
        $body = file_get_contents('/var/www/html/'.$file);
        if (!$body || strpos($body,'name="phone"') === false) throw new Exception('Invalid native form');
        $chunks[$chunkName] = insertRow($pdo,$table_prefix.'site_htmlsnippets',array('name'=>$chunkName,'description'=>'Форма страницы Аутсорсинг ПТО','snippet'=>$body,'static'=>1,'static_file'=>$file,'source'=>1));
    }
    $q=$pdo->prepare('UPDATE '.$table_prefix.'site_content SET template=? WHERE id=3812 AND template=36');
    $q->execute(array($templateId));
    if ($q->rowCount() !== 1) throw new Exception('Resource changed concurrently');
    $pdo->commit();
    $modx->cacheManager->refresh(array('resource'=>array('contexts'=>array('web'))));
    echo json_encode(array('resourceId'=>3812,'oldTemplateId'=>36,'newTemplateId'=>$templateId,'chunks'=>$chunks), JSON_PRETTY_PRINT)."\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // Also compensate on legacy non-transactional MODX tables.
    if ($templateId) {
        $q=$pdo->prepare('UPDATE '.$table_prefix.'site_content SET template=36 WHERE id=3812 AND template=?');
        $q->execute(array($templateId));
        foreach ($chunks as $id) { $q=$pdo->prepare('DELETE FROM '.$table_prefix.'site_htmlsnippets WHERE id=?'); $q->execute(array($id)); }
        $q=$pdo->prepare('DELETE FROM '.$table_prefix.'site_tmplvar_templates WHERE templateid=?'); $q->execute(array($templateId));
        $q=$pdo->prepare('DELETE FROM '.$table_prefix.'site_templates WHERE id=?'); $q->execute(array($templateId));
        $modx->cacheManager->refresh(array('resource'=>array('contexts'=>array('web'))));
    }
    fwrite(STDERR, $e->getMessage()."\n"); exit(1);
}
