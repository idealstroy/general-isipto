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
    <link rel="stylesheet" href="managers/dist/css/main.min.css?version=1.14">
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

            </div> 
          </div>
          <div class="page-right col-lg-8 col-12">
            <div class="page-right__title">Результаты за&nbsp;<span>месяц</span>
              <!--span год-->
            </div>
            <div class="page-right__subtitle">Отдел продаж</div>
            <div class="edit">
              <div class="edit-item d-flex">
                <div class="edit-item__point" style="color: #e10000;">
                  ФИО
                </div>
                <div class="edit-item__point" style="color: #e10000;">
                  Сумма
                </div>
                <div class="edit-item__point"></div>
              </div>
              <?php
                require_once 'connection.php';

                  $sql = 'SELECT * FROM `managers` ORDER BY `SumMonth` DESC';
                  $query = $pdo->query($sql);
                  while($row = $query->fetch(PDO::FETCH_OBJ)) {
                      echo '<div class="edit-item d-flex">';
                      echo '<div class="edit-item__point">'.$row->fio.'</div>';
                      echo '<div class="edit-item__point">'.$row->SumMonthFormat.'</div>';
                      echo '<div class="edit-item__point"><a href="/managers/mjcfynhir5edit.php?id='.$row->id.'"><span>редактировать</span></a></div>';
                      echo "</div>";
                      //echo '<div class="managers-item-content__sum">Сумма:&nbsp;<span>'.$myrow[SumMonth].'</span></div>';
                  }
                  echo '<br><br><br>';
                  $sql = 'SELECT * FROM `projects` ORDER BY `SumMonth` DESC';
                  $query = $pdo->query($sql);
                  while($row = $query->fetch(PDO::FETCH_OBJ)) {
                      echo '<div class="edit-item d-flex">';
                      echo '<div class="edit-item__point">'.$row->fio.'</div>';
                      echo '<div class="edit-item__point">'.$row->SumMonthFormat.'</div>';
                      echo '<div class="edit-item__point"><a href="/managers/mjcfynhir5edit-projects.php?id='.$row->id.'"><span>редактировать</span></a></div>';
                      echo "</div>";
                      //echo '<div class="managers-item-content__sum">Сумма:&nbsp;<span>'.$myrow[SumMonth].'</span></div>';
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
                          echo '<div class="edit-item d-flex">';
                            echo '<div class="edit-item__point">'.$myrow[fio].'</div>';
                            echo '<div class="edit-item__point">'.$myrow[SumMonthFormat].'</div>';
                            echo '<div class="edit-item__point"><a href="/managers/mjcfynhir5edit.php?id='.$myrow[id].'"><span>редактировать</span></a></div>';
                          echo "</div>";
                      }
                    // очищаем результат
                    mysqli_free_result($result);
                }

              // закрываем подключение
              mysqli_close($link);*/
              ?>
          </div>
          <br><br>
          <div class="edit">
            <a href="/managers/"><span>Перейти на главную</span></a>
          </div>
        </div>
      </div>
    </div>
    <script src="managers/dist/js/vendor.min.js"></script>
    <script src="managers/dist/js/common.min.js"></script>
  </body>
</html>