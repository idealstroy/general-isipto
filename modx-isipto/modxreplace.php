<?php
  //защита от повторной отправки
  session_start();
  if (!isset($_SESSION['sendform'])){
    $_SESSION['sendform'] = md5(rand(1,9999)).'modx';
  }

  //поиск значения переменной по ее названию $string в строке $file
  function getString($string,$file){
    preg_match("/" . preg_quote($string) . "\s*=\s*'(.*?)'/", $file, $result);
    return $result[1];
  }

  //поиск значения константы по ее названию $string в строке $file
  function getConstant($string,$file){
    preg_match("/" . preg_quote($string) . "'\s*,\s*'(.*?)'/", $file, $result);
    return $result[1];
  }

  //путь к главному файлу конфигурации
  $config = 'core/config/config.inc.php';

  $fileConfig = file_get_contents($config);

  //строки, отвечающие за подключение в БД
  $dbaseStrings = ['database_user','database_password','dbase','table_prefix'];

  //массив с текущими данными от БД
  foreach ($dbaseStrings as $val) {
    $dbaseAccess[$val] = getString($val,$fileConfig);
  }

  //путь к админке
  $managerName = basename(getString('modx_manager_path',$fileConfig));

  $files = array(
    'core' => $config,
    'root' => 'config.core.php',
    'connectors' => 'connectors/config.core.php',
    'manager' => $managerName . '/config.core.php'
  );

  //если все 6 обязательных полей заполнены, происходит замена
  if ($_POST['newpath'] &&
      $_POST['domain'] &&
      $_POST['database_user'] &&
      $_POST['dbase'] &&
      $_POST['table_prefix'] &&
      $_POST['manager'] &&
      $_POST['sendform'] == $_SESSION['sendform']) {
      unset($_SESSION['sendform']);

      //замена папки админки
      if($_POST['manager'] != $managerName){
        rename($managerName,$_POST['manager']);

        //обновление .gitignore
        $gitIgnore = file_get_contents('.gitignore');
        $gitIgnore = str_replace('/'.$managerName.'/*','/'.$_POST['manager'].'/*',$gitIgnore);
        file_put_contents('.gitignore', $gitIgnore);

        $managerName = $_POST['manager'];
        $files['manager'] = $managerName . '/config.core.php';
      }
      //замена слэшей в новом пути
      $newPathSlashes = str_replace('\\','/',$_POST['newpath']);

      //уникальные строки
      $uniqueStrings = array(
        'database_dsn',
        'modx_core_path',
        'modx_processors_path',
        'modx_connectors_path',
        'modx_manager_path',
        'modx_manager_url',
        'modx_base_path',
        'http_host',
        'modx_assets_path'
      );

      //разбитие основного конфига на массив
      foreach ($files as $fileName => $filePath){
        $fileArray = file($filePath);
        switch ($fileName){
          case 'core':
            foreach($fileArray as $key=>$val){
              //замена полей
              foreach($dbaseStrings as $word){
                if (stripos($val,$word) !== false){

                  $stringToReplace = getString($word,$val);
                  $fileArray[$key] = str_replace($stringToReplace,$_POST[$word],$val);

                  //если значение поля пустое, оно записывается
                  if ($stringToReplace == ''){
                    if (stripos($val,"''") !== false){
                      $fileArray[$key] = str_replace("''","'".$_POST[$word]."'",$val);
                    }
                    if (stripos($val,'""') !== false){
                      $fileArray[$key] = str_replace('""','"'.$_POST[$word].'"',$val);
                    }
                  }
                }
              }

              //старый домен
              $oldDomain = getString('http_host',$fileConfig);

              //обработка уникальных строк
              foreach($uniqueStrings as $word){
                if (stripos($val,$word) !== false && stripos($val,'define') === false){
                  switch ($word) {
                      case 'database_dsn':
                          $stringToReplace = getString($word,$val);
                          $fileArray[$key] = str_replace($stringToReplace,'mysql:host=localhost;dbname='.$_POST['dbase'].';charset=utf8',$val);
                          break;
                      case 'http_host':
                          if(stripos($val,'$_SERVER') === false && stripos($val,'$url_scheme') === false){
                            $stringToReplace = getString($word,$val);
                            $fileArray[$key] = str_replace($stringToReplace,$_POST['domain'],$val);
                          }
                          if(stripos($val,'array_key_exists') !== false){
                            $fileArray[$key] = str_replace($oldDomain,$_POST['domain'],$val);
                          }
                          break;
                      case 'modx_manager_path':
                          $stringToReplace = getString($word,$val);
                          $finalString = $newPathSlashes.'/'.$managerName.'/';
                          $fileArray[$key] = str_replace($stringToReplace,$finalString,$val);
                          break;
                      case 'modx_manager_url':
                          $stringToReplace = getString($word,$val);
                          $fileArray[$key] = str_replace($stringToReplace,'/'.$managerName.'/',$val);
                          break;
                      default:
                          $stringToReplace = getString($word,$val);
                          $keepString = stristr($stringToReplace,$oldDomain);
                          $finalString = $newPathSlashes.str_replace($oldDomain,'',$keepString);
                          $fileArray[$key] = str_replace($stringToReplace,$finalString,$val);
                  }
                }
              }

              //запись в файл
              file_put_contents($filePath, $fileArray);
            }
            break;
            default:
              //новый путь к ядру
              $pathToCore = $newPathSlashes.'/core/';

              //обход остальных файлов
              foreach($fileArray as $key=>$val){
                  $stringToReplace = getConstant('MODX_CORE_PATH',$val);
                  $fileArray[$key] = str_replace($stringToReplace,$pathToCore,$val);
              }
              //запись в файл
              file_put_contents($filePath, $fileArray);
          }
      }
    //очистка кэша
    function clearCache($path) {
      if (is_file($path)) return unlink($path);
      if (is_dir($path)) {
        foreach(scandir($path) as $val) if (($val!='.') && ($val!='..'))
          clearCache($path.DIRECTORY_SEPARATOR.$val);
        return rmdir($path); 
        }
      return false;
    }
    clearCache('core/cache');

    //обновление .htaccess

    if(file_exists('.htaccess')){
      $htAccess = file_get_contents('.htaccess');
      $htCache = '#htcache
<IfModule mod_expires.c>
  Header append Cache-Control "public"
  FileETag MTime Size
  ExpiresActive On
  ExpiresDefault "access plus 0 minutes"
  ExpiresByType image/ico "access plus 1 years"
  ExpiresByType text/css "access plus 1 years"
  ExpiresByType text/javascript "access plus 1 years"
  ExpiresByType image/gif "access plus 1 years"
  ExpiresByType image/jpg "access plus 1 years"
  ExpiresByType image/jpeg "access plus 1 years"
  ExpiresByType image/bmp "access plus 1 years"
  ExpiresByType image/png "access plus 1 years"
</IfModule>';
      if (!empty($htAccess) && stripos($htAccess,'#htcache') === false){
        file_put_contents('.htaccess', PHP_EOL . $htCache, FILE_APPEND);
      }
    }

    echo '<div class="success">Замена прошла успешно!</div>';
  }
?>
<!DOCTYPE html>
  <html lang="ru">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <meta charset="utf-8">
    <title>Редактирование конфигурации MODX</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto+Condensed|Roboto:400,700&amp;amp;subset=cyrillic" rel="stylesheet">
    <style>
      * {
        font-family: Roboto Condensed, sans-serif;
        margin: 0;
        padding: 0;
        outline: none;
      }
      .container {
        width: 80%;
        margin: 20px auto 0;
      }
      .panel {
        padding: 30px 60px;
      }
      .clear {
        clear: both;
      }
      .btn {
        background-color: #45ad47;
        color: white;
        font-size: 20px;
        padding: 10px 70px;
        border-radius: 5px;
        border: 0;
        float: right;
      }
      .link{
        display:block;
        color: #2b6cb1;
        margin-top: 10px;
      }
      .success{
        text-align: center;
        font-size: 40px;
        margin-top: 30px;
        color: #45ad47;
      }
      .form-group{
        margin: 20px 0;
      }
      label{
        font-size: 12px;
        display: block;
        margin-bottom: 5px;
      }
      input.form-control {
        border-radius: 5px;
        border: 1px solid #cccccc;
        padding: 10px;
        font-size: 20px;
        width: calc(100% - 20px);
      }
      input.form-control:focus {
        border: 1px solid #45ad47;
      }
      @media only screen and (max-width: 480px) {
        .container {
          width: 100%;
          margin: 20px auto 0;
        }
        .panel {
          padding: 10px;
        }
        .btn {
          width: 100%;
          margin-bottom: 50px;
        }
      }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="panel">
        <form action="" method="post">
          <button class="btn" type="submit" value="<?=$_SESSION['sendform']?>" name="sendform">Заменить</button>
          <div class="clear"></div>
          <div class="form-group">
            <label>Путь на хостинге</label>
            <input class="form-control" name="newpath" type="text" placeholder="Путь на хостинге" value="<?=__DIR__;?>">
          </div>
          <div class="form-group">
            <label>Домен</label>
            <input class="form-control" name="domain" type="text" placeholder="Домен" value="<?=$_SERVER['SERVER_NAME'];?>">
          </div>
          <div class="form-group">
              <label>Имя БД</label>
              <input class="form-control" name="dbase" type="text" placeholder="Имя БД" value="<?=$dbaseAccess['dbase'];?>">
          </div>
            <div class="form-group">
                <label>Пользователь БД</label>
                <input class="form-control" name="database_user" type="text" placeholder="Пользователь БД" value="<?=$dbaseAccess['database_user'];?>">
            </div>
          <div class="form-group">
            <label>Пароль БД (необязательно на локальном сервере)</label>
            <input class="form-control" name="database_password" type="text" placeholder="Пароль БД" value="<?=$dbaseAccess['database_password'];?>">
          </div>
          <div class="form-group">
            <label>Префикс таблиц</label>
            <input class="form-control" name="table_prefix" type="text" placeholder="Префикс таблиц" value="<?=$dbaseAccess['table_prefix'];?>">
          </div>
          <div class="form-group">
            <label>Путь к админке</label>
            <input class="form-control" name="manager" type="text" placeholder="Путь к админке" value="<?=$managerName;?>">
          </div>
        </form>
        <p>Этот файл следует удалить сразу же после использования и не хранить в одной директории с боевой версией сайта.</p>
      </div>
      <div class="panel">
        <p>Другие скрипты:<?=file_get_contents('config.core.php')?></p>
        <a class="link" href="zip.php">Запаковать весь сайт в zip-архив</a>
        <a class="link" href="adminer.php">Упрощенный доступ к MySQL</a>
      </div>
    </div>
  </body>
</html>  