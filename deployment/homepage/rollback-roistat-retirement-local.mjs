import fs from 'node:fs';import path from 'node:path';import {spawn} from 'node:child_process';import {createHash} from 'node:crypto';
if(process.argv[2]!=='--restore')throw Error('Explicit --restore required; restores only this migration, never the full database');
const plan=JSON.parse(fs.readFileSync('_backups/active-roistat-retirement.json','utf8'));
const manifest=JSON.parse(fs.readFileSync(plan.directory+'/manifest.json','utf8'));
const result=JSON.parse(fs.readFileSync(plan.directory+'/migration-result.json','utf8'));
for(const f of result.postFiles){if(!fs.existsSync(f.file)||createHash('sha256').update(fs.readFileSync(f.file)).digest('hex')!==f.sha256)throw Error('Later changes found; refusing automatic rollback: '+f.file);}
const objects=JSON.parse(fs.readFileSync(plan.directory+'/database-objects.json','utf8'));
for(const e of objects.objects){if(!e.row.static||!e.row.static_file)continue;const target=path.resolve('modx-isipto',e.row.static_file);if(!target.startsWith(path.resolve('modx-isipto')+path.sep))throw Error('Unsafe static path');const original=path.join(plan.directory,'files','modx-isipto',e.row.static_file);fs.writeFileSync(target,fs.existsSync(original)?fs.readFileSync(original):e.runtime);}
// Restore only retired/common hook files and migrated templates/chunks, not the independent CRM client/routing.
for(const m of manifest){if(!/modx-isipto[\\/]assets[\\/](templates|chunks)[\\/]/.test(m.file)&&!/assets[\\/]snippets[\\/]roistat_hooks(?:_2)?\.php$/.test(m.file))continue;fs.mkdirSync(path.dirname(m.file),{recursive:true});fs.copyFileSync(path.join(plan.directory,'files',m.file),m.file);}
const backup=fs.readFileSync(plan.directory+'/database-objects.json').toString('base64');const code="<?php $backup=json_decode(base64_decode('"+backup+"'),true);\n"+fs.readFileSync('deployment/homepage/rollback-roistat-retirement-local.php','utf8').replace(/^<\?php\s*/,'');
const c=spawn('docker',['exec','--user','www-data','-i','modx-isipto-web-1','php'],{windowsHide:true});c.stdin.end(code);let out='';c.stdout.on('data',b=>out+=b);c.stderr.pipe(fs.createWriteStream(plan.directory+'/rollback-errors.log'));const exit=await new Promise((r,j)=>{c.on('error',j);c.on('close',r)});if(exit)throw Error('Rollback incomplete: see private backup log');console.log(out.trim());
