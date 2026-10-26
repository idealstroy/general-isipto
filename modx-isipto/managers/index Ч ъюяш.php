<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <meta charset="utf-8">
    <base href="[[++site_url]]">
    <title>Заголовок</title>
    <title>{$_modx->config.site_name} </title>
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="Верстка: Юдин Антон - yudin-1337@yandex.ru.">
    <meta property="og:title" content="">
    <meta property="og:description" content="">
    <meta property="og:type" content="website">
    <meta property="og:url" content="">
    <meta property="og:image" content="../css/img/ogimage.png">
    <meta name="theme-color" content="#FFF">
    <link rel="stylesheet" href="/dist/css/vendor.min.css">
    <link rel="stylesheet" href="/dist/css/main.min.css">
  </head>
  <body>
    <div class="page">
      <div class="container">
        <div class="row">
          <div class="page-left col-lg-4 col-12"><a class="logo" href="/"></a>
            <div class="row">
              <div class="col-6">
                <div class="info-item">
                  <div class="info-item__title">Всего подписанных договоров</div>
                  <div class="info-item__subtitle">18</div>
                </div>
              </div>
              <div class="col-6">
                <div class="info-item">
                  <div class="info-item__title">Общая <br>сумма</div>
                  <div class="info-item__subtitle" style="font-size:27px">10 000 000</div>
                </div>
              </div>
            </div>
          </div>
          <div class="page-right col-lg-8 col-12">
            <div class="page-right__title">Результаты за&nbsp;<span>месяц</span>
              <!--span год-->
            </div>
            <div class="page-right__subtitle">Отдел продаж</div>

            <?php
              require_once 'connection.php';
               
              
              $link = mysqli_connect($host, $user, $password, $database) 
                  or die("Ошибка " . mysqli_error($link));
              $query ="SELECT * FROM managers ORDER BY SumMonth DESC";
              $result = mysqli_query($link, $query) or die("Ошибка " . mysqli_error($link));

              if($result)
                {
                    $rows = mysqli_num_rows($result);
                    for ($i = 0 ; $i < $rows ; ++$i)
                      {
                          $myrow = mysqli_fetch_array($result);
                          echo '<div class="managers-item d-flex">';
                          echo '<div class="managers-item__img d-flex align-items-center"><img src="/dist/img/'.$myrow[Картинка].'"></div>';

                          echo '<div class="managers-item-content">';
                          echo '<div class="managers-item-content__title">'.$myrow[ФИО].'</div>';
                          echo '<div class="managers-item-content__subtitle">Менеджер по продажам</div>';
                          
                          echo '<div class="specifications d-flex justify-content-start">';
                          echo '<div class="specifications-item d-flex align-items-start">';
                          echo '<div class="specifications-item__number">'.$myrow[Количество].'</div>';
                          echo '<div class="specifications-item__text">подписанных<br> договоров</div>';
                          echo '</div>';
                          echo '</div>';
                          echo '<div class="managers-item-content__sum">Сумма:&nbsp;<span>'.$myrow[SumMonth].'</span></div>';
                          echo '</div>';
                          echo '</div>';
                      }                     
                    // очищаем результат
                    mysqli_free_result($result);
                }
              
            // закрываем подключение
            mysqli_close($link);
          ?>
          </div>
        </div>
      </div>
    </div>
    <script src="/dist/js/vendor.min.js"></script>
    <script src="/dist/js/common.min.js"></script>
  </body>
</html>