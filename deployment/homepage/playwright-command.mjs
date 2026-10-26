import fs from 'node:fs';import {spawn} from 'node:child_process';
const args=process.argv.slice(2);const npm='C:/Program Files/nodejs/node_modules/npm/bin/npx-cli.js';if(!fs.existsSync(npm))throw Error('npx CLI missing');
if(args[0]==='fill'&&args[2]==='TEST_POPUP')args[2]='ТЕСТ ДЕПЛОЯ — попап';
if(args[0]==='fill'&&args[2]==='TEST_BOTTOM')args[2]='ТЕСТ ДЕПЛОЯ — нижняя форма';
const c=spawn('C:/Program Files/nodejs/node.exe',[npm,'--yes','--package','@playwright/cli','playwright-cli','-s=isipto-deploy',...args],{windowsHide:true});c.stdout.pipe(process.stdout);c.stderr.pipe(process.stderr);const code=await new Promise((r,j)=>{c.on('error',j);c.on('close',r)});if(code)process.exitCode=code;
