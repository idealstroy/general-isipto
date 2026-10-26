import fs from 'node:fs';import {spawn} from 'node:child_process';
const b=JSON.parse(fs.readFileSync('_backups/active-direct-bitrix.json','utf8'));
const home='modx-isipto/assets/templates/home_solution.html',original=fs.readFileSync(b.directory+'/home_solution.html','utf8');
if(fs.readFileSync(home,'utf8')!==original.replace('&hooks=`FormItSaveForm,roistat_hooks`','&hooks=`FormItSaveForm,bitrix24_hooks`'))throw Error('Homepage has other changes; refusing rollback');
const backup=fs.readFileSync(b.directory+'/modx-chunks.json');
const code="<?php $backup=json_decode(base64_decode('"+backup.toString('base64')+"'),true);\n"+fs.readFileSync('deployment/homepage/rollback-direct-bitrix-local.php','utf8').replace(/^<\?php\s*/,'');
const c=spawn('docker',['exec','--user','www-data','-i','modx-isipto-web-1','php'],{windowsHide:true});c.stdin.end(code);c.stdout.pipe(process.stdout);c.stderr.resume();const exit=await new Promise((r,j)=>{c.on('error',j);c.on('close',r)});if(exit)throw Error('Database rollback failed; homepage left unchanged');
// Exact backup restoration, only after the guard and DB rollback have passed.
fs.copyFileSync(b.directory+'/home_solution.html',home);
const sync="<?php require '/var/www/html/config.core.php';require MODX_CORE_PATH.'model/modx/modx.class.php';$m=new modX();$m->initialize('web');if($m->getOption('database_server')!=='db')exit(2);$m->getObject('modTemplate',84)->getContent();$m->cacheManager->refresh(array('resource'=>array('contexts'=>array('web')),'db'=>array(),'scripts'=>array()));";
const d=spawn('docker',['exec','--user','www-data','-i','modx-isipto-web-1','php'],{windowsHide:true});d.stdin.end(sync);d.stdout.resume();d.stderr.resume();const status=await new Promise((r,j)=>{d.on('error',j);d.on('close',r)});if(status)throw Error('Files restored; runtime template synchronization needs attention');
console.log('Original local bottom hook restored; .env and CRM receipts retained; production untouched');
