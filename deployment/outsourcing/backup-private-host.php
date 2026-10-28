<?php
// Private full backups only. No MODX bootstrap, database import or public-file writes.
if(PHP_SAPI!=='cli'||!isset($settings)||!in_array($settings['mode'],array('create','resume','check'),true))exit(2);
$root=realpath($settings['targetRoot']);$id=$settings['id'];
if($root!=='/home/i/idealstroy/isipto.ru/public_html'||!preg_match('/^outsourcing-[0-9TZ-]+$/D',$id))exit(2);
$base='/home/i/idealstroy/_codex_backups';$dir=$base.'/'.$id;umask(0077);
foreach(array('/home/i/idealstroy',$base,$dir) as $path)if(is_link($path))exit(2);
function command($command,$log){exec('bash -o pipefail -c '.escapeshellarg($command).' 2>'.escapeshellarg($log),$out,$status);return $status;}
function python($source){return "python -c ".escapeshellarg('exec(bytes(bytearray(['.implode(',',array_values(unpack('C*',$source))).'])))');}
function verifyArchive($path,$sql){
    $source="import sys,gzip,tarfile,json\np=".json_encode($path,JSON_UNESCAPED_SLASHES)."\nf=gzip.open(p,'rb')\nn=0\ntail=''\n";
    if($sql)$source.="tables=0\nwhile True:\n b=f.read(1048576)\n if not b: break\n n+=len(b)\n s=tail+b\n tables+=s.count('\\nCREATE TABLE ')\n tail=s[-200:]\n tail=tail.replace('\\nCREATE TABLE ','\\nCOUNTED TABLE ')\nf.close()\nassert tables>0 and '-- Dump completed on ' in tail\nprint(json.dumps(dict(gzipIntegrity=True,expandedBytes=n,tables=tables,dumpCompleted=True)))\n";
    else $source.="t=tarfile.open(fileobj=f,mode='r|')\nentries=0\nfor member in t:\n entries+=1\nt.close()\nwhile f.read(1048576): pass\nf.close()\nassert entries>0\nprint(json.dumps(dict(gzipIntegrity=True,tarReadable=True,entries=entries)))\n";
    $out=array();exec(python($source).' 2>'.escapeshellarg($path.'.verify.log'),$out,$status);
    if($status!==0)throw new RuntimeException('Backup integrity check failed');
    $result=json_decode(implode("\n",$out),true);if(!$result)throw new RuntimeException('Invalid verification result');
    $result['file']=basename($path);$result['sha256Verified']=true;return $result;
}
try{
    if($settings['mode']==='create'||$settings['mode']==='resume'){
        $resume=$settings['mode']==='resume';
        if(!$resume&&file_exists($dir))throw new RuntimeException('Refusing to overwrite existing backups');
        if($resume&&(realpath($dir)!==$dir||(fileperms($dir)&0077)!==0||file_exists($dir.'/verified.json')))throw new RuntimeException('Unsafe incomplete backup resume');
        if(!is_dir($base)&&!mkdir($base,0700))throw new RuntimeException('Backup parent unavailable');
        if(realpath($base)!==$base||!chmod($base,0700)||(!$resume&&!mkdir($dir,0700)))throw new RuntimeException('Unsafe backup destination');
        if(disk_free_space($dir)<4*1024*1024*1024)throw new RuntimeException('Insufficient hosting backup space');
        $compressor="import sys,zlib\ni=sys.stdin\no=sys.stdout\nwhile True:\n b=i.read(1048576)\n if not b: break\n c=zlib.compressobj(1,zlib.DEFLATED,31)\n n=0\n while b:\n  o.write(c.compress(b))\n  n+=len(b)\n  if n>=268435456: break\n  b=i.read(1048576)\n o.write(c.flush())\no.flush()\n";
        $backups=array();
        foreach(array('hosting-files.tar.gz','hosting-database.sql.gz') as $file){
            $path=$dir.'/'.$file;$partial=$path.'.partial';$log=$path.'.log';
            if(file_exists($partial))throw new RuntimeException('Incomplete partial archive requires inspection, not overwrite');
            $reuse=$resume&&is_file($path)&&!is_link($path)&&(fileperms($path)&0077)===0;
            if(file_exists($path)&&!$reuse)throw new RuntimeException('Refusing unsafe existing archive');
            $changed=false;
            if(!$reuse){
            if($file==='hosting-files.tar.gz')$cmd='tar -C '.escapeshellarg($root).' -cf - . | '.python($compressor).' >'.escapeshellarg($partial);
            else{
                require $root.'/core/config/config.inc.php';putenv('MYSQL_PWD='.$database_password);
                $cmd='mysqldump --skip-lock-tables --skip-add-locks --quick --hex-blob --routines --triggers --events --default-character-set=utf8 --host='.escapeshellarg($database_server).' --user='.escapeshellarg($database_user).' '.escapeshellarg($dbase).' | gzip -1 >'.escapeshellarg($partial);
            }
            $status=command($cmd,$log);putenv('MYSQL_PWD');
            if($status===1&&$file==='hosting-files.tar.gz'){
                $messages=array_filter(explode("\n",trim(file_get_contents($log))));$changed=count($messages)>0;
                foreach($messages as $line)if(!preg_match('/^tar: .*: file changed as we read it$/D',$line))$changed=false;
            }
            if($status!==0&&!$changed)throw new RuntimeException('Backup export failed; partial retained privately');
            if(!chmod($partial,0600)||!rename($partial,$path))throw new RuntimeException('Backup finalization failed');
            }
            $verification=verifyArchive($path,$file==='hosting-database.sql.gz');
            $metadata=array('file'=>$file,'bytes'=>filesize($path),'sha256'=>hash_file('sha256',$path),'completedAt'=>gmdate('c'),'remotePath'=>$path,'liveFileChangeWarning'=>$changed,'consistency'=>'Hot export; MyISAM and live files are not an atomic snapshot');
            $backups[]=array('metadata'=>$metadata,'verification'=>$verification);
        }
        $report=array('id'=>$id,'status'=>'VERIFIED','directory'=>$dir,'backups'=>$backups,'publicFilesChanged'=>false,'databaseWrites'=>0,'crmWrites'=>0);
        if(file_put_contents($dir.'/verified.json',json_encode($report,JSON_UNESCAPED_SLASHES),LOCK_EX)===false||!chmod($dir.'/verified.json',0600))throw new RuntimeException('Backup proof write failed');
    }else{
        if(realpath($dir)!==$dir||!is_file($dir.'/verified.json')||is_link($dir.'/verified.json'))throw new RuntimeException('Backup proof missing');
        $report=json_decode(file_get_contents($dir.'/verified.json'),true);
        if(!$report||$report['id']!==$id||$report['status']!=='VERIFIED')throw new RuntimeException('Invalid backup proof');
        foreach($report['backups'] as $b){
            $path=$dir.'/'.$b['metadata']['file'];
            if(!in_array($b['metadata']['file'],array('hosting-files.tar.gz','hosting-database.sql.gz'),true)||is_link($path)||realpath($path)!==$path||(fileperms($path)&0077)!==0)throw new RuntimeException('Unsafe private archive');
            if(hash_file('sha256',$path)!==$b['metadata']['sha256']||filesize($path)!==$b['metadata']['bytes'])throw new RuntimeException('Private archive drift');
            if(time()-strtotime($b['metadata']['completedAt'])>86400)throw new RuntimeException('Fresh backups required');
        }
    }
    echo json_encode($report,JSON_UNESCAPED_SLASHES)."\n";
}catch(Exception $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
