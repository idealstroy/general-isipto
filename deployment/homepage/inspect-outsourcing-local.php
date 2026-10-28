<?php
if (PHP_SAPI !== 'cli') exit(2);
require '/var/www/html/core/config/config.inc.php';
if ($database_server !== 'db') exit(2);
$pdo = new PDO('mysql:host='.$database_server.';dbname='.$dbase.';charset=utf8', $database_user, $database_password, array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$q = $pdo->prepare('SELECT id, pagetitle, uri, template, content FROM '.$table_prefix.'site_content WHERE uri=? AND context_key=?');
$q->execute(array('autsorsing-pto/', 'web'));
$resource = $q->fetch(PDO::FETCH_ASSOC);
$q = $pdo->prepare('SELECT * FROM '.$table_prefix.'site_templates WHERE id=?');
$q->execute(array($resource['template']));
$template = $q->fetch(PDO::FETCH_ASSOC);
$q = $pdo->prepare('SELECT id, uri, context_key FROM '.$table_prefix.'site_content WHERE template=? ORDER BY id');
$q->execute(array($resource['template']));
$consumers = $q->fetchAll(PDO::FETCH_ASSOC);
$q = $pdo->prepare('SELECT * FROM '.$table_prefix.'site_tmplvar_templates WHERE templateid=? ORDER BY tmplvarid');
$q->execute(array($resource['template']));
$links = $q->fetchAll(PDO::FETCH_ASSOC);
$hashes = array();
$newTemplateId = $template['static_file'] === 'assets/templates/outsourcing-solution.html' ? $template['id'] : 0;
foreach (array('site_content','site_templates','site_htmlsnippets','site_tmplvars','site_tmplvar_templates','site_tmplvar_contentvalues','site_snippets','site_plugins','site_plugin_events') as $table) {
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    $query = $pdo->query('SELECT * FROM '.$table_prefix.$table.' ORDER BY 1,2');
    $allHash = hash_init('sha256'); $otherHash = hash_init('sha256'); $existingHash = hash_init('sha256');
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $encoded = json_encode($row)."\n";
        hash_update($allHash, $encoded);
        $created = ($table === 'site_templates' && $newTemplateId && $row['id'] == $newTemplateId)
            || ($table === 'site_tmplvar_templates' && $newTemplateId && $row['templateid'] == $newTemplateId)
            || ($table === 'site_htmlsnippets' && in_array($row['name'], array('outsourcing_form_cost','outsourcing_form_time','outsourcing_form_footer'), true));
        if (!$created) hash_update($existingHash, $encoded);
        if ($table === 'site_content') {
            if ($row['id'] != $resource['id']) hash_update($otherHash, $encoded);
            else $resource = $row;
        }
    }
    $query->closeCursor();
    $hashes[$table] = hash_final($allHash);
    $hashes['existing_'.$table] = hash_final($existingHash);
    if ($table === 'site_content') $hashes['other_resources'] = hash_final($otherHash);
}
echo json_encode(array('resource'=>$resource, 'template'=>$template, 'consumers'=>$consumers, 'tvLinks'=>$links, 'hashes'=>$hashes), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";
