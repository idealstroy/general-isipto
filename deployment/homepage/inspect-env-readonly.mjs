import fs from 'node:fs';
import path from 'node:path';
const root=process.cwd(),found=[];
function walk(dir,depth=0){
 if(depth>7)return;
 for(const e of fs.readdirSync(dir,{withFileTypes:true})){
  const p=path.join(dir,e.name);
  if(e.isDirectory()){
   if(['.git','.ssh','.agents','node_modules','cache','cache1','vendor','st','__MACOSX'].includes(e.name))continue;
   walk(p,depth+1);
  }else if(e.isFile() && /^\.env(?:\.|$)/i.test(e.name)){
   const data=fs.readFileSync(p,'utf8');const keys=[];
   for(const line of data.split(/\r?\n/)){
    const m=line.match(/^\s*(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/);if(!m)continue;
    keys.push({name:m[1],configured:!!m[2].trim(),crmRelated:/bitrix|crm|webhook/i.test(m[1]),looksLikeBitrixWebhook:/https?:\/\/[^\s]+\/rest\/\d+\//i.test(m[2])});
   }
   found.push({file:path.relative(root,p),keys});
  }
 }
}
walk(root);
for(const p of [path.join(path.dirname(root),'.env'),path.join(root,'.env')]){
 if(fs.existsSync(p)&&!found.some(x=>path.resolve(root,x.file)===p))found.push({file:p,exists:true});
}
console.log(JSON.stringify({root,files:found},null,2));
