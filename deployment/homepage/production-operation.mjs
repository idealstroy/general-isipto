import fs from 'node:fs';
import path from 'node:path';
import {spawn} from 'node:child_process';
import {createHash} from 'node:crypto';
const root=process.cwd(),plan=JSON.parse(fs.readFileSync('_backups/active-homepage-deploy.json','utf8')),operation=process.argv[2];
const sshArgs=['-o','BatchMode=yes','-o','StrictHostKeyChecking=yes','-o','ConnectTimeout=15','-o','ServerAliveInterval=30','-o','IdentitiesOnly=yes','-i',path.join(root,'.ssh/isipto_transfer_ed25519_v3')],host='idealstroy_1@idealstroy.beget.tech';
if(!/^\/home\/i\/idealstroy\/_codex_releases\/homepage-[A-Za-z0-9]+$/.test(plan.remoteStage))throw Error('Unsafe stage path');
async function run(exe,args,label,capture=false,inputFile=null){
 const child=spawn(exe,args,{windowsHide:true});let stdout='';const log=fs.createWriteStream(path.join(plan.localBackup,label+'.log'),{flags:'a'});
 child.stderr.on('data',b=>log.write(b));child.stdout.on('data',b=>{log.write(b);if(capture)stdout+=b;else process.stdout.write(b);});
 child.stdin.on('error',()=>{});if(inputFile)fs.createReadStream(inputFile).pipe(child.stdin);else child.stdin.end();
 const code=await new Promise((resolve,reject)=>{child.on('error',reject);child.on('close',resolve);});await new Promise(r=>log.end(r));if(code!==0)throw Error(label+' failed, exit '+code+'; see private log');return stdout;
}
if(operation==='backup-files'||operation==='backup-database'){
 await run(process.execPath,['deployment/homepage/backup-host-stream.mjs',operation.slice(7),plan.localBackup],operation);
}else if(operation==='verify-backups'){
 for(const f of ['hosting-files.tar.gz','hosting-database.sql.gz'])await run(process.execPath,['deployment/homepage/verify-host-backup.mjs',path.join(plan.localBackup,f)],'verify-'+f);
}else if(operation==='stage'){
 for(const f of ['hosting-files.tar.gz','hosting-database.sql.gz'])if(!fs.existsSync(path.join(plan.localBackup,f+'.verified.json')))throw Error('Verified fresh backup required');
 const bundle=path.resolve(plan.localBackup,'release-bundle.tar.gz');
 await run('tar',['-czf',bundle,'-C',path.resolve(plan.sourceRelease),'.','-C',path.resolve('deployment/homepage'),'production-deploy.php','-C',path.resolve(plan.localBackup),'approved-baseline.json','plan.json'],'bundle');
 const digest=createHash('sha256').update(fs.readFileSync(bundle)).digest('hex');
 await run('ssh',[...sshArgs,host,`test ! -L /home/i/idealstroy/_codex_releases && if test ! -d /home/i/idealstroy/_codex_releases; then mkdir -m 700 /home/i/idealstroy/_codex_releases; fi && test ! -e ${plan.remoteStage} && mkdir -m 700 ${plan.remoteStage}`],'mkdir-stage');
 await run('scp',[...sshArgs,bundle,host+':'+plan.remoteStage+'/release-bundle.tar.gz'],'upload-bundle');
 const output=await run('ssh',[...sshArgs,host,`sha256sum ${plan.remoteStage}/release-bundle.tar.gz`],'remote-bundle-hash',true);
 if(output.trim().split(/\s+/)[0]!==digest)throw Error('Uploaded bundle hash differs');
 await run('ssh',[...sshArgs,host,`tar --no-same-owner --no-same-permissions -xzf ${plan.remoteStage}/release-bundle.tar.gz -C ${plan.remoteStage} && chmod 700 ${plan.remoteStage}`],'extract-stage');
 fs.writeFileSync(path.join(plan.localBackup,'staged.json'),JSON.stringify({remoteStage:plan.remoteStage,sha256:digest,stagedAt:new Date().toISOString()},null,2));console.log('Release staged outside public root; homepage not switched');
}else if(operation==='db-snapshot'){
 const phase=process.argv[3];if(!['before','after'].includes(phase))throw Error('Expected before or after');
 const output=await run('ssh',[...sshArgs,host,`ISIPTO_AUDIT_DB_ONLY=1 ${plan.php} -d memory_limit=1024M`],phase+'-database',true,'deployment/homepage/audit-host-readonly.php');
 const report=JSON.parse(output);fs.writeFileSync(path.join(plan.localBackup,phase+'-database.json'),JSON.stringify(report));console.log(JSON.stringify({phase,php:report.phpCli,home:report.database.homepage}));
}else if(operation==='collect-state'){
 await run('scp',[...sshArgs,host+':'+plan.remoteStage+'/state.json',path.join(plan.localBackup,'remote-state.json')],'collect-state');console.log('Rollback journal saved locally');
}else if(['inspect','prepare','activate','status','rollback','lead-status'].includes(operation)){
 const output=await run('ssh',[...sshArgs,host,`${plan.php} -d memory_limit=512M -d display_errors=stderr ${plan.remoteStage}/production-deploy.php ${operation}`],operation,true);
 const report=JSON.parse(output.trim());fs.writeFileSync(path.join(plan.localBackup,operation+'-result.json'),JSON.stringify(report,null,2));console.log(JSON.stringify(report));
}else throw Error('Unknown operation');
