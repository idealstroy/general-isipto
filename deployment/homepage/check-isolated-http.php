<?php
if (PHP_SAPI !== 'cli' || getenv('ISIPTO_ISOLATED_AUDIT') !== '1') exit(2);
function inspectPage($path) {
    $curl=curl_init('http://127.0.0.1:8080'.$path);
    curl_setopt_array($curl,array(CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>array('Host: isipto.ru'),CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130.0.0.0 Safari/537.36',CURLOPT_TIMEOUT=>120,CURLOPT_FOLLOWLOCATION=>false));
    $html=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_HTTP_CODE);$error=curl_error($curl);curl_close($curl);
    if($path==='/')file_put_contents('/tmp/audit-home-render.html',$html);
    $doc=new DOMDocument();libxml_use_internal_errors(true);$doc->loadHTML('<?xml encoding="utf-8" ?>'.$html);libxml_clear_errors();$xpath=new DOMXPath($doc);
    $headings=array();foreach($xpath->query('//h1') as $node)$headings[]=trim($node->textContent);
    $forms=array();foreach($xpath->query('//form[contains(concat(" ", normalize-space(@class), " "), " ajax_form ")]') as $form){$names=array();$actionLength=0;foreach($xpath->query('.//input[@name]',$form) as $input){$name=$input->getAttribute('name');$names[]=$name;if($name==='af_action')$actionLength=strlen($input->getAttribute('value'));}$forms[]=array('inputNames'=>$names,'actionLength'=>$actionLength);}
    // DOMDocument is an HTML4 parser and moves the popup's final hidden field.
    // Compare raw form boundaries as well; production has the same native markup.
    $rawActions=array();preg_match_all('~<form\b[^>]*>(.*?)</form\s*>~is',$html,$rawForms);
    foreach($rawForms[1] as $rawForm){preg_match_all('~<input\b[^>]*>~is',$rawForm,$inputs);$length=0;foreach($inputs[0] as $input)if(preg_match('~name=["\x27]af_action["\x27]~i',$input)&&preg_match('~value=["\x27]([^"\x27]*)~i',$input,$value))$length=strlen($value[1]);$rawActions[]=$length;}
    return array('path'=>$path,'status'=>$status,'curlError'=>$error,'h1'=>$headings,'forms'=>$forms,'rawFormActionLengths'=>$rawActions,'nativePopupCount'=>$xpath->query('//*[@id="form_up"]')->length,'nativePopupButtons'=>$xpath->query('//*[@href="#form_up"]')->length,'newAutomationHeading'=>strpos($html,'Автоматизируем ПТО на собственной платформе с ИИ')!==false,'unresolvedModxTags'=>substr_count($html,'[['),'phpFatalInResponse'=>(bool)preg_match('/Fatal error:|Parse error:|Uncaught exception/',strip_tags($html)),'bytes'=>strlen($html));
}
$pages=array();foreach(array('/','/index.php?q=vedenie-id/','/index.php?q=upravlenie-stroitelstvom/') as $path)$pages[]=inspectPage($path);
$errors=array();foreach($pages as $page){if($page['status']!==200 || $page['phpFatalInResponse'] || $page['unresolvedModxTags'])$errors[]='Page failed: '.$page['path'];}
$home=$pages[0];if(count($home['h1'])!==1||$home['nativePopupCount']!==1||!$home['newAutomationHeading'])$errors[]='Homepage structure failed';
foreach($home['rawFormActionLengths'] as $length)if($length!==32)$errors[]='Missing AjaxForm action in raw form';
if(count($home['forms'])<2)$errors[]='Expected popup and bottom forms';
echo json_encode(array('php'=>PHP_VERSION,'pages'=>$pages,'errors'=>$errors,'submittedLeads'=>0,'externalNetwork'=>'disabled by internal Docker network'),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
exit($errors?1:0);
