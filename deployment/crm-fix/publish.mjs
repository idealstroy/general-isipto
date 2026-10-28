import fs from 'node:fs';import path from 'node:path';import assert from 'node:assert/strict';import {createHash} from 'node:crypto';import {spawnSync} from 'node:child_process';
const pointer='_backups/active-crm-ui-fix.json';const p=JSON.parse(fs.readFileSync(pointer,'utf8')),mode=process.argv[2];
const sha=b=>createHash('sha256').update(b).digest('hex');
const ssh=['-o','BatchMode=yes','-o','StrictHostKeyChecking=yes','-o','ConnectTimeout=15','-o','IdentitiesOnly=yes','-i',path.resolve('.ssh/isipto_transfer_ed25519_v3')];const host='idealstroy_1@idealstroy.beget.tech';
p.stage='/home/i/idealstroy/_codex_releases/'+p.id;
function run(exe,args,label){const r=spawnSync(exe,args,{encoding:'utf8',windowsHide:true,timeout:120000,maxBuffer:2*1024*1024});fs.writeFileSync(p.directory+'/'+label+'.log',r.stderr||'');assert.equal(r.status,0,label+' failed; see private log');return r.stdout;}
if(mode==='stage'){
 const tests=JSON.parse(fs.readFileSync(p.directory+'/local-tests.json','utf8'));for(const test of Object.values(tests.tests))assert(test.passed);
 p.release='dist/'+p.id;assert(!fs.existsSync(p.release));fs.mkdirSync(p.release,{recursive:true});const files={};
 for(const f of p.files){const dest=p.release+'/payload/'+f;fs.mkdirSync(path.dirname(dest),{recursive:true});fs.copyFileSync('modx-isipto/'+f,dest);files[f]={before:p.oldHashes[f],after:sha(fs.readFileSync(dest))};}
 fs.writeFileSync(p.release+'/patch.json',JSON.stringify({id:p.id,files},null,2));fs.copyFileSync('deployment/crm-fix/patch.php',p.release+'/patch.php');
 run('tar',['-czf',path.resolve(p.release+'.tar.gz'),'-C',path.resolve(p.release),'.'],'archive');p.archiveSha256=sha(fs.readFileSync(p.release+'.tar.gz'));
 run('ssh',[...ssh,host,`umask 077; test ! -L /home/i/idealstroy/_codex_releases && test ! -e ${p.stage} && mkdir -m 700 ${p.stage}`],'stage-directory');
 run('scp',[...ssh,path.resolve(p.release+'.tar.gz'),host+':'+p.stage+'/patch.tar.gz'],'upload');assert.equal(run('ssh',[...ssh,host,'sha256sum '+p.stage+'/patch.tar.gz'],'uploaded-sha').trim().split(/\s+/)[0],p.archiveSha256);
 run('ssh',[...ssh,host,`umask 077; tar --no-same-owner --no-same-permissions -xzf ${p.stage}/patch.tar.gz -C ${p.stage}`],'extract');
 for(const f of ['patch.php',...p.files.map(f=>'payload/'+f)])run('ssh',[...ssh,host,`/usr/local/bin/php7.3 -l ${p.stage}/${f}`],'lint-'+path.basename(f));
 p.phase='staged';
}else if(['inspect','apply','verify','rollback'].includes(mode)){
 if(mode==='apply')assert.equal(p.phase,'inspected');
 const report=JSON.parse(run('ssh',[...ssh,host,`/usr/local/bin/php7.3 -d memory_limit=512M ${p.stage}/patch.php ${mode}`],mode).trim());
 fs.writeFileSync(p.directory+'/'+mode+'-result.json',JSON.stringify(report,null,2));p.phase=mode==='inspect'?'inspected':report.phase;console.log(JSON.stringify(report));
}else throw Error('Use stage, inspect, apply, verify or rollback');
fs.writeFileSync(pointer,JSON.stringify(p,null,2));console.log(JSON.stringify({phase:p.phase,directory:p.directory}));
