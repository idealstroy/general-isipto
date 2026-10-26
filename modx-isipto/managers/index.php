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
              </div>-->
                <div class="col-12">
                    <div class="info-item">
                      <div class="info-item__title">Общая сумма</div>

                        <div class="info-item__subtitle" style="font-size:27px">0</div>
                    </div>
                </div>

                <!--<div class="col-12">
                    <div class="info-item">
                        <div class="info-item__title">Прирост за день</div>

                        <div class="info-item__subtitle all-progress" style="font-size:27px">0</div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="info-item" style="height:auto">
                        <div class="info-item__title">Победитель прошлого месяца</div>
                        <div class="managers-win">
                            <div class="managers-win__img">
                                <img src="managers/dist/img/2.jpg">
                            </div>
                            <div class="managers-win__title">
                                Яна Черная
                            </div>
                        </div>
                    </div>
                </div>-->

                <div class="col-12">
                    <table id="calendar2">
                        <thead>
                        <tr><td>‹<td colspan="5"><td>›
                        <tr><td>Пн<td>Вт<td>Ср<td>Чт<td>Пт<td>Сб<td>Вс
                        <tbody>
                    </table>
                </div>

            </div> 
          </div>
          <div class="page-right col-lg-8 col-12">
            <div class="page-right__title">Результаты за&nbsp;<span>месяц</span></div>
            <a href="http://isipto.ru/managers/archive.php" class="page-right__a-title">Результаты за по филиалам</a>
            <div class="page-right__subtitle">Отдел продаж</div>

            <!--<div class="">
                Подарок за 1 место - поездка в Сочи на Роза Хутор или абонемент в фитнес центр!
            </div>-->
            <div class="title-projects">Менеджеры по продажам</div>
            <div class="row">
                <?php
                  require_once 'connection.php';

                    $sql = 'SELECT * FROM `managers` ORDER BY `SumMonth` DESC';
                    $query = $pdo->query($sql);
                    while($row = $query->fetch(PDO::FETCH_OBJ)) {

                        echo '<div class="col-md-4 col-12">';
                        echo '<div class="managers-item">';
                        echo '<div class="managers-item__img d-flex align-items-center"><img src="managers/dist/img/'.$row->image.'"></div>';

                        echo '<div class="managers-item-content">';
                        echo '<div class="managers-item-content__title">'.$row->fio.'</div>';
                        echo '<div class="managers-item-content__subtitle">Менеджер по продажам</div>';

                        echo '<div class="specifications d-flex justify-content-start">';
                        echo '<div class="specifications-item">';
                        echo '<div class="specifications-item__number">'.$row->SumMonthFormat.'</div>';
                        echo '<div class="specifications-item__number-none d-none">'.$row->SumMonth.'</div>';
                        echo '<div class="specifications-item__text">Сумма</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        //echo '<div class="managers-item-content__sum">Сумма:&nbsp;<span>'.$myrow[SumMonth].'</span></div>';
                        echo '</div>';
                        if ($row->progress) {
                            echo '<div class="specifications-item__progress"><div class="all-progress-item">'.$row->progress.'</div><span>Молодец!</span></div>';
                        }
                        echo '</div>';

                    }

                    ?>
                    </div>
                    <div class="title-projects">Менеджеры по проектам</div>
                    <div class="row">
                    <?php
                        require_once 'connection.php';
                        $sql = 'SELECT * FROM `projects` ORDER BY `SumMonth` DESC';
                        $query = $pdo->query($sql);
                        while($row = $query->fetch(PDO::FETCH_OBJ)) {

                            echo '<div class="col-md-4 col-12">';
                            echo '<div class="managers-item">';
                            echo '<div class="managers-item__img d-flex align-items-center"><img src="managers/dist/img/'.$row->image.'"></div>';

                            echo '<div class="managers-item-content">';
                            echo '<div class="managers-item-content__title">'.$row->fio.'</div>';
                            echo '<div class="managers-item-content__subtitle">Менеджер по проектам</div>';

                            echo '<div class="specifications d-flex justify-content-start">';
                            echo '<div class="specifications-item">';
                            echo '<div class="specifications-item__number">'.$row->SumMonthFormat.'</div>';
                            echo '<div class="specifications-item__number-none d-none">'.$row->SumMonth.'</div>';
                            echo '<div class="specifications-item__text">Сумма</div>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                            //echo '<div class="managers-item-content__sum">Сумма:&nbsp;<span>'.$myrow[SumMonth].'</span></div>';
                            echo '</div>';
                            if ($row->progress) {
                                echo '<div class="specifications-item__progress"><div class="all-progress-item">'.$row->progress.'</div><span>Молодец!</span></div>';
                            }
                            echo '</div>';

                        }


                      /*$link = mysqli_connect($host, $user, $password, $database)
                      or die("Ошибка " . mysqli_error($link));

                      $link->set_charset('utf8');

                      $query ="SELECT * FROM managers ORDER BY SumMonth DESC";
                      $result = mysqli_query($link, $query) or die("Ошибка " . mysqli_error($link));

                      if($result)
                        {
                            $rows = mysqli_num_rows($result);
                            for ($i = 0 ; $i < $rows ; ++$i)
                              {
                                  $myrow = mysqli_fetch_array($result);
                                  echo '<div class="managers-item d-flex">';
                                  echo '<div class="managers-item__img d-flex align-items-center"><img src="managers/dist/img/'.$myrow[image].'"></div>';

                                  echo '<div class="managers-item-content">';
                                  echo '<div class="managers-item-content__title">'.$myrow[fio].'</div>';
                                  echo '<div class="managers-item-content__subtitle">Менеджер по продажам</div>';

                                  echo '<div class="specifications d-flex justify-content-start">';
                                  echo '<div class="specifications-item d-flex align-items-start">';
                                  echo '<div class="specifications-item__number">'.$myrow[SumMonthFormat].'</div>';
                                  echo '<div class="specifications-item__number-none d-none">'.$myrow[SumMonth].'</div>';
                                  echo '<div class="specifications-item__text">Сумма</div>';
                                  echo '</div>';
                                  echo '</div>';
                                  //echo '<div class="managers-item-content__sum">Сумма:&nbsp;<span>'.$myrow[SumMonth].'</span></div>';
                                  echo '</div>';
                                  if ($myrow[progress]) {
                                      echo '<div class="specifications-item__progress"><div class="all-progress-item">'.$myrow[progress].'</div><span>Молодец!</span></div>';
                                  }
                                  echo '</div>';
                              }
                            mysqli_free_result($result);
                        }

                    mysqli_close($link);*/
                 ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script src="managers/dist/js/vendor.min.js"></script>
    <script src="managers/dist/js/common.min.js"></script>
  </body>
</html>