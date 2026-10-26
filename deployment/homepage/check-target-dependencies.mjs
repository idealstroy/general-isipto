import fs from 'node:fs';
import {createHash} from 'node:crypto';
const dir='_backups/hosting-readonly-20261022/',remote=JSON.parse(fs.readFileSync(dir+'remote-inventory.json','utf8')),local=JSON.parse(fs.readFileSync(dir+'local-inventory.json','utf8'));
const spec=JSON.parse(fs.readFileSync('deployment/homepage/release-spec.json','utf8')),release='dist/homepage-2026-10-21-20261021T065000115Z/',manifest=JSON.parse(fs.readFileSync(release+'manifest.json','utf8'));
const errors=[],checks=[];
for(const entry of manifest){const digest=p=>createHash('sha256').update(fs.readFileSync(p)).digest('hex');if(digest(release+'payload/'+entry.path)!==entry.sha256||digest('modx-isipto/'+entry.path)!==entry.sha256)errors.push('Manifest mismatch: '+entry.path);}
for(const [kind,names,table]of [['chunk',spec.requiredChunks,'site_htmlsnippets'],['snippet',spec.requiredSnippets,'site_snippets'],['TV',[...spec.tvs,'docs'],'site_tmplvars']]){
 for(const name of names){const r=remote.database.objects[table].find(x=>x.name===name),l=local.database.objects[table].find(x=>x.name===name);if(!r||!l){errors.push('Missing '+kind+': '+name);continue;}if(r.sha256!==l.sha256)errors.push('Changed '+kind+': '+name);if(r.static==='1'&&r.static_file&&!remote.files[r.static_file])errors.push('Missing static file: '+r.static_file);checks.push({kind,name,sameAsHosting:r.sha256===l.sha256});}
}
const html=fs.readFileSync(release+'payload/'+spec.template.static_file,'utf8'),sharedAssets=new Set();
const pattern=/(?:src|href)="(\/(?:assets|images)\/[^"?#]+)|url\(["']?(\/(?:assets|images)\/[^)"'?#]+)/g;
for(const m of html.matchAll(pattern)){const p=(m[1]||m[2]).slice(1);if(spec.files.includes(p))continue;sharedAssets.add(p);if(!remote.files[p])errors.push('Missing shared asset: '+p);else if(remote.files[p].sha256!==local.files[p]?.sha256)errors.push('Different shared asset: '+p);}
const sourceTemplate=remote.database.objects.site_templates.find(x=>x.id==='33'),bindings=remote.database.objects.site_tmplvar_templates.filter(x=>x.templateid==='33');
for(const name of spec.tvs){const tv=remote.database.objects.site_tmplvars.find(x=>x.name===name);if(!tv||!bindings.some(x=>x.tmplvarid===tv.id))errors.push('TV not bound to original template: '+name);}
const metadata=fs.existsSync(dir+'production-http-metadata.json')?JSON.parse(fs.readFileSync(dir+'production-http-metadata.json','utf8')):null;
const report={generatedAt:new Date().toISOString(),payloadFiles:manifest.length,dependencyChecks:checks,sharedAssets:[...sharedAssets],sourceTemplate:{id:sourceTemplate?.id,file:sourceTemplate?.static_file},originalTvBindings:bindings.length,phpCliHosting:remote.phpCli,phpLocal:local.phpCli,phpWebHosting:metadata?.phpHeader||'UNCONFIRMED',phpWebEvidence:metadata,errors,productionActivationApproved:false,possiblePhpInfoFiles:Object.keys(remote.files).filter(p=>/(?:^|\/)(?:phpinfo|info|phpversion)\.php$|(?:^|\/)\.user\.ini$/.test(p)&&!p.startsWith('st/'))};
fs.writeFileSync(dir+'target-dependencies.json',JSON.stringify(report,null,2));console.log(JSON.stringify(report,null,2));
