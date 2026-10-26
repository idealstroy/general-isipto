<?php
if (PHP_SAPI !== 'cli') exit(2);
require '/var/www/html/core/config/config.inc.php';
if ($database_server !== 'db') exit(2);
$pdo = new PDO('mysql:host='.$database_server.';dbname='.$dbase.';charset=utf8', $database_user, $database_password, array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$result = array();
foreach (array('site_content', 'site_templates', 'site_tmplvar_contentvalues', 'site_htmlsnippets', 'site_snippets', 'site_plugins', 'site_plugin_events') as $table) {
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    $query = $pdo->query('SELECT * FROM `'.$table_prefix.$table.'` ORDER BY 1');
    $hash = hash_init('sha256');
    $count = 0;
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        hash_update($hash, json_encode($row)."\n");
        $count++;
    }
    $query->closeCursor();
    $result[$table] = array('rows'=>$count, 'sha256'=>hash_final($hash));
}
echo json_encode($result, JSON_PRETTY_PRINT)."\n";
