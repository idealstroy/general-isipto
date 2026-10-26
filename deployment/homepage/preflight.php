<?php
// CLI-only inspection. No SQL imports, saves, file copies or lead submissions.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (empty($argv[1]) || !is_dir($argv[1])) { fwrite(STDERR,"Usage: php preflight.php /absolute/site/document-root\n"); exit(2); }
$root = rtrim(realpath($argv[1]), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
$release = __DIR__ . DIRECTORY_SEPARATOR;
$spec = json_decode(file_get_contents($release.'release-spec.json'),true);
$manifest = json_decode(file_get_contents($release.'manifest.json'),true);
if (!$spec || !$manifest) { fwrite(STDERR,"Invalid release metadata\n"); exit(2); }
$errors=array(); $warnings=array(); $payload=array();
foreach ($manifest as $entry) {
 $path=$entry['path'];
 if (strpos($path,'..')!==false || preg_match('~^(?:/|[A-Za-z]:)|\\\\~',$path)) { $errors[]='Unsafe path'; continue; }
 $file=$release.'payload/'.$path;
 if (!is_file($file) || hash_file('sha256',$file)!==$entry['sha256']) $errors[]='Payload mismatch: '.$path;
 else $payload[]=$path;
}
if (!is_file($root.'config.core.php')) $errors[]='MODX config.core.php not found';
if ($errors) {echo json_encode(array('errors'=>$errors),JSON_PRETTY_PRINT)."\n";exit(1);}
define('MODX_API_MODE',true);
require $root.'config.core.php';
require MODX_CORE_PATH.'model/modx/modx.class.php';
$modx=new modX();$modx->initialize('web');
$home=$modx->getObject('modResource',(int)$modx->getOption('site_start'));
if (!$home || $home->get('context_key')!=='web') $errors[]='Cannot identify web homepage';
$template=$home?$home->getOne('Template'):null;
foreach ($spec['requiredChunks'] as $name) if (!$modx->getObject('modChunk',array('name'=>$name))) $errors[]='Missing chunk: '.$name;
foreach ($spec['requiredSnippets'] as $name) if (!$modx->getObject('modSnippet',array('name'=>$name))) $errors[]='Missing snippet: '.$name;
foreach ($spec['tvs'] as $name) if (!$modx->getObject('modTemplateVar',array('name'=>$name))) $errors[]='Missing TV: '.$name;
if (!$modx->getObject('modResource',1)) $errors[]='Missing source resource 1 for approved reviews';
if (!$modx->getObject('modTemplateVar',array('name'=>'docs'))) $errors[]='Missing docs TV for approved reviews';
if (!$modx->getOption('blog')) $errors[]='Missing web blog setting';
$source=file_get_contents($release.'payload/'.$spec['template']['static_file']);
preg_match_all('~(?:src|href)="(/(?:assets|images)/[^"?#]+)|url\(["\x27]?(/(?:assets|images)/[^)"\x27?#]+)~',$source,$matches,PREG_SET_ORDER);
foreach ($matches as $match) {
 $path=!empty($match[1])?$match[1]:$match[2];
 if (in_array(ltrim($path,'/'),$spec['files'],true)) continue;
 if (!is_file($root.ltrim($path,'/'))) $errors[]='Missing shared asset: '.$path;
}
foreach (array('form_footer','form_tpl1') as $name) {
 $chunk=$modx->getObject('modChunk',array('name'=>$name));
 if ($chunk && $chunk->get('static')) {
  $file=$root.$chunk->get('static_file');
  if (!is_file($file)) $errors[]='Missing native form file: '.$name;
 }
}
$target=$modx->getObject('modTemplate',array('static_file'=>$spec['template']['static_file']));
if ($target && (!$home || (int)$home->get('template')!==(int)$target->get('id'))) $warnings[]='Target template already exists: inspect before registration';
$warnings[]='Actual lead delivery and a fresh production backup are not verified by this tool';
echo json_encode(array('mode'=>'INSPECT_ONLY','php'=>PHP_VERSION,'homeResource'=>$home?(int)$home->get('id'):null,'currentTemplate'=>$template?$template->get('templatename'):null,'currentTemplateFile'=>$template?$template->get('static_file'):null,'payloadFiles'=>count($payload),'errors'=>array_values(array_unique($errors)),'warnings'=>$warnings,'productionActivationApproved'=>false),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
exit($errors?1:0);
