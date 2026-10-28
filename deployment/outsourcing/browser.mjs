import {spawn} from 'node:child_process';
const args=process.argv.slice(2);
if(args[0]==='fresh')args.splice(0,args.length,'run-code','async (page) => { await page.setExtraHTTPHeaders({}); const cdp=await page.context().newCDPSession(page); await cdp.send("Network.enable"); await cdp.send("Network.setCacheDisabled",{cacheDisabled:true}); try { await page.goto("https://isipto.ru/autsorsing-pto/"); } finally { await cdp.detach(); } }');
if(args[0]==='layout-check')args.splice(0,args.length,'eval','() => ({viewport:innerWidth,documentWidth:document.documentElement.scrollWidth,horizontalOverflow:document.documentElement.scrollWidth>innerWidth+1,visibleForms:[...document.forms].filter(f=>f.getBoundingClientRect().width>0).length})');
if(args[0]==='fill'&&args[2]==='OUTSOURCING_POPUP')args[2]='ТЕСТ АУТСОРСИНГ — попап';
if(args[0]==='fill'&&args[2]==='OUTSOURCING_FORM')args[2]='ТЕСТ АУТСОРСИНГ — форма';
if(args[0]==='fill'&&args[2]==='OUTSOURCING_PHONE')args[2]='+7 (922) 222-22-22';
const child=spawn(process.execPath,['deployment/homepage/playwright-command.mjs',...args],{windowsHide:true,stdio:'inherit'});
process.exitCode=await new Promise((r,j)=>{child.on('error',j);child.on('close',r);});
