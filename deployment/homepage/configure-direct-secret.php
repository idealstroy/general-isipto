<?php
// Run only after explicit permission to change hosting. Secret comes from stdin, never argv.
if(PHP_SAPI!=='cli'||PHP_VERSION_ID<70300||empty($argv[1])||!in_array(isset($argv[2])?$argv[2]:'',array('--check','--stdin'),true))exit(2);
$root=realpath($argv[1]);if(!$root||!is_file($root.'/config.core.php')||realpath(__DIR__)===$root)exit(2);
$parent=dirname($root);$dir=$parent.'/.isipto-secrets';$file=$dir.'/bitrix24.env';
if(is_link($dir)||is_link($file))exit(2);
if($argv[2]==='--check'){$ok=is_readable($file)&&((fileperms($file)&0007)===0);echo json_encode(array('secretOutsidePublicRoot'=>true,'readable'=>is_readable($file),'noWorldPermissions'=>$ok,'secretPrinted'=>false))."\n";exit($ok?0:1);}
require __DIR__.'/payload/core/components/bitrix24/client.php';$value=trim(stream_get_contents(STDIN));try{new IsiptoBitrix24Client($value);}catch(Exception $e){fwrite(STDERR,"Invalid webhook format\n");exit(2);}
umask(0077);if(!is_dir($dir)&&!mkdir($dir,0700))exit(1);if(!chmod($dir,0700))exit(1);
if(file_exists($file)){$old=$file.'.before-'.gmdate('YmdHis').'-'.bin2hex(random_bytes(4));if(!copy($file,$old)||!chmod($old,0600))exit(1);}
$tmp=$dir.'/.new-'.bin2hex(random_bytes(8));if(file_put_contents($tmp,'BITRIX24_WEBHOOK_URL='.$value."\n",LOCK_EX)===false||!chmod($tmp,0600)||!rename($tmp,$file))exit(1);
echo json_encode(array('configured'=>true,'outsidePublicRoot'=>true,'secretPrinted'=>false,'crmRequests'=>0))."\n";
