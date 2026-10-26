import fs from 'node:fs';
const dir='_backups/hosting-readonly-20261022/';
const remote=JSON.parse(fs.readFileSync(dir+'remote-inventory.json','utf8')),local=JSON.parse(fs.readFileSync(dir+'local-inventory.json','utf8'));
if(process.argv.includes('--inventory-summary')){console.log(JSON.stringify({localFileSamples:Object.keys(local.files).slice(0,12),localLinks:Object.entries(local.links).slice(0,12),localLinkCount:Object.keys(local.links).length,remoteFileSamples:Object.keys(remote.files).slice(0,6)},null,2));process.exit(0);}
const files={equal:0,volatile:0,changed:[],remoteOnly:[],localOnly:[]};
for(const [name,r]of Object.entries(remote.files)){const l=local.files[name];if(r.volatile||l?.volatile){files.volatile++;continue;}if(!l)files.remoteOnly.push(name);else if(r.sha256===l.sha256)files.equal++;else files.changed.push({path:name,remoteBytes:r.size,localBytes:l.size});}
for(const name of Object.keys(local.files))if(!remote.files[name]&&!local.files[name].volatile)files.localOnly.push(name);
const objects={};
for(const [table,rows]of Object.entries(remote.database.objects)){
 const key=r=>r.id??`${r.tmplvarid}:${r.templateid}`;
 const lmap=new Map(local.database.objects[table].map(r=>[key(r),r])),rmap=new Map(rows.map(r=>[key(r),r]));
 const result={equal:0,changed:[],remoteOnly:[],localOnly:[]};
 for(const r of rows){const l=lmap.get(key(r));if(!l)result.remoteOnly.push(r);else if(r.sha256===l.sha256)result.equal++;else result.changed.push({id:key(r),name:r.name??r.templatename??r.uri,fields:Object.keys(r.fieldHashes).filter(k=>r.fieldHashes[k]!==l.fieldHashes[k]),remoteTemplate:r.template,localTemplate:l.template,remoteFile:r.static_file,localFile:l.static_file});}
 for(const l of lmap.values())if(!rmap.has(key(l)))result.localOnly.push(l);
 objects[table]=result;
}
function differences(a,b){return{changed:Object.keys(a).filter(k=>k in b&&a[k]!==b[k]),remoteOnly:Object.keys(a).filter(k=>!(k in b)),localOnly:Object.keys(b).filter(k=>!(k in a))};}
const result={createdAt:new Date().toISOString(),files,objects,settings:differences(remote.database.settings,local.database.settings),tvValues:differences(remote.database.tvValues,local.database.tvValues),symlinks:{remote:remote.links,local:local.links}};
fs.writeFileSync(dir+'comparison-raw.json',JSON.stringify(result,null,2));
const counts=x=>Object.fromEntries(Object.entries(x).map(([k,v])=>[k,Array.isArray(v)?v.length:v]));
console.log(JSON.stringify({files:counts(files),objects:Object.fromEntries(Object.entries(objects).map(([k,v])=>[k,counts(v)])),settings:result.settings,tvValues:counts(result.tvValues),changedFiles:files.changed},null,2));
