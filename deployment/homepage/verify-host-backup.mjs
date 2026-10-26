import fs from 'node:fs';
import path from 'node:path';
import {createGunzip} from 'node:zlib';
import {createHash} from 'node:crypto';
import {Transform,Writable} from 'node:stream';
import {pipeline} from 'node:stream/promises';
import {spawn} from 'node:child_process';
const file=path.resolve(process.argv[2]);
const metadata=JSON.parse(fs.readFileSync(file+'.json','utf8'));
let expandedBytes=0,tail='',tableCount=0;const hash=createHash('sha256');
await pipeline(fs.createReadStream(file),new Transform({transform(b,e,cb){hash.update(b);cb(null,b);}}),createGunzip(),new Writable({write(b,e,cb){expandedBytes+=b.length;if(file.endsWith('.sql.gz')){const s=tail+b.toString('utf8');tableCount+=(s.match(/\nCREATE TABLE /g)||[]).length;tail=s.slice(-200);tail=tail.replace(/\nCREATE TABLE /g,'\nCOUNTED TABLE ');}cb();}}));
if(hash.digest('hex')!==metadata.sha256)throw Error('SHA256 mismatch');
const report={file:path.basename(file),gzipIntegrity:true,sha256Verified:true,expandedBytes};
if(file.endsWith('.sql.gz')){
 report.tables=tableCount;report.dumpCompleted=/-- Dump completed on /.test(tail);
 if(!report.dumpCompleted||!tableCount)throw Error('Incomplete SQL dump');
}
if(file.endsWith('.tar.gz')){
 const list=fs.createWriteStream(file+'.entries.txt',{flags:'wx'});
 const child=spawn('tar',['-tzf',file],{windowsHide:true});
 const errors=[];child.stderr.on('data',b=>errors.push(b));
 const exited=new Promise((resolve,reject)=>{child.on('error',reject);child.on('close',resolve);});
 let entries=0;child.stdout.on('data',b=>{entries+=b.toString().split('\n').length-1;});
 await pipeline(child.stdout,list);
 const code=await exited;if(code!==0)throw Error('tar verification failed: '+Buffer.concat(errors).toString());
 report.tarReadable=true;report.entries=entries;
}
fs.writeFileSync(file+'.verified.json',JSON.stringify(report,null,2));
console.log(JSON.stringify(report));
