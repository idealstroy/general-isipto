import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import {spawn} from 'node:child_process';
import {createHash} from 'node:crypto';

const mode=process.argv[2],pointer='_backups/active-outsourcing-deploy.json';
const prepared=JSON.parse(fs.readFileSync('_backups/active-outsourcing-preparation.json','utf8'));
const sha=b=>createHash('sha256').update(b).digest('hex');
const host='idealstroy_1@idealstroy.beget.tech';
const ssh=['-o','BatchMode=yes','-o','StrictHostKeyChecking=yes','-o','ConnectTimeout=15','-o','ServerAliveInterval=30','-o','IdentitiesOnly=yes','-i',path.resolve('.ssh/isipto_transfer_ed25519_v3')];
let plan;
function packageCheck(){
  assert.equal(sha(fs.readFileSync(prepared.release+'.tar.gz')),prepared.archiveSha256,'Archive changed');
  const checks=JSON.parse(fs.readFileSync(prepared.release+'/checksums.json','utf8'));
  for(const [f,h] of Object.entries(checks))assert.equal(sha(fs.readFileSync(prepared.release+'/'+f)),h,'Package drift: '+f);
  const spec=JSON.parse(fs.readFileSync(prepared.release+'/release.json','utf8'));
  for(const [f,h] of Object.entries(spec.files))assert.equal(sha(fs.readFileSync('modx-isipto/'+f)),h,'Local page changed: '+f);
}
async function run(exe,args,label,input){
  const child=spawn(exe,args,{windowsHide:true});let out='';child.stdout.setEncoding('utf8');child.stdout.on('data',s=>out+=s);
  child.stderr.pipe(fs.createWriteStream(plan.directory+'/'+label+'.log',{flags:'a'}));child.stdin.on('error',()=>{});child.stdin.end(input);
  const code=await new Promise((r,j)=>{child.on('error',j);child.on('close',r);});assert.equal(code,0,label+' failed; inspect private log');return out;
}
function phase(value){plan.phase=value;plan.updatedUtc=new Date().toISOString();fs.writeFileSync(pointer,JSON.stringify(plan,null,2));fs.writeFileSync(plan.directory+'/plan.json',JSON.stringify(plan,null,2));}
if(mode==='start'||mode==='start-after-rollback'){
  let prior=null;
  if(mode==='start-after-rollback'){
    prior=JSON.parse(fs.readFileSync(pointer,'utf8'));assert.equal(prior.phase,'rolled-back');assert.notEqual(prior.id,prepared.id);
    assert.equal(JSON.parse(fs.readFileSync(prior.directory+'/rollback-result.json','utf8')).phase,'rolled-back');
    assert.deepEqual(JSON.parse(fs.readFileSync(prior.directory+'/database-rollback.json','utf8')).errors,[]);
    assert(fs.existsSync(prior.directory+'/http-rollback.json'));
  }else assert(!fs.existsSync(pointer),'Deployment already exists; inspect before restarting');packageCheck();
  for(const name of ['package-check.json','rehearsal-result.json'])assert.equal(JSON.parse(fs.readFileSync(prepared.directory+'/'+name,'utf8')).status,'PASSED');
  const directory='_backups/outsourcing-deploy-'+new Date().toISOString().replace(/[:.]/g,'-');fs.mkdirSync(directory,{recursive:true});
  plan={id:prepared.id,directory,release:prepared.release,archive:prepared.release+'.tar.gz',archiveSha256:prepared.archiveSha256,targetRoot:prepared.targetRoot,stage:'/home/i/idealstroy/_codex_releases/'+prepared.id,php:'/usr/local/bin/php7.3',createdUtc:new Date().toISOString()};
  if(prior){fs.writeFileSync(directory+'/previous-deployment.json',JSON.stringify(prior,null,2));plan.previousAttempt={id:prior.id,directory:prior.directory,rolledBack:true};}
  phase('backup-required');console.log(JSON.stringify({phase:plan.phase,directory,hostingChanged:false}));process.exit(0);
}
plan=JSON.parse(fs.readFileSync(pointer,'utf8'));
if(mode==='adopt-revision'){
  const failure=JSON.parse(fs.readFileSync(plan.directory+'/failed-install-cleanup.json','utf8'));
  assert.equal(failure.phase,'rolled-back');assert(failure.verifiedPartialRemoved);assert.notEqual(plan.id,prepared.id);packageCheck();
  for(const name of ['package-check.json','rehearsal-result.json'])assert.equal(JSON.parse(fs.readFileSync(prepared.directory+'/'+name,'utf8')).status,'PASSED');
  assert.deepEqual(JSON.parse(fs.readFileSync(plan.release+'/release.json','utf8')).files,JSON.parse(fs.readFileSync(prepared.release+'/release.json','utf8')).files);
  fs.writeFileSync(plan.directory+'/first-attempt-plan.json',JSON.stringify(plan,null,2));
  plan.previousAttempt={id:plan.id,stage:plan.stage,rolledBack:true};plan.id=prepared.id;plan.release=prepared.release;plan.archive=prepared.release+'.tar.gz';plan.archiveSha256=prepared.archiveSha256;plan.stage='/home/i/idealstroy/_codex_releases/'+prepared.id;
  phase('backups-verified');console.log(JSON.stringify({phase:plan.phase,revision:plan.id,originalFreshBackupsRetained:true}));process.exit(0);
}
assert.equal(plan.id,prepared.id);assert.equal(plan.targetRoot,'/home/i/idealstroy/isipto.ru/public_html');
assert(/^\/home\/i\/idealstroy\/_codex_releases\/outsourcing-[0-9TZ-]+$/.test(plan.stage));
if(mode==='backup-remote'||mode==='backup-remote-resume'||mode==='check-remote-backups'){
  assert.equal(plan.phase,'backup-required');
  const settings={mode:mode==='backup-remote'?'create':mode==='backup-remote-resume'?'resume':'check',id:plan.id,targetRoot:plan.targetRoot};
  const input='\n<?php $settings=json_decode(base64_decode("'+Buffer.from(JSON.stringify(settings)).toString('base64')+'"),true); ?>\n'+fs.readFileSync('deployment/outsourcing/backup-private-host.php','utf8');
  const report=JSON.parse((await run('ssh',[...ssh,host,`${plan.php} -d memory_limit=512M -f /dev/stdin`],mode,input)).trim());
  assert.equal(report.id,plan.id);assert.equal(report.status,'VERIFIED');
  for(const b of report.backups){fs.writeFileSync(plan.directory+'/'+b.metadata.file+'.json',JSON.stringify(b.metadata,null,2));fs.writeFileSync(plan.directory+'/'+b.metadata.file+'.verified.json',JSON.stringify(b.verification,null,2));}
  plan.backupStorage='hosting-private';plan.remoteBackupDirectory=report.directory;phase('backup-required');
  console.log(JSON.stringify(report));
}else if(mode==='backup-files'||mode==='backup-database'){
  assert.equal(plan.phase,'backup-required');const out=await run(process.execPath,['deployment/homepage/backup-host-stream.mjs',mode.slice(7),plan.directory],mode);console.log(out.trim());
}else if(mode==='verify-backups'){
  assert.equal(plan.phase,'backup-required');
  if(plan.backupStorage==='hosting-private'){
    const settings={mode:'check',id:plan.id,targetRoot:plan.targetRoot};
    const input='\n<?php $settings=json_decode(base64_decode("'+Buffer.from(JSON.stringify(settings)).toString('base64')+'"),true); ?>\n'+fs.readFileSync('deployment/outsourcing/backup-private-host.php','utf8');
    const report=JSON.parse((await run('ssh',[...ssh,host,`${plan.php} -d memory_limit=512M -f /dev/stdin`],'verify-private-backups',input)).trim());assert.equal(report.status,'VERIFIED');assert.equal(report.id,plan.id);
    for(const b of report.backups){const m=JSON.parse(fs.readFileSync(plan.directory+'/'+b.metadata.file+'.json','utf8'));assert.equal(m.sha256,b.metadata.sha256);assert(b.verification.gzipIntegrity&&b.verification.sha256Verified);}
    console.log(JSON.stringify({privateBackupsVerified:true,directory:report.directory}));
  }else for(const file of ['hosting-files.tar.gz','hosting-database.sql.gz'])console.log((await run(process.execPath,['deployment/homepage/verify-host-backup.mjs',plan.directory+'/'+file],'verify-'+file)).trim());
  phase('backups-verified');
}else if(mode==='stage'){
  assert.equal(plan.phase,'backups-verified');packageCheck();
  const receipt={releaseId:plan.id,targetRoot:plan.targetRoot,verifiedUtc:new Date().toISOString(),fullFilesBackupVerified:true,fullDatabaseBackupVerified:true};
  for(const [key,file] of [['filesSha256','hosting-files.tar.gz'],['databaseSha256','hosting-database.sql.gz']]){
    const proof=JSON.parse(fs.readFileSync(plan.directory+'/'+file+'.verified.json','utf8'));
    assert(proof.gzipIntegrity&&proof.sha256Verified);assert(file.endsWith('.sql.gz')?proof.dumpCompleted:proof.tarReadable);
    receipt[key]=JSON.parse(fs.readFileSync(plan.directory+'/'+file+'.json','utf8')).sha256;
  }
  fs.writeFileSync(plan.directory+'/backup-confirmation.json',JSON.stringify(receipt,null,2));
  await run('ssh',[...ssh,host,`umask 077; test ! -L /home/i/idealstroy/_codex_releases && test -d /home/i/idealstroy/_codex_releases && test ! -e ${plan.stage} && mkdir -m 700 ${plan.stage}`],'stage-directory');
  await run('scp',[...ssh,path.resolve(plan.archive),host+':'+plan.stage+'/release.tar.gz'],'upload');
  const remoteHash=await run('ssh',[...ssh,host,'sha256sum '+plan.stage+'/release.tar.gz'],'uploaded-hash');assert.equal(remoteHash.trim().split(/\s+/)[0],plan.archiveSha256);
  await run('ssh',[...ssh,host,`umask 077; tar --no-same-owner --no-same-permissions -xzf ${plan.stage}/release.tar.gz -C ${plan.stage}`],'extract');
  await run('scp',[...ssh,path.resolve(plan.directory+'/backup-confirmation.json'),host+':'+plan.stage+'/backup-confirmation.json'],'backup-proof');
  phase('staged');console.log(JSON.stringify({phase:plan.phase,publicWebsiteChanged:false}));
}else if(mode==='complete'){
  assert.equal(plan.phase,'applied');
  const web=JSON.parse(fs.readFileSync(plan.directory+'/publication-check.json','utf8'));assert.equal(web.status,'PASSED');
  const ui=JSON.parse(fs.readFileSync(plan.directory+'/browser-verification.json','utf8'));assert.equal(ui.status,'PASSED');assert.equal(ui.desktop.horizontalOverflow,false);assert.equal(ui.mobile.horizontalOverflow,false);assert(ui.consentExplicitlyCheckedForBothSubmissions);
  const crm=JSON.parse(fs.readFileSync(plan.directory+'/crm-tests-readonly.json','utf8'));assert.equal(crm.tests.length,2);
  for(const t of crm.tests){assert(!t.readFailed);assert.equal(t.deals.length,1);assert.equal(+t.deals[0].CATEGORY_ID,31);assert.equal(+t.deals[0].ASSIGNED_BY_ID,3280);}
  const report={status:'DEPLOYED_AND_VERIFIED',url:'https://isipto.ru/autsorsing-pto/',release:plan.id,backupStorage:plan.backupStorage,backupDirectory:plan.remoteBackupDirectory,files:8,sharedHandlerChanged:false,webhookChanged:false,controlDeals:crm.tests.map(t=>({test:t.test,id:t.deals[0].ID})),completedUtc:new Date().toISOString()};
  fs.writeFileSync(plan.directory+'/deployment-result.json',JSON.stringify(report,null,2));phase('verified');console.log(JSON.stringify(report));
}else if(['inspect','apply','rollback'].includes(mode)){
  if(mode==='inspect')assert.equal(plan.phase,'staged');if(mode==='apply')assert.equal(plan.phase,'inspected');
  const report=JSON.parse((await run('ssh',[...ssh,host,`${plan.php} -d memory_limit=512M ${plan.stage}/deploy.php ${mode} ${plan.targetRoot}`],mode)).trim());
  fs.writeFileSync(plan.directory+'/'+mode+'-result.json',JSON.stringify(report,null,2));phase(mode==='inspect'?'inspected':mode==='apply'?'applied':'rolled-back');console.log(JSON.stringify(report));
}else throw Error('Use start, backup-files, backup-database, verify-backups, stage, inspect, apply or rollback');
