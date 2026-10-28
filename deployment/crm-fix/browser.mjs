import {spawn} from 'node:child_process';
let args=process.argv.slice(2);
if(args[0]==='mock-crm-error')args=['route','https://isipto.ru/assets/components/ajaxform/action.php*','--status','200','--content-type','application/json','--body',JSON.stringify({success:false,message:'Заявка сохранена на сайте, но доставка в CRM не подтверждена. Свяжитесь с нами по телефону',data:[]})];
if(args[0]==='unmock')args=['unroute','https://isipto.ru/assets/components/ajaxform/action.php*'];
if(args[0]==='inspect-ui')args=['eval',`(() => { const p=document.querySelector('#form_up input[name="phone"]');return {phoneMarkedInvalid:p?p.classList.contains('error'):null,phoneValuePreserved:p?p.value.replace(/\D/g,'')==='79222222222':null,messages:[...document.querySelectorAll('.jGrowl-message')].map(e=>e.textContent)};})()`];
if(args[0]==='fill'&&args[2]==='UI_NAME')args[2]='ПРОВЕРКА ИНТЕРФЕЙСА — БЕЗ ОТПРАВКИ';
if(args[0]==='fill'&&args[2]==='LOCAL_CRM_TEST')args[2]='ТЕСТ ЛОКАЛЬНОЙ ФОРМЫ — восстановление CRM';
if(args[0]==='fill'&&args[2]==='UI_PHONE')args[2]='+7 (922) 222-22-22';
const c=spawn(process.execPath,['deployment/homepage/playwright-command.mjs',...args],{windowsHide:true,stdio:'inherit'});process.exitCode=await new Promise((r,j)=>{c.on('error',j);c.on('close',r);});
