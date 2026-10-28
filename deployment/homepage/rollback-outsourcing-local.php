<?php
// Explicit rollback of this local publication only. Does not delete any source files.
if (PHP_SAPI !== 'cli') exit(2);
require '/var/www/html/core/config/config.inc.php';
if ($database_server !== 'db') exit(2);
define('MODX_API_MODE', true);
require '/var/www/html/config.core.php';
require_once MODX_CORE_PATH.'model/modx/modx.class.php';
$modx = new modX(); $modx->initialize('web');
$pdo = new PDO('mysql:host='.$database_server.';dbname='.$dbase.';charset=utf8', $database_user, $database_password, array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$q=$pdo->query("SELECT t.id FROM {$table_prefix}site_templates t JOIN {$table_prefix}site_content r ON r.template=t.id WHERE r.id=3812 AND r.context_key='web' AND t.static_file='assets/templates/outsourcing-solution.html'");
$id=$q->fetchColumn();
if (!$id) { fwrite(STDERR,"Expected migrated template not found; no changes\n"); exit(1); }
$q=$pdo->prepare('SELECT COUNT(*) FROM '.$table_prefix.'site_content WHERE template=?'); $q->execute(array($id));
if ($q->fetchColumn()!=1) { fwrite(STDERR,"Template has other consumers; no changes\n"); exit(1); }
$pdo->beginTransaction();
$q=$pdo->prepare('UPDATE '.$table_prefix.'site_content SET template=36 WHERE id=3812 AND template=?');$q->execute(array($id));
$q=$pdo->prepare('DELETE FROM '.$table_prefix.'site_tmplvar_templates WHERE templateid=?');$q->execute(array($id));
$q=$pdo->prepare('DELETE FROM '.$table_prefix.'site_templates WHERE id=?');$q->execute(array($id));
foreach (array('cost','time','footer') as $type) {
    $q=$pdo->prepare('DELETE FROM '.$table_prefix.'site_htmlsnippets WHERE name=? AND static_file=?');
    $q->execute(array('outsourcing_form_'.$type,'assets/chunks/outsourcing-pto/form-'.$type.'.html'));
}
$pdo->commit();
$modx->cacheManager->refresh(array('resource'=>array('contexts'=>array('web'))));
echo "Original local template restored\n";
