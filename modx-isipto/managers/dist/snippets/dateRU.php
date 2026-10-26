<?php
$month_arr = array('01' => 'Янв',
    '02' => 'Фев',
    '03' => 'Мар',
    '04' => 'Апр',
    '05' => 'Мая',
    '06' => 'Июн',
    '07' => 'Июл',
    '08' => 'Авг',
    '09' => 'Сен',
    '10' => 'Окт',
    '11' => 'Ноя',
    '12' => 'Дек'
);

$time = strtotime($input);
$month = strftime('%m', $time);
$day = strftime('%d', $time);
$year = strftime('%Y', $time);

return "$day $month_arr[$month] $year";