import fs from 'node:fs';
import path from 'node:path';
import {spawn} from 'node:child_process';
import {pipeline} from 'node:stream/promises';
import {createHash} from 'node:crypto';
const dir='_backups/hosting-readonly-20261022/';
if(process.argv.includes('--contexts')){
 const a=JSON.parse(fs.readFileSync(dir+'remote-relations.json','utf8')).database.contextSettings,b=JSON.parse(fs.readFileSync(dir+'local-relations.json','utf8')).database.contextSettings;
 const differences=[];for(const key of new Set([...Object.keys(a),...Object.keys(b)])){const fields=new Set([...Object.keys(a[key]?.fieldHashes||{}),...Object.keys(b[key]?.fieldHashes||{})]);const changed=[...fields].filter(f=>a[key]?.fieldHashes[f]!==b[key]?.fieldHashes[f]);if(changed.length)differences.push({key,fields:changed,remotePublicValue:a[key]?.publicValue,localPublicValue:b[key]?.publicValue,remoteOnly:!b[key],localOnly:!a[key]});}fs.writeFileSync(dir+'context-comparison.json',JSON.stringify(differences,null,2));console.log(JSON.stringify({differences:differences.length,substantive:differences.filter(x=>x.fields.some(f=>f!=='editedon')),samples:differences.slice(0,3),timeSample:{remote:a['web:site_start'],local:b['web:site_start']}},null,2));process.exit(0);
}
const before=JSON.parse(fs.readFileSync(dir+'local-inventory.json','utf8'));
const child=spawn('docker',['exec','-i','--user','www-data','modx-isipto-web-1','env','ISIPTO_AUDIT_DB_ONLY=1','php','-d','memory_limit=1024M'],{windowsHide:true});
const exited=new Promise((resolve,reject)=>{child.on('error',reject);child.on('close',resolve);});
child.stderr.pipe(fs.createWriteStream(dir+'local-final-db.log'));fs.createReadStream('deployment/homepage/audit-host-readonly.php').pipe(child.stdin);
await pipeline(child.stdout,fs.createWriteStream(dir+'local-final-db.json'));if(await exited!==0)throw Error('Final read-only DB check failed');
const after=JSON.parse(fs.readFileSync(dir+'local-final-db.json','utf8'));
const equal=(a,b)=>JSON.stringify(a)===JSON.stringify(b),changed=[];
const entries=Object.entries(before.files).filter(([,v])=>!v.volatile);let next=0;
await Promise.all(Array.from({length:8},async()=>{while(next<entries.length){const [file,metadata]=entries[next++];try{const hash=createHash('sha256');for await(const b of fs.createReadStream(path.join('modx-isipto',file)))hash.update(b);if(hash.digest('hex')!==metadata.sha256)changed.push(file);}catch{changed.push(file);}}}));
const remoteRelations=JSON.parse(fs.readFileSync(dir+'remote-relations.json','utf8')).database,localRelations=JSON.parse(fs.readFileSync(dir+'local-relations.json','utf8')).database;
const rawRelationChanges=Object.keys(remoteRelations.relations).filter(k=>!equal(remoteRelations.relations[k],localRelations.relations[k]));
const contextDifferences=JSON.parse(fs.readFileSync(dir+'context-comparison.json','utf8'));
const substantiveContextChanges=contextDifferences.filter(x=>x.remoteOnly||x.localOnly||x.fields.some(f=>f!=='editedon'));
const relationChanges=rawRelationChanges.filter(k=>k!=='context_setting'||substantiveContextChanges.length);
const health=[];for(const url of ['http://localhost:3000/','http://localhost:3000/vedenie-id/','http://localhost:3000/upravlenie-stroitelstvom/','http://localhost:3000/_preview/isi-solution/']){
 const r=await fetch(url,{headers:{'User-Agent':'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130.0.0.0 Safari/537.36'},signal:AbortSignal.timeout(30000)});health.push({url,status:r.status});await r.arrayBuffer();
}
const zip='dist/homepage-2026-10-21-20261021T065000115Z.zip';
const result={checkedAt:new Date().toISOString(),databaseObjectsUnchanged:equal(before.database.objects,after.database.objects),settingsUnchanged:equal(before.database.settings,after.database.settings),tvValuesUnchanged:equal(before.database.tvValues,after.database.tvValues),checkedSourceFiles:entries.length,changedFiles:changed,allowedDocumentationChanges:['LOCAL-DEVELOPMENT.md'],unexpectedSourceChanges:changed.filter(p=>p!=='LOCAL-DEVELOPMENT.md'),relationChanges,technicalContextTimestampDifferences:contextDifferences,checkedRelationTables:Object.keys(remoteRelations.relations).length,mysqlHosting:remoteRelations.serverVersion,mysqlLocal:localRelations.serverVersion,health,releaseZipSHA256:createHash('sha256').update(fs.readFileSync(zip)).digest('hex')};
const classification=JSON.parse(fs.readFileSync(dir+'classification.json','utf8'));classification.environment.contextTimestampDifferences=contextDifferences;classification.environment.timestampNormalization='MySQL TIMESTAMP compared through UNIX_TIMESTAMP; host UTC+3 and local UTC';fs.writeFileSync(dir+'classification.json',JSON.stringify(classification,null,2));
fs.writeFileSync(dir+'preservation.json',JSON.stringify(result,null,2));console.log(JSON.stringify(result,null,2));
if(!result.databaseObjectsUnchanged||!result.settingsUnchanged||!result.tvValuesUnchanged||result.unexpectedSourceChanges.length||relationChanges.length)throw Error('Preservation/dependency gate failed; inspect audit');
