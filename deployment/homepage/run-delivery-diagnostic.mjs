import fs from 'node:fs';
import {spawn} from 'node:child_process';
const p=JSON.parse(fs.readFileSync('_backups/active-homepage-deploy.json','utf8'));
const c=spawn('ssh',['-o','BatchMode=yes','-o','StrictHostKeyChecking=yes','-o','ConnectTimeout=15','-o','IdentitiesOnly=yes','-i','.ssh/isipto_transfer_ed25519_v3','idealstroy_1@idealstroy.beget.tech',`${p.php} -d display_errors=stderr`],{windowsHide:true});
fs.createReadStream('deployment/homepage/diagnose-delivery-readonly.php').pipe(c.stdin);
let data='';c.stdout.on('data',b=>data+=b);c.stderr.on('data',()=>{});
const code=await new Promise((r,j)=>{c.on('error',j);c.on('close',r)});
if(code!==0)throw Error('Read-only diagnostic failed: '+code);
const result=JSON.parse(data);fs.writeFileSync(p.localBackup+'/delivery-diagnostic.json',JSON.stringify(result,null,2));console.log(JSON.stringify(result,null,2));
