import fs from 'node:fs';
import path from 'node:path';
import {spawn} from 'node:child_process';
import readline from 'node:readline';
import {once} from 'node:events';
import assert from 'node:assert/strict';

const plan=JSON.parse(fs.readFileSync('_backups/active-outsourcing-preparation.json','utf8'));
const dir=plan.directory;const release='/releases/'+path.basename(plan.release);
const hostPHP='isipto-direct-rehearsal-php';
async function run(args,input,expected=0) {
  const c=spawn('docker',args,{windowsHide:true});let out='',err='';
  c.stdout.setEncoding('utf8');c.stderr.setEncoding('utf8');c.stdout.on('data',s=>out+=s);c.stderr.on('data',s=>err+=s);
  c.stdin.on('error',()=>{});c.stdin.end(input);
  const code=await new Promise((resolve,reject)=>{c.on('error',reject);c.on('close',resolve);});
  fs.appendFileSync(dir+'/rehearsal.log',JSON.stringify({args:args.slice(0,5),exit:code,out,err})+'\n');
  assert.equal(code,expected,'Rehearsal command failed; see private rehearsal.log');return out;
}
const php=(source)=>run(['exec','--user','www-data','-i',hostPHP,'php','-d','memory_limit=512M'],source);
const execute=(mode,...args)=>run(['exec','--user','www-data',hostPHP,'php','-d','memory_limit=512M','/var/www/outsourcing-stage/deploy.php',mode,'/var/www/html',...args]);
const inspectDB=async()=>JSON.parse(await php(fs.readFileSync('deployment/homepage/inspect-outsourcing-local.php','utf8').replace("$database_server !== 'db'","$database_server !== 'isipto-direct-rehearsal-db'")));
const manifest=JSON.parse(fs.readFileSync(plan.release+'/release.json','utf8'));
const sourceHashes=JSON.parse(fs.readFileSync(plan.release+'/checksums.json','utf8'));
assert(sourceHashes['deploy.php']);
const resume=process.argv[2]==='resume-fixture';
if(!resume){
await run(['exec',hostPHP,'sh','-c','test ! -e /var/www/outsourcing-stage && test ! -e /var/www/html/config.core.php']);
await run(['exec',hostPHP,'sh','-c','mkdir -p /var/www/html && tar -C /source --exclude=core/cache --exclude=core/packages --exclude=assets/templates/outsourcing-solution.html --exclude=assets/chunks/outsourcing-pto -cf - core config.core.php index.php assets/snippets assets/templates assets/chunks assets/components | tar -C /var/www/html -xf -']);
}
let config=fs.readFileSync('modx-isipto/core/config/config.inc.php','utf8');
config=config.replace(/^\$database_server\s*=.*?;\s*$/m,"$database_server = 'isipto-direct-rehearsal-db';").replace(/^\$database_dsn\s*=.*?;\s*$/m,"$database_dsn = 'mysql:host=isipto-direct-rehearsal-db;dbname=idealstroy_isipt;charset=utf8';");
assert(config.includes("$database_server = 'isipto-direct-rehearsal-db';"));
await run(['exec','-i',hostPHP,'sh','-c','tee /var/www/html/core/config/config.inc.php >/dev/null'],config);
await run(['exec',hostPHP,'sh','-c','mkdir -p /var/www/html/core/cache && chown www-data:www-data /var/www && chown -R www-data:www-data /var/www/html']);
const db=spawn('docker',['exec','-i','isipto-direct-rehearsal-db','mysql','-uroot','-prehearsal_only_root','idealstroy_isipt'],{windowsHide:true});
db.stdout.resume();db.stderr.pipe(fs.createWriteStream(dir+'/rehearsal-import.log'));db.stdin.on('error',()=>{});
const done=new Promise((r,j)=>{db.on('error',j);db.on('close',r);});
// Read the current local DB, not the old pre-CRM hosting snapshot. Never write to the source DB.
const dump=spawn('docker',['exec','modx-isipto-db-1','mysqldump','-umodx','-pmodx_local_password','--single-transaction','--skip-lock-tables','--no-tablespaces','--set-gtid-purged=OFF','idealstroy_isipt'],{windowsHide:true});
dump.stderr.pipe(fs.createWriteStream(dir+'/rehearsal-dump.log'));
const dumped=new Promise((r,j)=>{dump.on('error',j);dump.on('close',r);});
const lines=readline.createInterface({input:dump.stdout,crlfDelay:Infinity});
for await (const line of lines) {
  if (/^INSERT INTO `(vec_session|vec_formit_forms|vec_manager_log|vec_event_log)`/.test(line)) continue;
  if (!db.stdin.write(line+'\n')) await once(db.stdin,'drain');
}
assert.equal(await dumped,0,'Read-only local DB export failed');
db.stdin.end();assert.equal(await done,0,'Fixture database import failed');
// Return ONLY the sandbox's target page to its pre-publication native template.
const restore=fs.readFileSync('deployment/homepage/rollback-outsourcing-local.php','utf8').replace("$database_server !== 'db'","$database_server !== 'isipto-direct-rehearsal-db'");
await php(restore);
if(!resume)await run(['exec',hostPHP,'sh','-c','cp -a '+release+' /var/www/outsourcing-stage && chown -R www-data:www-data /var/www/outsourcing-stage']);
await run(['exec','--user','www-data',hostPHP,'php','-l','/var/www/outsourcing-stage/deploy.php']);
const info=JSON.parse(await execute('audit'));assert.equal(info.phpCli,'7.3.33');
assert.equal(info.snapshot.root,'/var/www/html');assert.equal(info.snapshot.nameCollisions.template,0);
const fixtureSpec={...manifest,targetRoot:'/var/www/html',baseline:info.snapshot};
await run(['exec','--user','www-data','-i',hostPHP,'sh','-c','tee /var/www/outsourcing-stage/release.json >/dev/null'],JSON.stringify(fixtureSpec));
const before=await inspectDB();
const inspect=JSON.parse(await execute('inspect'));assert(inspect.readyForApplyAfterFreshBackup);
const denied=await run(['exec','--user','www-data',hostPHP,'php','/var/www/outsourcing-stage/deploy.php','apply','/var/www/html'],undefined,1);
assert.deepEqual((await inspectDB()).hashes,before.hashes,'Unconfirmed publication changed the DB');
const applied=JSON.parse(await execute('apply','--offline-rehearsal'));assert.equal(applied.phase,'applied');
const after=await inspectDB();assert.equal(after.template.static_file,'assets/templates/outsourcing-solution.html');
assert.deepEqual(after.consumers.map(r=>r.id),['3812']);
for (const key of ['other_resources','site_tmplvars','site_tmplvar_contentvalues','site_snippets','site_plugins','site_plugin_events']) assert.equal(after.hashes[key],before.hashes[key],key);
for (const table of ['site_templates','site_htmlsnippets','site_tmplvar_templates']) assert.equal(after.hashes['existing_'+table],before.hashes[table]);
const render=`<?php define('MODX_API_MODE',true);require '/var/www/html/config.core.php';require_once MODX_CORE_PATH.'model/modx/modx.class.php';$_SERVER['HTTP_HOST']='localhost:3000';$_SERVER['REQUEST_URI']='/autsorsing-pto/';$modx=new modX();$modx->initialize('web');$r=$modx->getObject('modResource',array('uri'=>'autsorsing-pto/','context_key'=>'web'));$modx->resource=$r;$modx->resourceIdentifier=$r->get('id');$modx->resourceMethod='id';$modx->resourceGenerated=true;$modx->getResponse();$modx->response->outputContent();`;
const html=await php(render);fs.writeFileSync(dir+'/rehearsal-rendered.html',html);
assert(html.includes('Организуем службу ПТО на вашем объекте'));
assert(!html.includes('[[!AjaxForm'));assert(!html.includes('_preview/'));
assert.equal((html.match(/<form\b/gi)||[]).length,4);
assert.equal((html.match(/value="Получить расчёт"/g)||[]).length,3);
// A later edit must stop rollback before touching the resource or other files.
await run(['exec','--user','www-data',hostPHP,'sh','-c','printf "\\n" >> /var/www/html/assets/templates/outsourcing-solution.html']);
await run(['exec','--user','www-data',hostPHP,'php','/var/www/outsourcing-stage/deploy.php','rollback','/var/www/html'],undefined,1);
assert.equal((await inspectDB()).resource.template,after.resource.template);
await run(['exec','--user','www-data',hostPHP,'cp','/var/www/outsourcing-stage/payload/assets/templates/outsourcing-solution.html','/var/www/html/assets/templates/outsourcing-solution.html']);
const reverted=JSON.parse(await execute('rollback'));assert.equal(reverted.phase,'rolled-back');
assert.deepEqual((await inspectDB()).hashes,before.hashes,'Rollback did not restore all audited MODX tables');
for(const file of Object.keys(manifest.files)) await run(['exec','--user','www-data',hostPHP,'test','!','-e','/var/www/html/'+file]);
const report={status:'PASSED',php:'7.3.33',externalNetwork:false,realWebhookMounted:false,realFormsSubmitted:false,productionSiteChanged:false,files:8,nativeFormsRendered:4,unconfirmedApplyBlocked:true,laterEditsBlockRollback:true,otherResourcesAndHandlersUnchanged:true,rollbackRestoresAuditedTables:true};
fs.writeFileSync(dir+'/rehearsal-result.json',JSON.stringify(report,null,2));console.log(JSON.stringify(report,null,2));
