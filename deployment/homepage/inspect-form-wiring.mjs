import fs from 'node:fs';
import {spawn} from 'node:child_process';
import {pipeline} from 'node:stream/promises';
const dir='_backups/hosting-readonly-20261022/';
if(process.argv[2]==='fetch'){
 const response=await fetch('https://isipto.ru/',{headers:{'User-Agent':'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130.0.0.0 Safari/537.36'},signal:AbortSignal.timeout(30000)});
 fs.writeFileSync(dir+'production-home-readonly.html',await response.text());
 const metadata={status:response.status,phpHeader:response.headers.get('x-powered-by'),checkedAt:new Date().toISOString(),url:response.url};fs.writeFileSync(dir+'production-http-metadata.json',JSON.stringify(metadata,null,2));console.log(JSON.stringify(metadata));
}else if(process.argv[2]==='copy'){
 const version=process.argv[3]||'74';const child=spawn('docker',['exec',`isipto-audit-php${version}-20261022`,'cat','/tmp/audit-home-render.html'],{windowsHide:true});
 const exited=new Promise((resolve,reject)=>{child.on('error',reject);child.on('close',resolve);});child.stderr.resume();await pipeline(child.stdout,fs.createWriteStream(dir+'isolated-home-readonly.html'));if(await exited!==0)throw Error('Could not copy fixture HTML');
}else if(process.argv[2]==='route'){
 const r=await fetch('https://isipto.ru/vedenie-id/',{headers:{'User-Agent':'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130.0.0.0 Safari/537.36'},signal:AbortSignal.timeout(30000)});await r.arrayBuffer();const report={url:r.url,status:r.status,checkedAt:new Date().toISOString()};fs.writeFileSync(dir+'production-route-status.json',JSON.stringify(report,null,2));console.log(JSON.stringify(report));
}else{
 const reports=[];
 for(const name of ['production-home-readonly.html','isolated-home-readonly.html']){
  const html=fs.readFileSync(dir+name,'utf8'),forms=[];
  for(const m of html.matchAll(/<form\b([^>]*)>([\s\S]*?)<\/form\s*>/gi)){
   const actions=[...m[2].matchAll(/<input\b[^>]*>/gi)].filter(x=>/name=["']af_action["']/i.test(x[0])).map(x=>(x[0].match(/value=["']([^"']*)/i)||[])[1]?.length||0);
   forms.push({attributes:m[1].replace(/value=["'][^"']*["']/gi,'value="REDACTED"'),actionLengths:actions});
  }
  const allActions=[...html.matchAll(/<input\b[^>]*>/gi)].filter(x=>/name=["']af_action["']/i.test(x[0])).length;
  reports.push({name,forms,allActions,inlineAjaxInitializer:(html.match(/AjaxForm\.initialize[\s\S]{0,500}/)||[])[0]?.replace(/[a-f0-9]{32,}/gi,'HASH_REDACTED')});
 }
 const js=fs.readFileSync('modx-isipto/assets/components/ajaxform/js/default.js','utf8');
 console.log(JSON.stringify({reports,actionWiring:[...js.matchAll(/.{0,80}(?:af_action|initialize|ajaxForm\().{0,160}/g)].map(x=>x[0]).slice(0,18)},null,2));
}
