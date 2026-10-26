<?php
//Монитор производительности
//[[++assets_path]]/snippets/monitor.php
$site = '://' . $_SERVER['SERVER_NAME'];
if (isset($_SERVER['HTTPS'])) {
    $site = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" . $site : "http" . $site;
} else {
    $site = 'http' . $site;
}
function get_content_from($url, $connect_timeout = 10, $timeout = 120)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connect_timeout);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

$content = get_content_from($site);
$pos = strpos($content, '<div id="monitorSpeed" style="display:none;">');
$content = substr($content, $pos);
$pos = strpos($content, '</div>');
$content = substr($content, 0, $pos);
$content = str_replace('<div id="monitorSpeed" style="display:none;">', '', $content);
$content = str_replace('</div>', '', $content);
$values = explode(";", $content);
$seconds = floatval($values[3]);
$finalWidth = round($seconds * 100) * 2;
if ($seconds >= 0 && $seconds < 0.5) {
    $resultText = 'очень быстро';
    $seconds = substr($values[3], 0, -2);
    $seconds = str_replace(',','.', $seconds);
    $finalWidth = round($seconds * 100) * 2;
} elseif ($seconds > 0.5 && $seconds < 1) {
    $resultText = 'быстро';
} elseif ($seconds > 1 && $seconds < 1.5) {
    $resultText = 'средне';
} elseif ($seconds > 1.5 && $seconds < 2) {
    $resultText = 'медленно';
} elseif ($seconds > 2 && $seconds < 2.5) {
    $resultText = 'очень медленно';
} elseif ($seconds > 2 && $seconds < 2.5) {
    $resultText = 'КРИТИЧНО МЕДЛЕННО!!!';
}
$finalResult = $resultText . ', ' . $seconds;
if ($values[4] == 'cache') {
    $cacheResult = 'Используется кэширование. Это значит, что посетители не ждут, пока сайт заново создаст страницы, а получают готовые из кэша. Кэширование дает значительный прирост к скорости сайта.';
} else {
    $cacheResult = 'Кэш не используется, это может замедлить работу сайта. Если кеширование включено, но вы видите это сообщение, просто обновите страницу, чтобы пересоздать кэш.';
}
?>
<style>
    .dashboard-block .body {
        max-height: none;
    }

    .modxMonitor {
        font: normal 13px/1.4 "Helvetica Neue", Helvetica, Arial, Tahoma, sans-serif;
        color: #444;
    }

    .modxMonitor .line {
        background: #4bed0b;
        background: -moz-linear-gradient(left, #4bed0b 0%, #ff1e1e 100%);
        background: -webkit-linear-gradient(left, #4bed0b 0%, #ff1e1e 100%);
        background: linear-gradient(to right, #4bed0b 0%, #ff1e1e 100%);
        filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#4bed0b', endColorstr='#ff1e1e', GradientType=1);
        width: 500px;
        height: 50px;
        border-radius: 3px;
        position: relative;
        overflow: hidden;
    }

    .modxMonitor .cursor {
        position: absolute;
        height: 50px;
        border-right: 2px dashed #2e3d47;
    }

    .modxMonitor .lineTime, .modxMonitor .lineText, .modxMonitor .delimiters {
        display: flex;
        justify-content: space-between;
        width: 500px;
    }

    #lt2 {
        padding-left: 5px;
    }

    #lt3, #lt4, #lt5 {
        padding-left: 10px;
    }

    .modxMonitor .textCorrect {
        padding-left: 20px;
    }

    .modxMonitor .delimiters {
        position: absolute;
    }

    .modxMonitor .delimiters .item {
        width: 1px;
        background: #CCC;
        height: 50px;
    }

    .modxMonitor .link {
        color: #3697cd;
    }

    .modxMonitor .link:hover {
        color: #297aa7;
    }

    .modxMonitor p span {
        font-size: 15px;
    }

    .modxMonitor .big {
        font-size: 17px;
        font-weight: bold;
    }
</style>
<div class="modxMonitor">
    <div class="lineTime">
        <span id="lt1">0 c</span>
        <span id="lt2">0.5 c</span>
        <span id="lt3">1 c</span>
        <span id="lt4">1.5 c</span>
        <span id="lt5">2 c</span>
        <span id="lt6">2.5 c</span>
    </div>
    <div class="line">
        <div class="cursor" style="width:<?= $finalWidth ?>px;"></div>
        <div class="delimiters">
            <div class="item"></div>
            <div class="item"></div>
            <div class="item"></div>
            <div class="item"></div>
            <div class="item"></div>
            <div class="item"></div>
        </div>
    </div>
    <div class="lineText">
        <span>Очень быстро</span>
        <span class="textCorrect">Быстро</span>
        <span class="textCorrect">Средне</span>
        <span class="textCorrect">Медленно</span>
        <span>Очень медленно</span>
    </div>
    <p><span>Скорость сайта:</span><span class="big"> <?= $finalResult ?> секунд.</span></p>
    <p><span>Данные о кэшировании:</span> <?= $cacheResult ?></p>
</div>