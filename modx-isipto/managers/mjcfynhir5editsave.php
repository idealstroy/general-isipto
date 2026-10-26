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
              <?php
                require_once 'connection.php';

              $sql = "UPDATE `managers` SET SumMonth='".$_GET['SumMonth']."', SumMonthFormat='".$_GET['SumMonthFormat']."', progress='".$_GET['progress']."' WHERE id=".$_GET['id'];

                $query = $pdo->query($sql);
                while($row = $query->fetch(PDO::FETCH_OBJ)) {
                      $id = $_GET['id'];
                      $fio = $row->fio;
                      $SumMonth = $row->SumMonth;
                      $progress = $row->progress;
                      $SumMonthFormat = $row->SumMonthFormat;
                }

              $sql = "UPDATE `projects` SET SumMonth='".$_GET['SumMonth']."', SumMonthFormat='".$_GET['SumMonthFormat']."', progress='".$_GET['progress']."' WHERE id=".$_GET['id'];

              $query = $pdo->query($sql);
              while($row = $query->fetch(PDO::FETCH_OBJ)) {
                  $id = $_GET['id'];
                  $fio = $row->fio;
                  $SumMonth = $row->SumMonth;
                  $progress = $row->progress;
                  $SumMonthFormat = $row->SumMonthFormat;
              }
                  echo "Запись успешно сохранена!<br><br>";
                  echo "<a href=\"/managers/mjcfynhir5.php\"><span>Вернуться к редактированию</span></a><br>";
                  echo "<a href=\"/managers/\"><span>Перейти на главную</span></a>";

                /*$link = mysqli_connect($host, $user, $password, $database)
                or die("Ошибка " . mysqli_error($link));

                $link->set_charset('utf8');

                $query = "UPDATE managers SET SumMonth='".$_GET['SumMonth']."', SumMonthFormat='".$_GET['SumMonthFormat']."', progress='".$_GET['progress']."' WHERE id=".$_GET['id'];
                $result = mysqli_query($link, $query) or die("Ошибка " . mysqli_error($link));

                if($result) 
                {
                  echo "Запись успешно сохранена!<br><br>";
                  echo "<a href=\"/managers/mjcfynhir5.php\"><span>Вернуться к редактированию</span></a><br>";
                  echo " <a href=\"/managers/\"><span>Перейти на главную</span></a>";
                }
                else
                {
                  echo "ошибка";
                }*/
              ?>
            </div>
          </div>
      </div>
    </div>
    <script src="managers/dist/js/vendor.min.js"></script>
    <script src="managers/dist/js/common.min.js"></script>
  </body>
</html>