import fs from 'node:fs';
import {spawn} from 'node:child_process';
import {pipeline} from 'node:stream/promises';
const dir='_backups/hosting-readonly-20261022/';
const version=process.argv[2]||'56';if(!['56','73','74'].includes(version))throw Error('Expected 56, 73 or 74');
for(const [name,script,args]of [[`php${version}-preflight`,'/audit-release/preflight.php',['/var/www/html']],[`php${version}-render`,'/audit-tools/check-isolated-http.php',[]]]){
 const child=spawn('docker',['exec',`isipto-audit-php${version}-20261022`,'php','-d','memory_limit=512M',script,...args],{windowsHide:true});
 child.stderr.pipe(fs.createWriteStream(dir+name+'.log',{flags:'w'}));
 const exited=new Promise((resolve,reject)=>{child.on('error',reject);child.on('close',resolve);});
 await pipeline(child.stdout,fs.createWriteStream(dir+name+'.json',{flags:'w'}));
 if(await exited!==0)throw Error(name+' failed; inspect private report');
 const report=JSON.parse(fs.readFileSync(dir+name+'.json','utf8'));console.log(JSON.stringify({check:name,php:report.php,errors:report.errors,pages:report.pages}));
}
