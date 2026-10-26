import {spawn} from 'node:child_process';
const expression = `() => {
 const box = e => { const r=e.getBoundingClientRect(),s=getComputedStyle(e);return {x:r.x,y:r.y,width:r.width,height:r.height,display:s.display,visibility:s.visibility,overflow:s.overflow,color:s.color}; };
 return {
  url:location.href,viewport:innerWidth,scrollWidth:document.documentElement.scrollWidth,
  h1:[...document.querySelectorAll('h1')].map(e=>({text:e.innerText,...box(e)})),
  headings:[...document.querySelectorAll('h2,h3')].map(e=>e.innerText),
  heroButton:[...document.querySelectorAll('.first-screen .btn-modal')].map(e=>({text:e.innerText,html:e.outerHTML,...box(e),parent:box(e.parentElement)})),
  forms:[...document.querySelectorAll('form.ajax_form')].map(e=>({action:e.getAttribute('action'),method:e.method,fields:[...e.querySelectorAll('[name]')].map(x=>({name:x.name,type:x.type})),button:[...e.querySelectorAll('button,input[type=submit]')].map(x=>({text:x.innerText,html:x.outerHTML}))})),
  brokenImages:[...document.images].filter(e=>!e.complete||!e.naturalWidth).map(e=>e.getAttribute('src')),
  overflow:[...document.querySelectorAll('h1,h2,h3,p,a.btn')].filter(e=>{const r=e.getBoundingClientRect();return r.width>0&&(r.right>innerWidth+2||r.left< -2);}).map(e=>({tag:e.tagName,text:e.innerText,...box(e)})).slice(0,20)
 };
}`;
new Function('return (' + expression + ')');
const child = spawn(process.execPath,['deployment/homepage/playwright-command.mjs','eval','(' + expression.replace(/\s+/g,' ') + ')()'],{windowsHide:true,stdio:'inherit'});
process.exitCode=await new Promise((resolve,reject)=>{child.on('error',reject);child.on('close',resolve);});
