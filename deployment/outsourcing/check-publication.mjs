import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import {spawnSync} from 'node:child_process';
import {createHash} from 'node:crypto';

const plan=JSON.parse(fs.readFileSync('_backups/active-outsourcing-deploy.json','utf8'));
const spec=JSON.parse(fs.readFileSync(plan.release+'/release.json','utf8'));
const mode=process.argv[2];assert(['before','after','rollback'].includes(mode));
const origin='https://isipto.ru';
const sha=b=>createHash('sha256').update(b).digest('hex');
const headers={'Cache-Control':'no-cache','User-Agent':'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130.0.0.0 Safari/537.36'};
const links=s=>[...s.matchAll(/<a\b[^>]*href=["']([^"']*)["']/gi)].map(x=>x[1]);
const headings=s=>[...s.matchAll(/<h[1-3]\b[^>]*>[\s\S]*?<\/h[1-3]>/gi)].map(x=>x[0]);
const fieldNames=s=>[...s.matchAll(/<form\b[^>]*>[\s\S]*?<\/form>/gi)].map(x=>[...x[0].matchAll(/\bname="([^"]*)"/g)].map(y=>y[1]));
async function page(url){const r=await fetch(url,{headers,signal:AbortSignal.timeout(45000)});const html=await r.text();return {status:r.status,url:r.url,title:html.match(/<title[^>]*>([\s\S]*?)<\/title>/i)?.[1],canonical:html.match(/<link\b[^>]*rel=["']canonical["'][^>]*>/i)?.[0],h1:html.match(/<h1\b[^>]*>[\s\S]*?<\/h1>/i)?.[0],links:links(html),html};}
const settings=Buffer.from(JSON.stringify({targetRoot:plan.targetRoot,mode,spec})).toString('base64');
const script='\n<?php $settings=json_decode(base64_decode("'+settings+'"),true); ?>\n'+fs.readFileSync('deployment/outsourcing/inspect-deployment.php','utf8');
const result=spawnSync('ssh',['-o','BatchMode=yes','-o','StrictHostKeyChecking=yes','-o','ConnectTimeout=15','-o','IdentitiesOnly=yes','-i',path.resolve('.ssh/isipto_transfer_ed25519_v3'),'idealstroy_1@idealstroy.beget.tech','/usr/local/bin/php7.3 -d memory_limit=512M -d display_errors=stderr -f /dev/stdin'],{input:script,encoding:'utf8',windowsHide:true,timeout:120000,maxBuffer:2*1024*1024});
fs.writeFileSync(plan.directory+'/database-'+mode+'.log',result.stderr||'');
assert.equal(result.status,0,'Read-only database inspection failed; see private log');const db=JSON.parse(result.stdout.trim());
fs.writeFileSync(plan.directory+'/database-'+mode+'.json',JSON.stringify(db,null,2));assert.deepEqual(db.errors,[]);
const routes=['/autsorsing-pto/','/','/upravlenie-stroitelstvom/','/vedenie-id/','/index.php?q=vedenie-id/','/o-kompanii/otzyivyi/'];
const current={};for(const route of routes){const p=await page(origin+route);if(route==='/autsorsing-pto/'||route==='/')assert.equal(p.status,200,'HTTP error: '+route);else assert(p.status<500,'Server error: '+route);fs.writeFileSync(plan.directory+'/'+(route==='/autsorsing-pto/'?'outsourcing':route==='/'?'home':'control-'+routes.indexOf(route))+'-'+mode+'.html',p.html);current[route]=p;}
if(mode==='after'||mode==='rollback'){
  const before=JSON.parse(fs.readFileSync(plan.directory+'/http-before.json','utf8'));
  const oldDB=JSON.parse(fs.readFileSync(plan.directory+'/database-before.json','utf8'));
  assert.deepEqual(db.normalizedTableHashes,oldDB.normalizedTableHashes,'Other resource fields or shared MODX elements changed');
  for(const route of routes){const p=current[route],old=before[route];for(const field of ['status','title','canonical','h1'])assert.equal(p[field],old[field],'SEO/HTTP metadata changed: '+route+' '+field);if(route!=='/autsorsing-pto/'||mode==='rollback')assert.deepEqual(p.links,old.links,'Other page links changed: '+route);}
  if(mode==='rollback')assert.equal(db.templateId,spec.baseline.templateId);
  else {
  const html=current['/autsorsing-pto/'].html;
  assert(!/https?:\/\/(localhost|127\.0\.0\.1)(?:[:/])/i.test(html),'Local URL leaked');assert(!html.includes('_preview/'));
  assert(!html.includes('[[!AjaxForm'));assert(!/Fatal error:|Parse error:|Uncaught exception/.test(html.replace(/<[^>]*>/g,'')));
  const local=await page('http://localhost:3000/autsorsing-pto/');assert.equal(local.status,200);
  const normalize=x=>x.replaceAll('http://localhost:3000',origin);
  assert.deepEqual(headings(html),headings(local.html).map(normalize));
  assert.deepEqual(fieldNames(html),fieldNames(local.html));assert.equal(fieldNames(html).length,4);
  // PDO-generated links may be absolute on hosting and relative on localhost: compare resolved destinations.
  const resolve=x=>new URL(x,origin+'/autsorsing-pto/').href;
  assert.deepEqual(links(html).map(resolve),links(local.html).map(normalize).map(resolve));
  for(const [file,hash]of Object.entries(spec.files).filter(([f])=>f.endsWith('.png'))){const r=await fetch(origin+'/'+file,{headers,signal:AbortSignal.timeout(45000)});assert.equal(r.status,200);assert.equal(sha(Buffer.from(await r.arrayBuffer())),hash);}
  fs.writeFileSync(plan.directory+'/publication-check.json',JSON.stringify({status:'PASSED',url:origin+'/autsorsing-pto/',forms:4,imagesVerified:4,linksAndHeadingsMatchLocal:true,otherResourcesAndSharedElementsUnchanged:true,crmDeliveryTested:false,checkedUtc:new Date().toISOString()},null,2));
  }
}
for(const p of Object.values(current))delete p.html;
fs.writeFileSync(plan.directory+'/http-'+mode+'.json',JSON.stringify(current,null,2));
console.log(JSON.stringify({mode,status:'PASSED',pagesChecked:routes.length,forms:fieldNames(fs.readFileSync(plan.directory+'/outsourcing-'+mode+'.html','utf8')).length,databaseWrites:0,crmWrites:0}));
