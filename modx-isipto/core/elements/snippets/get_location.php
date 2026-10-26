<?php
$country = '';
$ip = $_SERVER['REMOTE_ADDR'];
include MODX_CORE_PATH.'/elements/SxGeo/SxGeo.php';
$SxGeo = new SxGeo(MODX_CORE_PATH.'/elements/SxGeo/SxGeo.dat', SXGEO_MEMORY);
$country = $SxGeo->getCountry($ip);
unset($SxGeo);
$modx->setPlaceholder('countrycode', $country);
return;

?>