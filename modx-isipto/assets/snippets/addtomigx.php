<?php
/*Добавление новой записи в простой MIGX.*/
/*Принимает параметры: atmID - ID документа; atmTV - название MIGX TV*/
/*ID документа*/
$docID = $hook->getValue('atmID');
$page = $modx->getObject('modResource', $docID);

/*Название TV с которой нужно работать*/
$value = $page->getTVValue($hook->getValue('atmTV'));
$items = $modx->fromJSON($value);

/*Перебор поля с MIGX*/
$nextID = 1;
if (is_array($items)) {
    foreach ($items as $item) {
        $id = $modx->getOption('MIGX_id', $item, 0) + 1;
        if ($id > $nextID) {
            $nextID = $id;
        }
    }
} else {
    $items = array();
}

/*Изображение*/
$filename = pathinfo($hook->getValue('image'));

/*Формирование массива*/
$item = array(
    'MIGX_id' => $nextID,
    'name' => $hook->getValue('fio'), //строка
    'date' => date("Y-m-d H:i:s"), //дата
    'photo' => $filename['filename'] . '.' . $filename['extension'] //файл
);

/*Запись в TV*/
$items[] = $item;
if (!$page->setTVValue('reviews', $modx->toJson($items))) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Ошибка');
    return false;
}