<?php
require '/var/www/html/config.core.php';require MODX_CORE_PATH.'model/modx/modx.class.php';$m=new modX();$m->initialize('web');$m->resource=$m->getObject('modResource',3075);
$c=$m->getObject('modChunk',array('name'=>'blog-new-3'));$raw=$c?$c->getContent():'';
$parent=$m->getOption('blog');$ids=$m->runSnippet('pdoResources',array('parents'=>$parent,'limit'=>3,'returnIds'=>1));
$html=$m->resource->process();$m->parser->processElementTags('',$html,true,true);$start=strpos($html,'id="solution-blog"');$end=$start===false?false:strpos($html,'</main>',$start);$excerpt=$start===false?'':substr($html,$start,$end===false?3000:$end-$start);
echo json_encode(array('blogParent'=>$parent,'directChildren'=>$m->getCount('modResource',array('parent'=>(int)$parent,'published'=>1,'deleted'=>0)),'returnedArticleIds'=>$ids,'blogHtmlExcerpt'=>$excerpt),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
