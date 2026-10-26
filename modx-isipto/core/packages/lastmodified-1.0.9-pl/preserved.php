<?php return array (
  '55184d94186b99a39d9f40d46ef81e71' => 
  array (
    'criteria' => 
    array (
      'name' => 'LastModified',
    ),
    'object' => 
    array (
      'id' => 35,
      'source' => 1,
      'property_preprocess' => 0,
      'name' => 'LastModified',
      'description' => '',
      'editor_type' => 0,
      'category' => 0,
      'cache_type' => 0,
      'plugincode' => 'if ($modx->event->name == \'OnWebPagePrerender\') {
	$dtm = ($modx->resource->get(\'editedon\'))?strtotime($modx->resource->get(\'editedon\')):strtotime($modx->resource->get(\'createdon\'));
	if(empty($dtm)) { return; }

	//$modx->log(modX::LOG_LEVEL_INFO,$_SERVER[\'REQUEST_URI\'].\' use \'.$dtm.\' date\');
	if(!empty($_SERVER[\'HTTP_IF_MODIFIED_SINCE\'])){
		$ltm = strtotime($_SERVER[\'HTTP_IF_MODIFIED_SINCE\']);
		if($dtm <= $ltm){
			header(\'HTTP/1.0 304 Not Modified\');
			header(\'Last-Modified: \'.gmdate(\'D, d M Y H:i:s\',$dtm).\' GMT\');
			header(\'Cache-control: private, max-age=3600\');
			header(\'Expires: \'.gmdate(\'D, d M Y H:i:s\',time()+3600));
			exit();
		}
	}
	header(\'Last-Modified: \'.gmdate(\'D, d M Y H:i:s\',$dtm).\' GMT\');
	header(\'Cache-control: private, max-age=3600\');
	header(\'Expires: \'.gmdate(\'D, d M Y H:i:s\',time()+3600));
	return;
}',
      'locked' => 0,
      'properties' => 'a:0:{}',
      'disabled' => 1,
      'moduleguid' => '',
      'static' => 0,
      'static_file' => '',
      'content' => 'if ($modx->event->name == \'OnWebPagePrerender\') {
	$dtm = ($modx->resource->get(\'editedon\'))?strtotime($modx->resource->get(\'editedon\')):strtotime($modx->resource->get(\'createdon\'));
	if(empty($dtm)) { return; }

	//$modx->log(modX::LOG_LEVEL_INFO,$_SERVER[\'REQUEST_URI\'].\' use \'.$dtm.\' date\');
	if(!empty($_SERVER[\'HTTP_IF_MODIFIED_SINCE\'])){
		$ltm = strtotime($_SERVER[\'HTTP_IF_MODIFIED_SINCE\']);
		if($dtm <= $ltm){
			header(\'HTTP/1.0 304 Not Modified\');
			header(\'Last-Modified: \'.gmdate(\'D, d M Y H:i:s\',$dtm).\' GMT\');
			header(\'Cache-control: private, max-age=3600\');
			header(\'Expires: \'.gmdate(\'D, d M Y H:i:s\',time()+3600));
			exit();
		}
	}
	header(\'Last-Modified: \'.gmdate(\'D, d M Y H:i:s\',$dtm).\' GMT\');
	header(\'Cache-control: private, max-age=3600\');
	header(\'Expires: \'.gmdate(\'D, d M Y H:i:s\',time()+3600));
	return;
}',
    ),
  ),
  '43de2c733d587b36609cac801cb13bd9' => 
  array (
    'criteria' => 
    array (
      'pluginid' => 35,
      'event' => 'OnWebPagePrerender',
    ),
    'object' => 
    array (
      'pluginid' => 35,
      'event' => 'OnWebPagePrerender',
      'priority' => 0,
      'propertyset' => 0,
    ),
  ),
);