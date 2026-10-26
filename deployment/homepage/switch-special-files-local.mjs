import fs from 'node:fs';
const map={'roistat_hooks_bim':'bitrix24_hooks_bim','roistat_hooks_obuchenie':'bitrix24_hooks_obuchenie','roistat_hooks_quiz':'bitrix24_hooks_quiz'};let count=0;
for(const dir of ['modx-isipto/assets/templates','modx-isipto/assets/chunks'])for(const e of fs.readdirSync(dir,{withFileTypes:true})){if(!e.isFile()||!e.name.endsWith('.html'))continue;const p=dir+'/'+e.name,s=fs.readFileSync(p,'utf8');let n=s;for(const [a,b]of Object.entries(map))n=n.replaceAll(a,b);if(n!==s){fs.writeFileSync(p,n);count++;}}
console.log(JSON.stringify({filesSwitched:count}));
