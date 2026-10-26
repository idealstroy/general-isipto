<?php
if ($modx->event->name != 'OnWebPagePrerender' || $modx->context->key == 'mgr' || !$modx->getOption('friendly_urls') || $modx->resource->get('parent') != 0) {
    return;
}
$id = $modx->resource->get('id');
$object = $modx->getObject('modResource',$id);
$tvRedirect = $object->getTVValue('id_redirect');
if ($tvRedirect != 'no') {
    $modx->sendRedirect($tvRedirect, array('responseCode' => 'HTTP/1.1 301 Moved Permanently'));
}