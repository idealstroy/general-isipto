import fs from 'node:fs';
import path from 'node:path';
import {spawn} from 'node:child_process';
const dir='_backups/hosting-readonly-20261022/',raw=JSON.parse(fs.readFileSync(dir+'comparison-raw.json','utf8'));
const groups={};for(const p of raw.files.remoteOnly){const group=p.startsWith('st/')?'st':p.startsWith('assets/cache_image/')?'assets/cache_image':p.split('/')[0];groups[group]=(groups[group]||0)+1;}
console.log(JSON.stringify({remoteOnlyGroups:groups,localOnly:raw.files.localOnly,homeChanges:raw.objects.site_content.changed,newTemplates:raw.objects.site_templates.localOnly.map(x=>({id:x.id,name:x.templatename,file:x.static_file})),newChunks:raw.objects.site_htmlsnippets.localOnly.map(x=>({id:x.id,name:x.name,file:x.static_file})),tvBindings:raw.objects.site_tmplvar_templates.localOnly.map(x=>({tv:x.tmplvarid,template:x.templateid,rank:x.rank}))},null,2));
const extracted=path.resolve(dir,'production-config-readonly');
if(!fs.existsSync(extracted)){
 fs.mkdirSync(extracted);
 const child=spawn('tar',['-xzf',path.resolve(dir,'hosting-files.tar.gz'),'-C',extracted,...raw.files.changed.map(x=>'./'+x.path)],{windowsHide:true,stdio:['ignore','ignore','pipe']});
 const errors=[];child.stderr.on('data',b=>errors.push(b));
 if(await new Promise((resolve,reject)=>{child.on('error',reject);child.on('close',resolve);})!==0)throw Error('Could not extract selected originals: '+Buffer.concat(errors));
}
// Emit only changed lines of non-secret entry files; never print database configuration.
for(const file of ['index.php','.htaccess','.gitignore','config.core.php']){
 const before=fs.readFileSync(path.join(extracted,file),'utf8').replace(/\r/g,'').split('\n'),after=fs.readFileSync(path.join('modx-isipto',file),'utf8').replace(/\r/g,'').split('\n');
 const dp=Array.from({length:before.length+1},()=>new Uint16Array(after.length+1));
 for(let i=before.length-1;i>=0;i--)for(let j=after.length-1;j>=0;j--)dp[i][j]=before[i]===after[j]?dp[i+1][j+1]+1:Math.max(dp[i+1][j],dp[i][j+1]);
 const lines=[];let i=0,j=0;while(i<before.length||j<after.length){if(i<before.length&&j<after.length&&before[i]===after[j]){i++;j++;}else if(j<after.length&&(i===before.length||dp[i][j+1]>=dp[i+1][j]))lines.push('+ '+after[j++]);else lines.push('- '+before[i++]);}
 console.log(JSON.stringify({file,changedLines:lines},null,2));
}
const release=JSON.parse(fs.readFileSync('deployment/homepage/release-spec.json','utf8'));
const environment=new Set(['.htaccess','index.php','.gitignore','vzkad9o4/config.core.php','connectors/config.core.php','manager/config.core.php','config.core.php','core/config/config.inc.php']);
const knownExtra=p=>/^\.(migration|playwright-cli)\//.test(p)||['assets/cache_image/noimage_253x348_7a0.jpg','assets/components/phpthumbon/cache/253x348_7a0.jpg','core/config/config.inc.php.production','docker-compose.yml','Dockerfile','database/modx.sql.gz','LOCAL-DEVELOPMENT.md','POSITIONING.md'].includes(p);
const classified={intended:{files:raw.files.localOnly.filter(p=>release.files.includes(p)),newTemplate:84,newChunk:134,newTvBindings:11,homeResource:3075,homeFields:['template','editedon']},environment:{changedFiles:raw.files.changed.filter(x=>environment.has(x.path)),localToolsAndCaches:raw.files.localOnly.filter(knownExtra),omittedRemoteDirectories:groups},unknown:{changedFiles:raw.files.changed.filter(x=>!environment.has(x.path)),localFiles:raw.files.localOnly.filter(p=>!release.files.includes(p)&&!knownExtra(p)),remoteFiles:raw.files.remoteOnly.filter(p=>!p.startsWith('st/')&&!p.startsWith('_deploy_tmp/')&&!p.startsWith('__MACOSX/'))}};
const secretsFile='core/config/config.inc.php';
const assignmentKeys=text=>{const map=new Map();for(const line of text.split(/\r?\n/)){const m=line.match(/^\s*(\$[A-Za-z_]\w*|define\s*\(\s*['"][^'"]+['"])\s*(?:=|,)/);if(m)map.set(m[1],line.trim());}return map;};
const a=assignmentKeys(fs.readFileSync(path.join(extracted,secretsFile),'utf8')),b=assignmentKeys(fs.readFileSync(path.join('modx-isipto',secretsFile),'utf8'));
classified.environment.changedConfigAssignmentNames=[...new Set([...a.keys(),...b.keys()])].filter(k=>a.get(k)!==b.get(k));
fs.writeFileSync(dir+'classification.json',JSON.stringify(classified,null,2));
console.log(JSON.stringify({classification:classified},null,2));
