import fs from 'node:fs';
import path from 'node:path';
const dir='_backups/direct-bitrix-'+new Date().toISOString().replace(/[:.]/g,'-');fs.mkdirSync(dir,{recursive:true});
for(const f of ['modx-isipto/docker-compose.yml','modx-isipto/assets/templates/home_solution.html'])fs.copyFileSync(f,path.join(dir,path.basename(f)));
fs.writeFileSync('_backups/active-direct-bitrix.json',JSON.stringify({directory:dir,createdAt:new Date().toISOString()},null,2));console.log(JSON.stringify({backup:dir}));
