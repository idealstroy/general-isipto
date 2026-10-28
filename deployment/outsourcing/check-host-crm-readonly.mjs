import fs from 'node:fs';import path from 'node:path';import assert from 'node:assert/strict';import {spawnSync} from 'node:child_process';
const p=JSON.parse(fs.readFileSync('_backups/active-outsourcing-deploy.json','utf8'));
const enc=Buffer.from(JSON.stringify({targetRoot:p.targetRoot})).toString('base64');
const source='\n<?php $settings=json_decode(base64_decode("'+enc+'"),true); ?>\n'+fs.readFileSync('deployment/outsourcing/check-host-crm-readonly.php','utf8');
const r=spawnSync('ssh',['-o','BatchMode=yes','-o','StrictHostKeyChecking=yes','-o','ConnectTimeout=15','-o','IdentitiesOnly=yes','-i',path.resolve('.ssh/isipto_transfer_ed25519_v3'),'idealstroy_1@idealstroy.beget.tech','/usr/local/bin/php7.3 -f /dev/stdin'],{input:source,encoding:'utf8',windowsHide:true,timeout:45000});
fs.writeFileSync(p.directory+'/hosting-crm-readonly.log',r.stderr||'');assert.equal(r.status,0,'Hosting CRM diagnostic failed; see private log');const j=JSON.parse(r.stdout.trim());fs.writeFileSync(p.directory+'/hosting-crm-readonly.json',JSON.stringify(j,null,2));console.log(JSON.stringify(j));
