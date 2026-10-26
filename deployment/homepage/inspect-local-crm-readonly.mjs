import fs from 'node:fs';
import {spawn} from 'node:child_process';
const c=spawn('docker',['exec','--user','www-data','-i','modx-isipto-web-1','php'],{windowsHide:true});
fs.createReadStream('deployment/homepage/inspect-local-crm-readonly.php').pipe(c.stdin);
c.stdout.pipe(process.stdout);c.stderr.pipe(process.stderr);
const code=await new Promise((r,j)=>{c.on('error',j);c.on('close',r)});if(code)process.exitCode=code;
