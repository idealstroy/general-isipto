import fs from 'node:fs';import assert from 'node:assert/strict';import {spawnSync} from 'node:child_process';
const p=JSON.parse(fs.readFileSync('_backups/active-crm-ui-fix.json','utf8'));
const r=spawnSync('docker',['exec','--user','www-data','-i','modx-isipto-web-1','php'],{input:fs.readFileSync('deployment/crm-fix/verify-local-test.php'),encoding:'utf8',windowsHide:true,timeout:60000});
fs.writeFileSync(p.directory+'/local-delivery-read.log',r.stderr||'');assert.equal(r.status,0,'Read-only test lookup failed; inspect private log');const j=JSON.parse(r.stdout.trim());fs.writeFileSync(p.directory+'/local-delivery-'+(process.argv[2]||'check')+'.json',JSON.stringify(j,null,2));console.log(JSON.stringify(j,null,2));
if(process.argv[2]==='confirm'){assert.equal(j.deals.length,1,'Expected exactly one test deal');assert.equal(+j.deals[0].CATEGORY_ID,31);assert.equal(+j.deals[0].ASSIGNED_BY_ID,3280);assert(j.deals[0].localSourceRecorded);}
