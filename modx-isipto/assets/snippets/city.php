<?php
$city_current = $modx->getPlaceholder('gl.current.city.name_ru');
$city_real = $modx->getPlaceholder('gl.real.city.name_ru');
$country = $modx->getPlaceholder('gl.real.country.name_en');

if (($country != "Russia")) {
    $city = "Москва";
    return $city;
}
else {
    if ($city_current == 'По умолчанию') {
        if (empty($city_real)) {
            echo '<script src="https://api-maps.yandex.ru/2.0-stable/?load=package.standard&lang=ru-RU" type="text/javascript"></script>
                <script type="text/javascript">
                  window.onload =function(){
                      document.getElementById("city-arrow").innerHTML = ymaps.geolocation.city;
                  }
                </script>';
        }
        else {
            $city = $city_real;
            return $city;
        }
    }
    else {
        $city = $city_current;
        return $city;
    }
}