<?php
require_once "config.core.php";
require_once MODX_CORE_PATH."model/modx/modx.class.php";
$modx = new modX();
$modx->initialize("mgr");
$res = $modx->getObject("modResource", 5284);
if ($res) {
    $res->set("template", 0);
    $res->save();
    $modx->cacheManager->refresh();
    echo "Fixed template for resource 5284\n";
}
