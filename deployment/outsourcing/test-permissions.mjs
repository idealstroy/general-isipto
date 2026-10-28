import fs from 'node:fs';import assert from 'node:assert/strict';import {spawnSync} from 'node:child_process';
const p=JSON.parse(fs.readFileSync('_backups/active-outsourcing-preparation.json','utf8'));
const source=fs.readFileSync(p.release+'/deploy.php','utf8');
const helper=source.match(/function retainGroup\(\$file,\$group\) \{[\s\S]*?\n\}/)?.[0];assert(helper);
const test=`<?php namespace OutsourcingPermissionTest;
$actual=601;$calls=0;
function filegroup($file){global $actual;return $actual;}
function chgrp($file,$group){global $calls;$calls++;return false;}
${helper}
if(!retainGroup('fixture',601)||$calls!==0)exit(1);
$actual=999;if(retainGroup('fixture',601)||$calls!==1)exit(2);
echo json_encode(array('sameGroupSkipsDeniedChgrp'=>true,'wrongGroupRefusesInstall'=>true,'php'=>PHP_VERSION))."\\n";`;
const r=spawnSync('docker',['exec','--user','www-data','-i','isipto-direct-rehearsal-php','php'],{input:test,encoding:'utf8',windowsHide:true,timeout:60000});assert.equal(r.status,0,r.stderr);
const checks=JSON.parse(r.stdout.trim());assert.equal(checks.php,'7.3.33');
const old=JSON.parse(fs.readFileSync(p.directory+'/previous-preparation.json','utf8'));
const oldSpec=JSON.parse(fs.readFileSync(old.release+'/release.json','utf8')),newSpec=JSON.parse(fs.readFileSync(p.release+'/release.json','utf8'));
assert.deepEqual(oldSpec.files,newSpec.files,'Page payload changed in permission-only revision');
const prior=JSON.parse(fs.readFileSync(old.directory+'/rehearsal-result.json','utf8'));assert.equal(prior.status,'PASSED');
const report={...prior,revision:p.id,fullRehearsalRelease:old.id,revisionScope:'Installer filesystem permissions only; all eight page files unchanged',permissionsRegressionChecks:checks};
fs.writeFileSync(p.directory+'/rehearsal-result.json',JSON.stringify(report,null,2));console.log(JSON.stringify(report,null,2));
