import fs from 'node:fs';
import path from 'node:path';
import {createHash} from 'node:crypto';
const plan=JSON.parse(fs.readFileSync('_backups/active-homepage-deploy.json','utf8')),mode=process.argv[2],origin='https://isipto.ru';
const headers={'User-Agent':'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130.0.0.0 Safari/537.36','Cache-Control':'no-cache'};
async function page(uri){const response=await fetch(origin+uri,{headers,signal:AbortSignal.timeout(45000)});const html=await response.text();const h1=[...html.matchAll(/<h1\b[^>]*>([\s\S]*?)<\/h1>/gi)].map(x=>x[1].replace(/<[^>]*>/g,'').replace(/\s+/g,' ').trim());const canonicalTag=html.match(/<link\b[^>]*rel=["']canonical["'][^>]*>/i)?.[0];return{path:uri,status:response.status,url:response.url,h1,title:(html.match(/<title[^>]*>([\s\S]*?)<\/title>/i)||[])[1],canonical:canonicalTag?.match(/href=["']([^"']*)/i)?.[1],html};}
if(mode==='assets'){
 const manifest=JSON.parse(fs.readFileSync(plan.sourceRelease+'/manifest.json','utf8')),result=[];
 for(const e of manifest.filter(x=>/\.(css|js|png)$/.test(x.path))){const r=await fetch(origin+'/'+e.path,{headers,signal:AbortSignal.timeout(45000)}),data=Buffer.from(await r.arrayBuffer()),sha256=createHash('sha256').update(data).digest('hex');result.push({path:e.path,status:r.status,sha256Matches:sha256===e.sha256});}
 fs.writeFileSync(path.join(plan.localBackup,'public-assets.json'),JSON.stringify(result,null,2));console.log(JSON.stringify(result));if(result.some(x=>x.status!==200||!x.sha256Matches))throw Error('Public asset verification failed');
}else if(mode==='baseline'){
 const root=await page('/');const routes=['/autsorsing-pto/','/upravlenie-stroitelstvom/','/vedenie-id/','/index.php?q=vedenie-id/','/vedenie-id/podgotovka-ispolnitelnoy-dokumentacii/','/uslugi/stroitelnyij-audit/','/o-kompanii/otzyivyi/','/ekaterinburg/'];
 for(const a of root.html.matchAll(/<a\b([^>]*)>([\s\S]*?)<\/a>/gi)){if(a[2].replace(/<[^>]*>/g,'').trim()!=='Блог')continue;const href=a[1].match(/href=["']([^"']*)/i)?.[1];if(href){const u=new URL(href,origin);if(u.origin===origin&&!routes.includes(u.pathname))routes.push(u.pathname);}}
 const result=[root];for(const route of routes)result.push(await page(route));
 fs.writeFileSync(path.join(plan.localBackup,'before-home.html'),root.html);for(const r of result)delete r.html;fs.writeFileSync(path.join(plan.localBackup,'http-before.json'),JSON.stringify(result,null,2));console.log(JSON.stringify(result));
}else if(mode==='smoke'){
 const before=JSON.parse(fs.readFileSync(path.join(plan.localBackup,'http-before.json'),'utf8')),root=await page('/'),errors=[];
 if(root.status!==200)errors.push('Homepage is not 200');if(root.h1.length!==1)errors.push('Expected one H1');if(!root.html.includes('Автоматизируем ПТО на собственной платформе с ИИ'))errors.push('New homepage marker missing');if(root.html.includes('[['))errors.push('Unresolved MODX tags');if(/https?:\/\/(localhost|127\.0\.0\.1)(?:[:/])/i.test(root.html))errors.push('Local URL leaked');if(/Fatal error:|Parse error:|Uncaught exception/.test(root.html.replace(/<[^>]*>/g,'')))errors.push('PHP fatal response');
 for(const key of ['title','canonical'])if(root[key]!==before[0][key])errors.push('SEO metadata changed: '+key);
 const forms=[];for(const m of root.html.matchAll(/<form\b([^>]*)>([\s\S]*?)<\/form>/gi)){const actions=[...m[2].matchAll(/<input\b[^>]*>/gi)].filter(x=>/name=["']af_action["']/i.test(x[0])).map(x=>x[0].match(/value=["']([^"']*)/i)?.[1]?.length||0);forms.push({nativePopup:/form_up/.test(m[1]),bottom:/form-footer__form/.test(m[1]),actionLengths:actions});}
 if(!forms.some(x=>x.nativePopup&&x.actionLengths.includes(32))||!forms.some(x=>x.bottom&&x.actionLengths.includes(32)))errors.push('Native popup/bottom AjaxForm missing');
 const controls=[];for(const b of before.slice(1)){const r=await page(b.path);delete r.html;const unchanged=r.status===b.status&&JSON.stringify(r.h1)===JSON.stringify(b.h1)&&r.title===b.title;controls.push({...r,unchanged});if(!unchanged)errors.push('Existing page changed: '+b.path);}
 fs.writeFileSync(path.join(plan.localBackup,'published-home.html'),root.html);delete root.html;
 const result={checkedAt:new Date().toISOString(),root,forms,controls,errors,submittedLeads:0};fs.writeFileSync(path.join(plan.localBackup,'smoke-result.json'),JSON.stringify(result,null,2));console.log(JSON.stringify(result));if(errors.length)throw Error('Post-deploy smoke failed; rollback required');
}else throw Error('Expected assets, baseline or smoke');
