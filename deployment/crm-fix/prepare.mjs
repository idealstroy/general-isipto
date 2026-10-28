import fs from 'node:fs';import path from 'node:path';import assert from 'node:assert/strict';import {createHash} from 'node:crypto';
const pointer='_backups/active-crm-ui-fix.json';assert(!fs.existsSync(pointer),'Existing CRM fix plan; inspect before restarting');
const stamp=new Date().toISOString(),id='crm-ui-'+stamp.replace(/[-:.]/g,''),directory='_backups/crm-ui-fix-'+stamp.replace(/[:.]/g,'-');
fs.mkdirSync(directory,{recursive:true});
const files=['assets/snippets/bitrix24_hooks.php','core/components/ajaxform/model/ajaxform/ajaxform.class.php'];
const hashes={};for(const file of files){const dest=directory+'/local-before/'+file;fs.mkdirSync(path.dirname(dest),{recursive:true});fs.copyFileSync('modx-isipto/'+file,dest);hashes[file]=createHash('sha256').update(fs.readFileSync(dest)).digest('hex');}
const plan={id,directory,targetRoot:'/home/i/idealstroy/isipto.ru/public_html',files,oldHashes:hashes,createdUtc:stamp,phase:'backed-up-local'};fs.writeFileSync(pointer,JSON.stringify(plan,null,2));
// Validate and probe the current root .env in memory; never print a URL, token, user ID or raw response.
let probe={source:'root .env',status:'NOT_CONFIGURED'};
if(fs.existsSync('.env')){
 const values=[];for(const line of fs.readFileSync('.env','utf8').replace(/^\uFEFF/,'').split(/\r?\n/)){const m=line.match(/^\s*(?:export\s+)?BITRIX24_WEBHOOK_URL\s*=\s*(.*?)\s*$/);if(m)values.push(m[1].replace(/^(['"])(.*)\1$/,'$2'));}
 if(values.length===1&&values[0]){
  const base=values[0].replace(/\/profile\.json$/,'/');let u;try{u=new URL(base);}catch{}
  if(u&&u.protocol==='https:'&&u.hostname==='isipto.bitrix24.ru'&&/^\/rest\/\d+\/[A-Za-z0-9_-]+\/$/.test(u.pathname)&&!u.search&&!u.hash&&!u.username&&!u.port){
   try{const r=await fetch(base+'crm.deal.fields.json',{method:'POST',redirect:'error',signal:AbortSignal.timeout(20000)});const body=await r.json();probe={source:'root .env',status:r.ok&&body.result?'OK':'FAILED',httpStatus:r.status,apiError:typeof body.error==='string'&&/^[A-Z0-9_]+$/.test(body.error)?body.error:null};}catch{probe={source:'root .env',status:'TRANSPORT_FAILED'};}
  }else probe={source:'root .env',status:'INVALID_FORMAT'};
 }else probe={source:'root .env',status:'MISSING_OR_DUPLICATE_VALUE'};
}
fs.writeFileSync(directory+'/root-env-auth.json',JSON.stringify(probe,null,2));console.log(JSON.stringify({directory,localFilesBackedUp:files.length,webhookProbe:probe,hostingChanged:false},null,2));
