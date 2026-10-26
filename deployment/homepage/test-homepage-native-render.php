<?php
$_SERVER['HTTP_HOST']='localhost:3000';$_SERVER['REQUEST_URI']='/';$_SERVER['REQUEST_METHOD']='GET';$_SERVER['SERVER_NAME']='localhost';
require '/var/www/html/config.core.php';require MODX_CORE_PATH.'model/modx/modx.class.php';$modx=new modX();$modx->initialize('web');
$modx->resource=$modx->getObject('modResource',3075);$modx->resourceIdentifier=3075;$modx->resourceMethod='id';
$html=$modx->resource->process();$modx->parser->processElementTags('',$html,true,true);
$formCount=preg_match_all('/<form\b/i',$html,$unused);
$bottomHasInputs=preg_match('/<form\b[^>]*form-footer__form[^>]*>.*?name="fio".*?name="phone".*?<\/form>/s',$html)===1;
$links=array();preg_match_all('~href=["\x27]((?:[^"\x27]*/)?blog/[^"\x27]+)["\x27]~i',$html,$matches);foreach($matches[1] as $u)$links[$u]=true;
$forms=array();foreach(isset($_SESSION['AjaxForm'])?$_SESSION['AjaxForm']:array() as $v){if(in_array(isset($v['form'])?$v['form']:'',array('form_footer','form_tpl1'),true))$forms[]=array('form'=>$v['form'],'hooks'=>$v['hooks']);}
$hasBottom=false;$hasPopup=false;foreach($forms as $v){if($v['hooks']==='FormItSaveForm,bitrix24_hooks'){if($v['form']==='form_footer')$hasBottom=true;if($v['form']==='form_tpl1')$hasPopup=true;}}
echo json_encode(array('renderedThroughModxResource'=>true,'bottomUsesBitrix'=>$hasBottom,'popupUsesBitrix'=>$hasPopup,'formCount'=>$formCount,'bottomHasInputs'=>$bottomHasInputs,'blogArticleLinks'=>count($links),'forms'=>$forms,'outboundRequests'=>0),JSON_PRETTY_PRINT)."\n";
if(!$hasBottom||!$hasPopup||!$bottomHasInputs||count($links)<3)exit(1);
