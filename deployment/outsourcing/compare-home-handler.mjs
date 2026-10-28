import fs from 'node:fs';import path from 'node:path';import assert from 'node:assert/strict';import {spawnSync} from 'node:child_process';import {createHash} from 'node:crypto';
const p=JSON.parse(fs.readFileSync('_backups/active-outsourcing-deploy.json','utf8'));
const source='\n'+fs.readFileSync('deployment/outsourcing/compare-home-handler.php','utf8');
const r=spawnSync('ssh',['-o','BatchMode=yes','-o','StrictHostKeyChecking=yes','-o','ConnectTimeout=15','-o','IdentitiesOnly=yes','-i',path.resolve('.ssh/isipto_transfer_ed25519_v3'),'idealstroy_1@idealstroy.beget.tech','/usr/local/bin/php7.3 -d memory_limit=512M -d display_errors=stderr -f /dev/stdin'],{input:source,encoding:'utf8',windowsHide:true,timeout:60000,maxBuffer:4*1024*1024});
fs.writeFileSync(p.directory+'/home-handler-comparison.log',r.stderr||'');assert.equal(r.status,0,'Read-only comparison failed; see private log');
const report=JSON.parse(r.stdout.trim());
const approved=fs.readFileSync('modx-isipto/assets/templates/outsourcing-solution.html','utf8');
report.approvedOutsourcingHooks=[...approved.matchAll(/&hooks=`([^`]*)`/g)].map(x=>x[1]);
const prior=JSON.parse(fs.readFileSync('_backups/active-direct-release.json','utf8'));
const hash=file=>createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const oldHook=prior.directory+'/payload/assets/snippets/bitrix24_hooks.php';
report.handlerMatchesPreviouslyPublishedPackage=fs.existsSync(oldHook)?hash(oldHook)===report.handler.sha256:null;
report.sharedFilesMatchPreviouslyPublishedPackage={};for(const [file,sha]of Object.entries(report.sharedFileHashes)){const f=prior.directory+'/payload/'+file;report.sharedFilesMatchPreviouslyPublishedPackage[file]=fs.existsSync(f)?hash(f)===sha:null;}
fs.writeFileSync(p.directory+'/home-handler-comparison.json',JSON.stringify(report,null,2));console.log(JSON.stringify(report,null,2));
