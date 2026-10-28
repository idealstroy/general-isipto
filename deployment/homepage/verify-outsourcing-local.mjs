import fs from 'node:fs';
import assert from 'node:assert/strict';
import crypto from 'node:crypto';
import {spawnSync} from 'node:child_process';

const root='modx-isipto';
const backup='_backups/outsourcing-promotion-local-2026-10-27';
const hash=s=>crypto.createHash('sha256').update(s).digest('hex');
const read=p=>fs.readFileSync(p,'utf8');
const php=file=>{
  const r=spawnSync('docker',['exec','--user','www-data','-i','modx-isipto-web-1','php'],{input:read('deployment/homepage/'+file),encoding:'utf8',windowsHide:true,maxBuffer:20*1024*1024});
  assert.equal(r.status,0,r.stderr); return JSON.parse(r.stdout);
};
const request=async path=>{const r=await fetch('http://localhost:3000'+path);return {status:r.status,html:await r.text()};};
const links=s=>[...s.matchAll(/\bhref="([^"]+)"/g)].map(m=>m[1]);
const fields=s=>[...s.matchAll(/<form\b[^>]*>[\s\S]*?<\/form>/gi)].map(m=>[...m[0].matchAll(/\bname="([^"]+)"/g)].map(x=>x[1]));

{
  const before=JSON.parse(read(backup+'/before.json'));
  const after=php('inspect-outsourcing-local.php');
  assert.notEqual(after.resource.template,before.resource.template);
  assert.equal(after.template.static_file,'assets/templates/outsourcing-solution.html');
  assert.equal(after.template.static,'1');
  assert.deepEqual(after.consumers.map(x=>x.id),['3812']);
  const stripTemplate=r=>{const x={...r};delete x.template;return x;};
  assert.deepEqual(stripTemplate(after.resource),stripTemplate(before.resource),'Other resource properties changed');
  assert.deepEqual(after.tvLinks.map(x=>x.tmplvarid),before.tvLinks.map(x=>x.tmplvarid));
  for(const table of ['other_resources','site_tmplvars','site_tmplvar_contentvalues','site_snippets','site_plugins','site_plugin_events']) assert.equal(after.hashes[table],before.hashes[table],table+' changed');
  for(const table of ['site_templates','site_htmlsnippets','site_tmplvar_templates']) assert.equal(after.hashes['existing_'+table],before.hashes[table],'Existing shared elements changed: '+table);
  assert.equal(hash(fs.readFileSync(root+'/assets/templates/outsourcing.html')),hash(fs.readFileSync(backup+'/outsourcing.html')),'Shared original template changed');
  const current=await request('/autsorsing-pto/');assert.equal(current.status,200);
  assert(!fs.existsSync(root+'/_preview/outsourcing-pto'),'Temporary preview directory still exists');
  assert.equal((await request('/_preview/outsourcing-pto/')).status,404,'Removed test URL must return 404');
  assert(!current.html.includes('_preview/'));
  assert(!current.html.includes('[[!AjaxForm'));
  const expected=read(backup+'/preview-rendered.html');
  const headings=s=>[...s.matchAll(/<h[1-3]\b[^>]*>[\s\S]*?<\/h[1-3]>/gi)].map(m=>m[0]);
  assert.deepEqual(headings(current.html),headings(expected));
  assert.deepEqual(links(current.html),links(expected).map(x=>x==='http://localhost:3000/_preview/outsourcing-pto/'?'http://localhost:3000/autsorsing-pto/':x));
  assert.deepEqual(fields(current.html),fields(expected));
  assert.equal(fields(current.html).length,4);
  for(const name of ['result-clock.png','pto-ai-monitor.png','form-portrait-extended.png','schedule-portrait-hd.png']) {
    const r=await fetch('http://localhost:3000/images/outsourcing-pto/'+name);assert.equal(r.status,200);
    assert.equal(hash(Buffer.from(await r.arrayBuffer())),hash(fs.readFileSync(backup+'/preview/'+name)));
  }
  for(const [path,old] of Object.entries(JSON.parse(read(backup+'/other-pages.json')))) {
    const r=await request(path);assert.equal(r.status,200);
    assert.equal((r.html.match(/<h1\b[^>]*>[\s\S]*?<\/h1>/i)||[])[0],old.h1);
    assert.deepEqual(links(r.html),old.links);
  }
  fs.writeFileSync(backup+'/after.json',JSON.stringify(after,null,2));
  fs.writeFileSync(backup+'/active-after.html',current.html);
  const report={status:'passed',url:'http://localhost:3000/autsorsing-pto/',backup,resourceId:3812,newTemplateId:after.resource.template,forms:4,linksAndHeadingsPreserved:true,otherResourcesUnchanged:true,TVvaluesAndHandlersUnchanged:true,imagesVerified:true,testUrlRemoved:true,realRequestsSent:false};
  fs.writeFileSync(backup+'/verification.json',JSON.stringify(report,null,2));
  console.log(JSON.stringify(report,null,2));
}
