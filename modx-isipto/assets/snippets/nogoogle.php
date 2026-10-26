<?php
/*Запрет Google Speed Insights загружать данные, пропущенные через сниппет. Можно использовать как phx фильтр.*/
if (stripos($_SERVER['HTTP_USER_AGENT'], 'Speed Insights') === false || !isset($_SERVER['HTTP_USER_AGENT'])) {
    $output = $input;
}
return $output;