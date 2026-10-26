import fs from 'node:fs';
import {spawn} from 'node:child_process';
const f=process.argv[2];if(!f?.startsWith('deployment/homepage/')||!f.endsWith('.php'))throw Error('Unexpected script');
const c=spawn('docker',['exec','--user','www-data','-i','modx-isipto-web-1','php'],{windowsHide:true});if(process.argv[3]==='routing-preflight')c.stdin.write("<?php putenv('ISIPTO_ROUTING_PREFLIGHT=1'); ?>");fs.createReadStream(f).pipe(c.stdin);c.stdout.pipe(process.stdout);c.stderr.pipe(process.stderr);const code=await new Promise((r,j)=>{c.on('error',j);c.on('close',r)});if(code)process.exitCode=code;
