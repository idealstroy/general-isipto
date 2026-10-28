import fs from 'node:fs';import assert from 'node:assert/strict';import {spawnSync} from 'node:child_process';
const p=JSON.parse(fs.readFileSync('_backups/active-outsourcing-deploy.json','utf8'));
const input='<?php $testSince='+JSON.stringify(p.createdUtc)+'; ?>\n'+fs.readFileSync('deployment/outsourcing/verify-crm-tests.php','utf8');
const r=spawnSync('docker',['exec','--user','www-data','-i','modx-isipto-web-1','php'],{input,encoding:'utf8',windowsHide:true,timeout:60000});
fs.writeFileSync(p.directory+'/crm-readonly.log',r.stderr||'');assert.equal(r.status,0,'CRM read-only check failed; see private log');
const report=JSON.parse(r.stdout.trim());fs.writeFileSync(p.directory+'/crm-tests-readonly.json',JSON.stringify(report,null,2));console.log(JSON.stringify(report,null,2));
if(process.argv[2]==='require-both')for(const test of report.tests){assert(!test.readFailed);assert.equal(test.deals.length,1,'Expected exactly one deal per test');assert.equal(+test.deals[0].CATEGORY_ID,31);assert.equal(+test.deals[0].ASSIGNED_BY_ID,3280);}
