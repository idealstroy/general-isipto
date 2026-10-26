import fs from 'node:fs';
import {spawn} from 'node:child_process';
import {createGunzip} from 'node:zlib';
import readline from 'node:readline';
import {once} from 'node:events';
const dir='_backups/hosting-readonly-20261022/';
if(process.argv[2]==='config'){
 let config=fs.readFileSync('modx-isipto/core/config/config.inc.php','utf8');
 config=config.replace(/^\$database_server\s*=.*?;\s*$/m,"$database_server = 'isipto-audit-db-20261022';").replace(/^\$database_dsn\s*=.*?;\s*$/m,"$database_dsn = 'mysql:host=isipto-audit-db-20261022;dbname=idealstroy_isipt;charset=utf8';");
 if(!config.includes("$database_server = 'isipto-audit-db-20261022';"))throw Error('Cannot isolate database host');
 fs.writeFileSync(dir+'php56-config.inc.php',config,{flag:'wx'});console.log('Isolated config prepared');
}else if(process.argv[2]==='restore'){
 const child=spawn('docker',['exec','-i','isipto-audit-db-20261022','mysql','-uroot','-paudit_only_root','idealstroy_isipt'],{windowsHide:true});
 child.stderr.pipe(fs.createWriteStream(dir+'isolated-restore.log',{flags:'a'}));
 const exited=new Promise((resolve,reject)=>{child.on('error',reject);child.on('close',resolve);});
 child.stdin.on('error',()=>{});child.stdout.resume();
 const input=fs.createReadStream(dir+'hosting-database.sql.gz').pipe(createGunzip());
 const lines=readline.createInterface({input,crlfDelay:Infinity});let skipped=0;
 for await(const line of lines){if(/^INSERT INTO `(vec_session|vec_formit_forms|vec_manager_log)`/.test(line)){skipped++;continue;}if(!child.stdin.write(line+'\n'))await once(child.stdin,'drain');}
 child.stdin.end();if(await exited!==0)throw Error('Isolated import failed; inspect private log');
 console.log(JSON.stringify({restored:true,omittedDataTables:['vec_session','vec_formit_forms','vec_manager_log'],skippedInsertStatements:skipped}));
}else throw Error('Expected config or restore');
