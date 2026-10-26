import fs from 'node:fs';
import path from 'node:path';
import {createHash} from 'node:crypto';
const release='dist/homepage-2026-10-21-20261021T065000115Z';
const spec=JSON.parse(fs.readFileSync(release+'/release-spec.json','utf8')),manifest=JSON.parse(fs.readFileSync(release+'/manifest.json','utf8'));
if(manifest.length!==7||spec.files.length!==7)throw Error('Expected exactly 7 approved files');
for(const e of manifest){for(const file of [release+'/payload/'+e.path,'modx-isipto/'+e.path]){if(createHash('sha256').update(fs.readFileSync(file)).digest('hex')!==e.sha256)throw Error('Unapproved payload difference: '+e.path);}}
const remote=JSON.parse(fs.readFileSync('_backups/hosting-readonly-20261022/remote-inventory.json','utf8'));
const dependencies=JSON.parse(fs.readFileSync('_backups/hosting-readonly-20261022/target-dependencies.json','utf8'));
const baseline={files:{},objects:[]};
for(const file of [...dependencies.sharedAssets,'assets/templates/index_new.html','index.php','.htaccess'])baseline.files[file]=remote.files[file].sha256;
for(const [table,names,field]of [['site_htmlsnippets',spec.requiredChunks,'name'],['site_snippets',spec.requiredSnippets,'name'],['site_tmplvars',spec.tvs,'name'],['site_templates',['33'],'id']]){
 for(const name of names){const r=remote.database.objects[table].find(x=>x[field]===name);if(!r)throw Error('Missing baseline dependency '+name);baseline.objects.push({table,id:r.id,sha256:r.sha256,name});}
}
const stamp=new Date().toISOString().replace(/[-:]/g,'').replace(/\.\d+Z$/,'Z');
const directory=path.join('_backups','homepage-deploy-'+stamp);fs.mkdirSync(directory,{recursive:true});
const plan={id:'homepage-'+stamp,createdAt:new Date().toISOString(),localBackup:directory,sourceRelease:release,remoteRoot:'/home/i/idealstroy/isipto.ru/public_html',remoteStage:'/home/i/idealstroy/_codex_releases/homepage-'+stamp,php:'/usr/local/bin/php7.3',siteGroup:6429,expectedOriginalTemplateFile:'assets/templates/index_new.html',fullBackupRequired:true};
fs.writeFileSync(path.join(directory,'plan.json'),JSON.stringify(plan,null,2));fs.writeFileSync(path.join(directory,'approved-baseline.json'),JSON.stringify(baseline,null,2));
fs.writeFileSync('_backups/active-homepage-deploy.json',JSON.stringify(plan,null,2));console.log(JSON.stringify(plan,null,2));
