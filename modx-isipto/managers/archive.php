<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <meta charset="utf-8">
    <base href="https://isipto.ru/managers">
    <title>Результаты отдела продаж на текущий месяц</title>
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="Верстка: Юдин Антон - yudin-1337@yandex.ru.">
    <meta property="og:title" content="">
    <meta property="og:description" content="">
    <meta property="og:type" content="website">
    <meta property="og:url" content="">
    <meta property="og:image" content="../css/img/ogimage.png">
    <meta name="theme-color" content="#FFF">
    <link rel="stylesheet" href="managers/dist/css/vendor.min.css">
    <link rel="stylesheet" href="managers/dist/css/main.min.css?version=1.15">
  </head>
  <body>
    <div class="page">
      <div class="container">
        <div class="row">
          <div class="page-left col-lg-4 col-12"><a class="logo" href="/"></a>
            <div class="row">
              <!--<div class="col-6">
                <div class="info-item">
                  <div class="info-item__title">Всего подписанных договоров</div>
                  <div class="info-item__subtitle">0</div>
                </div>
              </div>
                <div class="col-12">
                    <div class="info-item">
                      <div class="info-item__title">Общая <br>сумма</div>

                        <div class="info-item__subtitle" style="font-size:27px">0</div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="info-item">
                        <div class="info-item__title">Прирост <br>за день</div>

                        <div class="info-item__subtitle all-progress" style="font-size:27px">0</div>
                    </div>
                </div>

                <div class="col-12">
                    <table id="calendar2">
                        <thead>
                        <tr><td>‹<td colspan="5"><td>›
                        <tr><td>Пн<td>Вт<td>Ср<td>Чт<td>Пт<td>Сб<td>Вс
                        <tbody>
                    </table>
                </div>-->

            </div> 
          </div>
        </div>
          <div class="page-right">
              <div class="page-right__title">Результаты за&nbsp;<span>месяц</span></div>
              <a href="http://isipto.ru/managers/" class="page-right__a-title">Результаты по менеджерам</a>
            </div>
            <div class="page-right__subtitle">Отдел продаж</div>


              <table class="table">
                  <thead>
                  <tr>
                      <th scope="col"></th>
                      <th scope="col">Январь</th>
                      <th scope="col">Февраль</th>
                      <th scope="col">Март</th>
                      <th scope="col">Апрель</th>
                      <th scope="col">Май</th>
                      <th scope="col">Июнь</th>
                      <th scope="col">Июль</th>
                      <th scope="col">Август</th>
                      <th scope="col">Сентябрь</th>
                      <th scope="col">Октябрь</th>
                      <th scope="col">Ноябрь</th>
                      <th scope="col">Декабрь</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                      <th scope="row">Москва</th>
                      <td class="active">202744</td>
                      <td class="active">832754</td>
                      <td class="active">500248</td>
                      <td class="active">2022249</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                  </tr>
                  <tr>
                      <th scope="row">Уфа</th>
                      <td>168000</td>
                      <td>421186</td>
                      <td>264000</td>
                      <td>1068220</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                  </tr>
                  <tr>
                      <th scope="row">Краснодар</th>
                      <td>64200</td>
                      <td>40200</td>
                      <td>350596</td>
                      <td>513694</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                  </tr>
                  <tr>
                      <th scope="row">Екатеринбург</th>
                      <td>0</td>
                      <td>18000</td>
                      <td>60000</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                      <td>-</td>
                  </tr>
                  </tbody>
              </table>

            <!--<div class="">
                Подарок за 1 место - поездка в Сочи на Роза Хутор или абонемент в фитнес центр!
            </div>-->


        <!--<table class="table">
          <thead>
              <tr>
                  <th scope="col"></th>
                  <th scope="col">Январь</th>
                  <th scope="col">Февраль</th>
                  <th scope="col">Март</th>
                  <th scope="col">Апрель</th>
                  <th scope="col">Май</th>
                  <th scope="col">Июнь</th>
                  <th scope="col">Июль</th>
                  <th scope="col">Август</th>
                  <th scope="col">Сентябрь</th>
                  <th scope="col">Октябрь</th>
                  <th scope="col">Ноябрь</th>
                  <th scope="col">Декабрь</th>
              </tr>
          </thead>
          <tbody>
                <?php/*
                  require_once 'connection.php';

                  $link = mysqli_connect($host, $user, $password, $database)
                  or die("Ошибка " . mysqli_error($link));

                  $link->set_charset('utf8');

                  $query ="SELECT * FROM years";
                  $result = mysqli_query($link, $query) or die("Ошибка " . mysqli_error($link));

                  if($result)
                    {
                        $rows = mysqli_num_rows($result);
                        for ($i = 0 ; $i < $rows ; ++$i)
                          {
                              $myrow = mysqli_fetch_array($result);
                              echo '<div class="managers-item d-flex">';
                              echo '<div class="managers-item__img d-flex align-items-center"><img src="managers/dist/img/'.$myrow[image].'"></div>';

                              echo '<th scope="row">'.$myrow[fio].'</th>';
                              echo '<td>'.$myrow[January].'</td>';
                              echo '<td>'.$myrow[February].'</td>';
                              echo '<td>'.$myrow[March].'</td>';
                              echo '<td>'.$myrow[April].'</td>';
                              echo '<td>'.$myrow[May].'</td>';
                              echo '<td>'.$myrow[June].'</td>';
                              echo '<td>'.$myrow[July].'</td>';
                              echo '<td>'.$myrow[August].'</td>';
                              echo '<td>'.$myrow[September].'</td>';
                              echo '<td>'.$myrow[October].'</td>';
                              echo '<td>'.$myrow[November].'</td>';
                              echo '<td>'.$myrow[December].'</td>';
                              echo '</tr>';
                          }
                        mysqli_free_result($result);
                    }
                mysqli_close($link);*/
              ?>
            </tbody>
          </table>-->
        </div>
      </div>
    </div>
  <script src="managers/dist/js/vendor.min.js"></script>
  <script src="managers/dist/js/common.min.js"></script>
  </body>
</html>