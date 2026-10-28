import fs from 'node:fs';import path from 'node:path';import assert from 'node:assert/strict';import {spawnSync} from 'node:child_process';
const p=JSON.parse(fs.readFileSync('_backups/active-outsourcing-deploy.json','utf8'));
const encoded=Buffer.from(JSON.stringify({...p,cleanup:process.argv[2]==='cleanup'})).toString('base64');
const source='\n<?php $settings=json_decode(base64_decode("'+encoded+'"),true); ?>\n'+fs.readFileSync('deployment/outsourcing/inspect-failed-install.php','utf8');
const r=spawnSync('ssh',['-o','BatchMode=yes','-o','StrictHostKeyChecking=yes','-o','ConnectTimeout=15','-o','IdentitiesOnly=yes','-i',path.resolve('.ssh/isipto_transfer_ed25519_v3'),'idealstroy_1@idealstroy.beget.tech','/usr/local/bin/php7.3 -d display_errors=stderr -f /dev/stdin'],{input:source,encoding:'utf8',windowsHide:true,timeout:60000});
fs.writeFileSync(p.directory+'/failed-install-'+(process.argv[2]||'inspect')+'.log',r.stderr||'');assert.equal(r.status,0,'See private diagnostic log');const j=JSON.parse(r.stdout.trim());fs.writeFileSync(p.directory+'/failed-install-'+(process.argv[2]||'inspect')+'.json',JSON.stringify(j,null,2));console.log(JSON.stringify(j));
