<?php
$base_url = $modx->getPlaceholder('+base_url');

require_once MODX_CORE_PATH . 'ABTest/ABTest.php'; // Путь до ABTest.php

ABTest::instance()->activateTest('main_new1_2screen'); // Название теста из tests.php

$utm = str_replace("&amp;", "&", $_SERVER['QUERY_STRING']);

// Переадресация должна работать в самом верху кода, до загрузки контента страницы
if (ABTest::instance()->getTestValue('main_new1_2screen') === 'new'): // Если вариант new, то запускается переадресация
    $param = !empty($_SERVER['QUERY_STRING']) ? "?" .$utm : ""; // Здесь сохраняются все метки, которые были в ссылке

    setcookie("roistat_referer", isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "");
    ABTest::instance()->submit();

    header("Location: https://isipto.ru/st/{$param}"); // Замените site.ru на адрес сайта, куда будет вести переадресация

    exit();
    endif;
ABTest::instance()->submit();