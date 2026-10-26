<?php
require '/var/www/html/config.core.php';require MODX_CORE_PATH.'model/modx/modx.class.php';$m=new modX();$m->initialize('web');
$out=array();foreach(array('/var/www/html','/var/www/html/assets/components/ajaxform') as $cwd){chdir($cwd);foreach(array('roistat_hooks','bitrix24_hooks') as $name){$s=$m->getObject('modSnippet',array('name'=>$name));$path=$s->getSourceFile();$out[]=array('cwd'=>$cwd,'name'=>$name,'source'=>$s->get('source'),'staticFile'=>$s->get('static_file'),'resolved'=>$path,'exists'=>is_file($path),'dbLength'=>strlen($s->get('snippet')));}}
echo json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
