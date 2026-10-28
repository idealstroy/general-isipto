import fs from 'node:fs';import path from 'node:path';import assert from 'node:assert/strict';import {spawnSync} from 'node:child_process';
const p=JSON.parse(fs.readFileSync('_backups/active-crm-ui-fix.json','utf8'));
let source=fs.readFileSync('deployment/crm-fix/test-form-errors.php','utf8').replaceAll('/var/www/html/',p.targetRoot+'/').replace("if ($database_server!=='db')exit(2);","if ($database_server==='db')exit(2);").replace("$modx->initialize('web');","$modx->initialize('web');$modx->setLogLevel(modX::LOG_LEVEL_FATAL);");
source='\n'+source;
const r=spawnSync('ssh',['-o','BatchMode=yes','-o','StrictHostKeyChecking=yes','-o','ConnectTimeout=15','-o','IdentitiesOnly=yes','-i',path.resolve('.ssh/isipto_transfer_ed25519_v3'),'idealstroy_1@idealstroy.beget.tech','/usr/local/bin/php7.3 -d memory_limit=512M -d display_errors=stderr -f /dev/stdin'],{input:source,encoding:'utf8',windowsHide:true,timeout:60000,maxBuffer:2*1024*1024});
fs.writeFileSync(p.directory+'/host-tests.log',r.stderr||'');fs.writeFileSync(p.directory+'/host-tests.stdout',r.stdout||'');assert.equal(r.status,0,'Native hosting test failed; inspect private log');
const result=JSON.parse(r.stdout.trim());for(const test of Object.values(result.tests))assert(test.passed);fs.writeFileSync(p.directory+'/host-tests.json',JSON.stringify(result,null,2));console.log(JSON.stringify(result,null,2));
