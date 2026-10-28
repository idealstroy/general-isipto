<?php
if (PHP_SAPI!=='cli' || !isset($settings)) exit(2);
$root=realpath($settings['targetRoot']);$stage=$settings['stage'];
if ($root!=='/home/i/idealstroy/isipto.ru/public_html' || !preg_match('~^/home/i/idealstroy/_codex_releases/outsourcing-[0-9TZ-]+$~',$stage)) exit(2);
$spec=json_decode(file_get_contents($stage.'/release.json'),true);
$file=$root.'/assets/templates/outsourcing-solution.html.'.$spec['id'].'.partial';
$journal=dirname($root).'/.isipto-release-backups/'.$spec['id'].'/state.json';
$state=json_decode(file_get_contents($journal),true);
$result=array('phase'=>$state['phase'],'uid'=>posix_geteuid(),'groups'=>posix_getgroups(),'indexGroup'=>filegroup($root.'/index.php'),'templateDirectoryGroup'=>filegroup($root.'/assets/templates'),'partialExists'=>is_file($file),'partialOwner'=>is_file($file)?fileowner($file):null,'partialGroup'=>is_file($file)?filegroup($file):null,'partialHashMatches'=>is_file($file)?hash_file('sha256',$file)===$spec['files']['assets/templates/outsourcing-solution.html']:false,'chgrpAvailable'=>function_exists('chgrp'));
if (isset($settings['cleanup']) && $settings['cleanup']) {
    if ($state['phase']!=='rolled-back' || !is_file($file) || is_link($file) || (fileowner($file)!==posix_geteuid() && fileowner($file)!==fileowner($root.'/index.php')) || !$result['partialHashMatches']) exit(2);
    if (!unlink($file)) exit(2);$result['verifiedPartialRemoved']=true;
}
echo json_encode($result)."\n";
