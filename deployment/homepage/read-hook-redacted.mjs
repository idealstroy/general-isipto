import fs from 'node:fs';
let s=fs.readFileSync('modx-isipto/assets/snippets/roistat_hooks.php','utf8');
s=s.replace(/https?:\/\/[^\s'"<>]+/g,'[URL_REDACTED]').replace(/(['"]key['"]\s*=>\s*['"])[^'"]+/g,'$1[SECRET_REDACTED]');
console.log(s);
