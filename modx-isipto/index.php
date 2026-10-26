<?php

if (isset($_GET['ctime'])) {
    // Устанавливаем заголовок 404 Not Found

        header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found", true, 404);
        echo "404 Not Found";
        exit();
}


function isSearchBot() {
    $bots = [
        'Googlebot','Googlebot-Image','Googlebot-News','Googlebot-Video','AdsBot-Google','AdsBot-Google-Mobile',
        'Mediapartners-Google','Bingbot','BingPreview','MSNBot','Slurp','Yahoo! Slurp','YandexBot','YandexImages',
        'YandexVideo','YandexMedia','YandexBlogs','YandexFavicons','YandexWebmaster','YandexDirect','YandexNews',
        'YandexPagechecker','YandexMetrika','YandexAccessibilityBot','YandexVertisBot','YandexForDomainBot',
        'YandexMarket','DuckDuckBot','Baiduspider','Baiduspider-image','Baiduspider-video','Baiduspider-news',
        'Baiduspider-favo','Sogou','Sogou Spider','Sogou web spider','Sogou inst spider','Sogou orion spider','Yeti',
        'SeznamBot','Exabot','facebookexternalhit','facebot','Twitterbot','LinkedInBot','Pinterestbot','Applebot',
        'Amazonbot','AhrefsBot','SemrushBot','MJ12bot','DotBot','PetalBot','CCBot','ia_archiver','Qwantify',
        'MojeekBot','Bytespider','spbot','OpenLinkProfiler','archive.org_bot','ZoominfoBot','Cliqzbot'
    ];

    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    foreach ($bots as $bot) {
        if (stripos($userAgent, $bot) !== false) {
            return true;
        }
    }

    return false;
}
/*
// Проверяем, есть ли символ ? в URI
if (strpos($_SERVER['REQUEST_URI'], '?') !== false) {
    if (isSearchBot()) {
        // Отдаем 404 Not Found только ботам
        header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found", true, 404);
        echo "404 Not Found";
        exit();
    }

    // Для обычных пользователей можно оставить обычную логику
    // header("X-Robots-Tag: noindex, nofollow", true);
}
*/

// Здесь может быть основной контент страницы





if  (stristr($_SERVER['HTTP_REFERER'], 'mandarin-browser.com') != FALSE)  {
   echo 'Ваш вход подозрительный';
   exit;
}


$url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];



if  (stristr($url, '/smety/') != FALSE)  {
    header('HTTP/1.0 404 not found');
    echo '404';
    exit();
}


if  (stristr($url, '?y_ref=') != FALSE)  {
    header('HTTP/1.0 404 not found');

}


if ( ($_SERVER['SERVER_NAME']!='isipto.ru') & ($_SERVER['SERVER_NAME']!='press.isipto.ru') & ($_SERVER['SERVER_NAME']!='obuchenie.isipto.ru') & ($_SERVER['SERVER_NAME']!='localhost') )
{
    header('Location: https://isipto.ru/404/');
}

if  ($url=='https://isipto.ru/uslugi/')  {
    header('HTTP/1.0 404 not found');

}


if  (stristr($url, '%5B%5B%2Blink%5D%5D') != FALSE)  {
    header('HTTP/1.0 404 not found');
}


if  (stristr($url, '/uslugi/podgotovka-ispolnitelnoy-dokumentacii/') != FALSE)  {
    header('HTTP/1.0 404 not found');
}


if  (stristr($url, 'sankt-peterburg/null') != FALSE)  {
    header('HTTP/1.0 404 not found');
}


if  (stristr($url, '/press/') != FALSE)  {
    header('HTTP/1.0 404 not found');
}

if  (stristr($url, '/obuchenie/') != FALSE)  {
    header('HTTP/1.0 404 not found');
}


if  (stristr($url, '/%5B%5B+link%5D%5D') != FALSE)  {
    header('HTTP/1.0 404 not found');
}

if  (stristr($url, 'site_url') != FALSE)  {
    header('HTTP/1.0 404 not found');
}


if  (stristr($url, '/uslugi/ispolnitelnaya-dokumentacziya-mostov') != FALSE)  {
    header('HTTP/1.0 404 not found');
}

if  (stristr($url, 'kemerovo/ispolnitelnaya-dokumentatsiya-na-magistralnye-truboprovody') != FALSE)  {
    header('HTTP/1.0 404 not found');
}


if  (stristr($url, 'vyipolnennyie-proektyi') != FALSE)  {
    header('HTTP/1.0 404 not found');
}



if  (stristr($url, '/techkarty1') != FALSE)  {
    header('HTTP/1.0 404 not found');
}

if  (stristr($url, '/1/') != FALSE)  {
    header('HTTP/1.0 404 not found');
}
 
if  (isset($_GET['etext']))  {
    header('HTTP/1.0 404 not found');
} 
 


if  (stristr($url, '/ispolnitelnaya-dokumentacziya-na-dorogi/') != FALSE)  {
    header('HTTP/1.0 404 not found');
}



if  (stristr($url, '%5B%5B++base_url%5D%5D') != FALSE)  {
    header('HTTP/1.0 404 not found');
}

if (stristr($url, '%5B%5B%2B%2Bbase_url%5D%5D') != FALSE) {
    header('HTTP/1.0 404 not found');
} 

if (stristr($url, 'kopiya-') != FALSE) {
    header('HTTP/1.0 404 not found');
} 

if (stristr($url, '[[++base_url]]') != FALSE) {
    header('HTTP/1.0 404 not found');
} 

if (stristr($url, '[[+link]]') != FALSE) {
    header('HTTP/1.0 404 not found');
} 

if (stristr($url, '[[%2B%2Bbase_url]]') != FALSE) {
    header('HTTP/1.0 404 not found');
}

if (stristr($url, 'base_url') != FALSE) {
    header('HTTP/1.0 404 not found');
}

/*
 * This file is part of MODX Revolution.
 *
 * Copyright (c) MODX, LLC. All Rights Reserved.
 *
 * For complete copyright and license information, see the COPYRIGHT and LICENSE
 * files found in the top-level directory of this distribution.
 */

$tstart= microtime(true);

/* define this as true in another entry file, then include this file to simply access the API
 * without executing the MODX request handler */
if (!defined('MODX_API_MODE')) {
    define('MODX_API_MODE', false);
}

/* include custom core config and define core path */
@include(dirname(__FILE__) . '/config.core.php');
if (!defined('MODX_CORE_PATH')) define('MODX_CORE_PATH', dirname(__FILE__) . '/core/');

/* include the modX class */
if (!@include_once (MODX_CORE_PATH . "model/modx/modx.class.php")) {
    $errorMessage = 'Site temporarily unavailable';
    @include(MODX_CORE_PATH . 'error/unavailable.include.php');
    header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable');
    echo "<html><title>Error 503: Site temporarily unavailable</title><body><h1>Error 503</h1><p>{$errorMessage}</p></body></html>";
    exit();
}

/* start output buffering */
ob_start();

/* Create an instance of the modX class */
$modx= new modX();
if (!is_object($modx) || !($modx instanceof modX)) {
    ob_get_level() && @ob_end_flush();
    $errorMessage = '<a href="setup/">MODX not installed. Install now?</a>';
    @include(MODX_CORE_PATH . 'error/unavailable.include.php');
    header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable');
    echo "<html><title>Error 503: Site temporarily unavailable</title><body><h1>Error 503</h1><p>{$errorMessage}</p></body></html>";
    exit();
}

/* Set the actual start time */
$modx->startTime= $tstart;

/* Initialize the default 'web' context */
$modx->initialize('web');

/* execute the request handler */
if (!MODX_API_MODE) {
    $modx->handleRequest();
}
