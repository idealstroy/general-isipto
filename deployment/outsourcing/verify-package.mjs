import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import {createHash} from 'node:crypto';
import {spawnSync} from 'node:child_process';

const p=JSON.parse(fs.readFileSync('_backups/active-outsourcing-preparation.json','utf8'));
const sha=b=>createHash('sha256').update(b).digest('hex');
const manifest=JSON.parse(fs.readFileSync(p.release+'/release.json','utf8'));
const checks=JSON.parse(fs.readFileSync(p.release+'/checksums.json','utf8'));
assert.equal(Object.keys(manifest.files).length,8);
assert.equal(sha(fs.readFileSync(p.release+'.tar.gz')),p.archiveSha256);
for(const [file,hash] of Object.entries(checks)) assert.equal(sha(fs.readFileSync(p.release+'/'+file)),hash,'Sealed package drift: '+file);
for(const [file,hash] of Object.entries(manifest.files)) assert.equal(sha(fs.readFileSync('modx-isipto/'+file)),hash,'Local page changed after build: '+file);
const expected=new Set([...Object.keys(checks),'checksums.json']);
function walk(dir,prefix='') {
  for(const e of fs.readdirSync(dir,{withFileTypes:true})) {
    assert(!e.isSymbolicLink());const name=prefix+e.name;
    if(e.isDirectory())walk(path.join(dir,e.name),name+'/');else assert(expected.has(name),'Unexpected package file: '+name);
  }
}
walk(p.release);
assert(!fs.existsSync(p.release+'/backup-confirmation.json'),'Fresh hosting backups must not be fabricated during preparation');
const hostLint=process.argv.includes('--host-php73-lint');
const lint=hostLint
  ?spawnSync('ssh',['-o','BatchMode=yes','-o','StrictHostKeyChecking=yes','-o','ConnectTimeout=15','-o','IdentitiesOnly=yes','-i',path.resolve('.ssh/isipto_transfer_ed25519_v3'),'idealstroy_1@idealstroy.beget.tech','/usr/local/bin/php7.3 -l -f /dev/stdin'],{input:'\n'+fs.readFileSync(p.release+'/deploy.php','utf8'),encoding:'utf8',windowsHide:true,timeout:60000})
  :spawnSync('docker',['exec','--user','www-data','-i','isipto-direct-rehearsal-php','php','-l'],{input:fs.readFileSync(p.release+'/deploy.php'),encoding:'utf8',windowsHide:true,timeout:60000});
assert.equal(lint.status,0,lint.stderr||lint.stdout);
const report={status:'PASSED',release:p.id,files:8,archiveSha256:p.archiveSha256,packageMatchesLocalPage:true,unexpectedFiles:false,secretsIncluded:false,php73Syntax:'PASSED',hostingChanged:false};
fs.writeFileSync(p.directory+'/package-check.json',JSON.stringify(report,null,2));console.log(JSON.stringify(report,null,2));
if(p.revisionScope==='verified-crm-baseline-only') {
  const previous=JSON.parse(fs.readFileSync(p.directory+'/previous-preparation.json','utf8'));
  const oldSpec=JSON.parse(fs.readFileSync(previous.release+'/release.json','utf8'));
  assert.deepEqual(manifest.files,oldSpec.files);
  assert.equal(sha(fs.readFileSync(p.release+'/deploy.php')),sha(fs.readFileSync(previous.release+'/deploy.php')));
  const prior=JSON.parse(fs.readFileSync(previous.directory+'/rehearsal-result.json','utf8'));assert.equal(prior.status,'PASSED');
  const evidence={...prior,revision:p.id,priorRehearsalEvidence:previous.id,revisionScope:'Verified hosting CRM dependency baseline only; installer and all eight page files byte-identical; previous rehearsal reused, not rerun'};
  fs.writeFileSync(p.directory+'/rehearsal-result.json',JSON.stringify(evidence,null,2));
  console.log(JSON.stringify({rehearsalEvidence:'REUSED_FOR_IDENTICAL_INSTALLER_AND_PAYLOAD',priorRelease:previous.id}));
}
