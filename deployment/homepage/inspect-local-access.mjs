import {spawn} from 'node:child_process';
const c=spawn('docker',['logs','--since','30m','--tail','300','modx-isipto-web-1'],{windowsHide:true});let raw='';c.stdout.on('data',b=>raw+=b);c.stderr.on('data',b=>raw+=b);const code=await new Promise((r,j)=>{c.on('error',j);c.on('close',r)});if(code)throw Error('Cannot read local access log');
const requests=[];let phpErrors=0;
for(const l of raw.split(/\r?\n/)){
 const m=l.match(/\[([^\]]+)\]\s+"(GET|POST|HEAD)\s+([^\s]+)\s+HTTP\/[^\"]+"\s+(\d+)/);
 if(m)requests.push({time:m[1],method:m[2],path:m[3].split('?')[0],status:Number(m[4])});
 if(/PHP (?:Fatal error|Warning|Parse error)/.test(l))phpErrors++;
}
console.log(JSON.stringify({requests:requests.slice(-45),phpErrorCount:phpErrors},null,2));
