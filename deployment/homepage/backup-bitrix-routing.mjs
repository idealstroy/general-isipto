import fs from 'node:fs';
import path from 'node:path';
const directory='_backups/bitrix-routing-'+new Date().toISOString().replace(/[:.]/g,'-');fs.mkdirSync(directory,{recursive:true});
for(const f of ['modx-isipto/assets/snippets/bitrix24_hooks.php','modx-isipto/core/components/bitrix24/client.php'])fs.copyFileSync(f,path.join(directory,path.basename(f)));
fs.writeFileSync('_backups/active-bitrix-routing.json',JSON.stringify({directory,createdAt:new Date().toISOString()},null,2));console.log(JSON.stringify({backup:directory}));
