<?php
require '/var/www/html/core/config/config.inc.php';$p=new PDO('mysql:host='.$database_server.';dbname='.$dbase.';charset=utf8',$database_user,$database_password,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
echo $p->query('SELECT snippet FROM `'.$table_prefix.'site_snippets` WHERE name=\'FormItSaveForm\'')->fetchColumn();
