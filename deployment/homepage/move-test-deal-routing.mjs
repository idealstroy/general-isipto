import fs from 'node:fs';import {spawn} from 'node:child_process';
const plan=JSON.parse(fs.readFileSync('_backups/active-bitrix-routing.json','utf8'));
const log=fs.createWriteStream(plan.directory+'/test-deal-routing.jsonl',{flags:'a'});
const c=spawn('docker',['exec','--user','www-data','-i','modx-isipto-web-1','php'],{windowsHide:true});fs.createReadStream('deployment/homepage/move-test-deal-routing.php').pipe(c.stdin);c.stdout.on('data',b=>{log.write(b);process.stdout.write(b);});c.stderr.resume();const code=await new Promise((r,j)=>{c.on('error',j);c.on('close',r)});await new Promise(r=>log.end(r));if(code)throw Error('Test-deal routing not confirmed; inspect the saved before/after log before retrying');
