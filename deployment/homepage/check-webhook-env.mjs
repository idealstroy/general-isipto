import fs from 'node:fs';
const s=fs.readFileSync('.env','utf8');
const entries=[];
for(const [i,line] of s.split(/\r?\n/).entries()){
 const m=line.match(/^\s*(?:export\s+)?BITRIX24_WEBHOOK_URL\s*=\s*(.*?)\s*$/);if(!m)continue;
 const v=m[1].replace(/^(['"])(.*)\1$/,'$2');let valid=false;
 try{const u=new URL(v);valid=u.protocol==='https:'&&u.hostname==='isipto.bitrix24.ru'&&/^\/rest\/\d+\/[^/]+\/(?:profile\.json)?$/.test(u.pathname)&&!u.search&&!u.hash;}catch{}
 entries.push({line:i+1,empty:!v,valid});
}
console.log(JSON.stringify({entries,nonEmptyLines:s.split(/\r?\n/).filter(x=>x.trim()&&!x.trim().startsWith('#')).length},null,2));
