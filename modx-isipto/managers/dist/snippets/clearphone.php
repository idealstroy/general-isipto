<?php
/*
    Сниппет очистки телефона для протокола tel
    Обычное TV
    [[clearPhone? &input=`1234` &id=`1` &tv=`phone`]]
    Подойдет для MIGX
    [[+phone:clearPhone]]
*/
if (!function_exists('clearPhone')) {
    function clearPhone($phone)
    {
        $clear = str_replace(array('(', ')', '-', ' ', '+', '<span>', '</span>'), '', $phone);
        if ($clear[0] == 8) {
            $clear[0] = '7';
        }
        return '+' . $clear;
    }
}

$output = '';

if ($input) {
    $output = clearPhone($input);
} else {
    if (!empty($id) && is_numeric($id)) {
        if (!empty($tv)) {
            $resourcePhoneId = $modx->getObject('modResource', $id);
            $resourcePhoneTV = $resourcePhoneId->getTVValue($tv);
            $output = clearPhone($resourcePhoneTV);
        }
    }
}
return $output;