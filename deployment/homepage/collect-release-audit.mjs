import fs from 'node:fs';
import path from 'node:path';
import {spawn} from 'node:child_process';
import {pipeline} from 'node:stream/promises';
import {createHash} from 'node:crypto';
const mode=process.argv[2],relations=process.argv.includes('--relations'),root=process.cwd(),dir=path.join(root,'_backups/hosting-readonly-20261022');
if(!['remote','local'].includes(mode))throw Error('Expected remote or local');
const target=path.join(dir,mode+(relations?'-relations':'-inventory')+'.json'),raw=target+'.partial';
const refresh=process.argv.includes('--refresh');
if((fs.existsSync(target)&&!refresh)||fs.existsSync(raw))throw Error('Inventory already exists');
const args=mode==='remote'?['-o','BatchMode=yes','-o','StrictHostKeyChecking=yes','-o','ConnectTimeout=15','-o','IdentitiesOnly=yes','-i',path.join(root,'.ssh/isipto_transfer_ed25519_v3'),'idealstroy_1@idealstroy.beget.tech',(relations?'ISIPTO_AUDIT_RELATIONS_ONLY=1 ':'')+'php -d memory_limit=1024M']:['exec','-i','--user','www-data','modx-isipto-web-1','env',relations?'ISIPTO_AUDIT_RELATIONS_ONLY=1':'ISIPTO_AUDIT_DB_ONLY=1','php','-d','memory_limit=1024M'];
const child=spawn(mode==='remote'?'ssh':'docker',args,{windowsHide:true});
child.stderr.pipe(fs.createWriteStream(target+'.log',{flags:refresh?'w':'wx'}));
const exited=new Promise((resolve,reject)=>{child.on('error',reject);child.on('close',resolve);});
child.stdin.on('error',()=>{});fs.createReadStream(path.join(root,'deployment/homepage/audit-host-readonly.php')).pipe(child.stdin);
await pipeline(child.stdout,fs.createWriteStream(raw,{flags:'wx'}));
if(await exited!==0)throw Error('Inventory failed; see private log');
const report=JSON.parse(fs.readFileSync(raw,'utf8'));
if(mode==='local'&&!relations){
 const site=path.join(root,'modx-isipto'),queue=[];report.root=site;report.errors=[];report.files={};report.links={};
 async function walk(folder){for(const e of await fs.promises.readdir(folder,{withFileTypes:true})){const absolute=path.join(folder,e.name),rel=path.relative(site,absolute).split(path.sep).join('/');if(e.isSymbolicLink()){report.links[rel]=await fs.promises.readlink(absolute);}else if(e.isDirectory())await walk(absolute);else if(e.isFile())queue.push([absolute,rel]);}}
 await walk(site);let next=0,done=0;
 const timer=setInterval(()=>console.log(JSON.stringify({localFiles:done,total:queue.length})),15000);
 try{await Promise.all(Array.from({length:8},async()=>{while(next<queue.length){const [absolute,rel]=queue[next++];try{const size=(await fs.promises.stat(absolute)).size,volatile=/^(core\/(cache|cache1)\/|.*(?:\/sessions\/))/.test(rel);let sha256=null;if(!volatile){const hash=createHash('sha256');for await(const b of fs.createReadStream(absolute))hash.update(b);sha256=hash.digest('hex');}report.files[rel]={size,sha256,volatile};}catch{report.errors.push('Unreadable: '+rel);}done++;}}));}finally{clearInterval(timer);}
}
fs.writeFileSync(target,JSON.stringify(report));fs.unlinkSync(raw);
console.log(JSON.stringify({mode,files:Object.keys(report.files).length,errors:report.errors,php:report.phpCli,tables:report.database.tables.length}));
