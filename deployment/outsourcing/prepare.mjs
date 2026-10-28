import fs from 'node:fs';
import path from 'node:path';
import {spawnSync} from 'node:child_process';
import {createHash} from 'node:crypto';
import assert from 'node:assert/strict';

const mode=process.argv[2];
const pointer='_backups/active-outsourcing-preparation.json';
const targetRoot='/home/i/idealstroy/isipto.ru/public_html';
const sha=b=>createHash('sha256').update(b).digest('hex');
const files=['assets/templates/outsourcing-solution.html',...['cost','time','footer'].map(t=>'assets/chunks/outsourcing-pto/form-'+t+'.html'),...['result-clock.png','pto-ai-monitor.png','form-portrait-extended.png','schedule-portrait-hd.png'].map(f=>'images/outsourcing-pto/'+f)];
function run(exe,args,input,log) {
  const r=spawnSync(exe,args,{input,encoding:'utf8',windowsHide:true,timeout:120000,maxBuffer:8*1024*1024});
  if(log)fs.writeFileSync(log,(r.stdout||'')+'\n'+(r.stderr||''));
  assert.equal(r.status,0,'Command failed; inspect private log: '+log);return r.stdout;
}
function hostingAudit(directory,label) {
  // The Beget login wrapper consumes the first stdin character: preserve the PHP opening tag.
  const script='\n<?php $argv=array("deploy.php","audit","'+targetRoot+'"); ?>\n'+fs.readFileSync('deployment/outsourcing/deploy.php','utf8');
  const output=run('ssh',['-o','BatchMode=yes','-o','StrictHostKeyChecking=yes','-o','ConnectTimeout=15','-o','IdentitiesOnly=yes','-i',path.resolve('.ssh/isipto_transfer_ed25519_v3'),'idealstroy_1@idealstroy.beget.tech','/usr/local/bin/php7.3 -d memory_limit=512M -d display_errors=stderr -d log_errors=0 -f /dev/stdin'],script,directory+'/'+label+'.log');
  const audit=JSON.parse(output.trim());assert.equal(audit.mode,'READ_ONLY');assert.equal(audit.snapshot.root,targetRoot);return audit;
}
if(mode==='refresh-after-crm-fix') {
  const previous=JSON.parse(fs.readFileSync(pointer,'utf8'));
  const deployment=JSON.parse(fs.readFileSync('_backups/active-outsourcing-deploy.json','utf8'));
  const fix=JSON.parse(fs.readFileSync('_backups/active-crm-ui-fix.json','utf8'));
  assert.equal(deployment.phase,'rolled-back');assert.equal(deployment.id,previous.id);
  assert.equal(fix.phase,'verified');assert.equal(fix.targetRoot,targetRoot);
  const fixProof=JSON.parse(fs.readFileSync(fix.directory+'/verify-result.json','utf8'));
  assert.equal(fixProof.phase,'verified');assert.equal(fixProof.crmCalls,0);
  const patch=JSON.parse(fs.readFileSync(fix.release+'/patch.json','utf8'));
  const original=JSON.parse(fs.readFileSync(previous.directory+'/hosting-audit.json','utf8'));
  const priorSpec=JSON.parse(fs.readFileSync(previous.release+'/release.json','utf8'));
  assert.equal(sha(fs.readFileSync(previous.release+'.tar.gz')),previous.archiveSha256);
  for(const [file,hash] of Object.entries(JSON.parse(fs.readFileSync(previous.release+'/checksums.json','utf8'))))assert.equal(sha(fs.readFileSync(previous.release+'/'+file)),hash);
  assert.equal(sha(fs.readFileSync('deployment/outsourcing/deploy.php')),sha(fs.readFileSync(previous.release+'/deploy.php')),'Installer changed; a new rehearsal is required');
  for(const [file,hash] of Object.entries(priorSpec.files))assert.equal(sha(fs.readFileSync('modx-isipto/'+file)),hash,'Page changed; a new rehearsal is required');
  const stamp=new Date().toISOString(),id='outsourcing-'+stamp.replace(/[-:.]/g,'');
  const directory='_backups/outsourcing-preparation-'+stamp.replace(/[:.]/g,'-');fs.mkdirSync(directory,{recursive:true});
  const audit=hostingAudit(directory,'ssh-audit');
  const hook='snippet:bitrix24_hooks',file='assets/snippets/bitrix24_hooks.php';
  assert.equal(original.snapshot.dependencies[hook].codeHash,patch.files[file].before);
  assert.equal(audit.snapshot.dependencies[hook].codeHash,patch.files[file].after);
  assert.equal(sha(fs.readFileSync('modx-isipto/'+file)),patch.files[file].after);
  const normalized=structuredClone(audit.snapshot);
  normalized.dependencies[hook].codeHash=original.snapshot.dependencies[hook].codeHash;
  normalized.dependencies[hook].rowHash=original.snapshot.dependencies[hook].rowHash;
  assert.deepEqual(normalized,original.snapshot,'Unexpected hosting changes beyond the verified CRM fix');
  fs.writeFileSync(directory+'/hosting-audit.json',JSON.stringify(audit,null,2));
  fs.writeFileSync(directory+'/previous-preparation.json',JSON.stringify(previous,null,2));
  const plan={id,directory,targetRoot,release:'dist/'+id,createdUtc:stamp,hostingChanged:false,revisionScope:'verified-crm-baseline-only'};
  fs.writeFileSync(pointer,JSON.stringify(plan,null,2));
  console.log(JSON.stringify({mode:'READ_ONLY',directory,revisionScope:plan.revisionScope,unexpectedHostingChanges:false,hostingChanged:false}));
} else if(mode==='audit-host'||mode==='revise-after-rollback') {
  let previous=null;
  if(mode==='revise-after-rollback') {
    previous=JSON.parse(fs.readFileSync(pointer,'utf8'));
    const deployment=JSON.parse(fs.readFileSync('_backups/active-outsourcing-deploy.json','utf8'));
    const failure=JSON.parse(fs.readFileSync(deployment.directory+'/failed-install-cleanup.json','utf8'));
    assert.equal(failure.phase,'rolled-back');assert(failure.verifiedPartialRemoved);
  } else assert(!fs.existsSync(pointer),'Preparation already exists; inspect its audit before creating another');
  const stamp=new Date().toISOString();const id='outsourcing-'+stamp.replace(/[-:.]/g,'');
  const directory='_backups/outsourcing-preparation-'+stamp.replace(/[:.]/g,'-');
  fs.mkdirSync(directory,{recursive:true});
  const audit=hostingAudit(directory,'ssh-audit');
  if(previous){
    assert.deepEqual(audit.snapshot,JSON.parse(fs.readFileSync(previous.directory+'/hosting-audit.json','utf8')).snapshot,'Hosting state did not return to the original baseline');
    fs.writeFileSync(directory+'/previous-preparation.json',JSON.stringify(previous,null,2));
  }
  fs.writeFileSync(directory+'/hosting-audit.json',JSON.stringify(audit,null,2));
  const plan={id,directory,targetRoot,release:'dist/'+id,createdUtc:stamp,hostingChanged:false};
  fs.writeFileSync(pointer,JSON.stringify(plan,null,2));
  console.log(JSON.stringify({mode:'READ_ONLY',phpCli:audit.phpCli,resourceId:audit.snapshot.resourceId,otherTemplateConsumers:audit.snapshot.consumers.length-1,targetFileCollisions:Object.values(audit.snapshot.files).filter(Boolean).length,nameCollisions:audit.snapshot.nameCollisions,directory,hostingChanged:false}));
} else if(mode==='build') {
  const p=JSON.parse(fs.readFileSync(pointer,'utf8'));
  const audit=JSON.parse(fs.readFileSync(p.directory+'/hosting-audit.json','utf8'));
  assert(!fs.existsSync(p.release),'Refusing to overwrite a sealed package');
  for(const [file,hash] of Object.entries(audit.snapshot.files))assert.equal(hash,null,'Existing hosting file requires investigation: '+file);
  for(const [name,count] of Object.entries(audit.snapshot.nameCollisions))assert.equal(count,0,'Existing MODX name: '+name);
  const template=fs.readFileSync('modx-isipto/'+files[0],'utf8');
  assert(!template.includes('_preview/'));assert.equal((template.match(/&hooks=`FormItSaveForm,bitrix24_hooks`/g)||[]).length,3);
  const checksums={};fs.mkdirSync(p.release,{recursive:true});
  for(const file of files) {
    const data=fs.readFileSync('modx-isipto/'+file);checksums[file]=sha(data);
    const dest=p.release+'/payload/'+file;fs.mkdirSync(path.dirname(dest),{recursive:true});fs.copyFileSync('modx-isipto/'+file,dest);
  }
  const spec={id:p.id,targetRoot,createdUtc:new Date().toISOString(),files:checksums,baseline:audit.snapshot};
  fs.writeFileSync(p.release+'/release.json',JSON.stringify(spec,null,2));
  fs.copyFileSync('deployment/outsourcing/deploy.php',p.release+'/deploy.php');
  fs.copyFileSync('deployment/outsourcing/README.md',p.release+'/README.md');
  const all={};for(const file of ['deploy.php','release.json','README.md',...files.map(f=>'payload/'+f)])all[file]=sha(fs.readFileSync(p.release+'/'+file));
  fs.writeFileSync(p.release+'/checksums.json',JSON.stringify(all,null,2));
  run('tar',['-czf',path.resolve(p.release+'.tar.gz'),'-C',path.resolve(p.release),'.'],undefined,p.directory+'/archive.log');
  const archiveSha256=sha(fs.readFileSync(p.release+'.tar.gz'));
  fs.writeFileSync(p.release+'.tar.gz.sha256',archiveSha256+'  '+path.basename(p.release)+'.tar.gz\n');
  p.phase='BUILT_NOT_PUBLISHED';p.archiveSha256=archiveSha256;fs.writeFileSync(pointer,JSON.stringify(p,null,2));
  console.log(JSON.stringify({phase:p.phase,release:p.release,files:files.length,bytes:fs.statSync(p.release+'.tar.gz').size,archiveSha256,hostingChanged:false}));
} else if(mode==='recheck-host') {
  const p=JSON.parse(fs.readFileSync(pointer,'utf8'));
  const original=JSON.parse(fs.readFileSync(p.directory+'/hosting-audit.json','utf8'));
  const current=hostingAudit(p.directory,'ssh-recheck');
  assert.deepEqual(current.snapshot,original.snapshot,'Hosting changed since preparation; inspect before publication');
  fs.writeFileSync(p.directory+'/hosting-recheck.json',JSON.stringify({checkedUtc:new Date().toISOString(),...current},null,2));
  console.log(JSON.stringify({mode:'READ_ONLY',unchangedSincePreparation:true,phpCli:current.phpCli,hostingChanged:false}));
} else throw new Error('Use audit-host, revise-after-rollback, build or recheck-host');
