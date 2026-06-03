$root = Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
if (-not (Test-Path "$root\partials\head.html")) {
    $root = Split-Path $PSScriptRoot -Parent
}

$head = @"
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="canonical" href="https://isipto.ru/vedenie-id/pto-program/" />
  <title>Программа для исполнительной документации в строительстве — ПТО Онлайн | ИСИПТО</title>
  <meta name="description" content="ПТО Онлайн — программа для подготовки и ведения исполнительной документации в строительстве: от карточки объекта до готового комплекта без ручного дубля в файлах.">
  <meta name="keywords" content="программа для исполнительной документации, программа для ведения исполнительной документации в строительстве, исполнительная документация программа, программа для формирования исполнительной документации, пто онлайн, строительство">
  <meta name="robots" content="index, follow">
  <meta property="og:url" content="https://isipto.ru/vedenie-id/pto-program/" />
  <meta property="og:locale" content="ru_RU" />
  <meta property="og:type" content="website" />
  <meta property="og:site_name" content="ИСИПТО" />
  <meta property="og:title" content="Ведение исполнительной документации в строительстве — ПТО Онлайн">
  <meta property="og:description" content="Подготовка и ведение ИД в одной программе: шаблоны, журналы, акты и контроль комплектности по объекту — без Excel и разрозненных папок.">
  <meta property="og:image" content="https://isipto.ru/assets/css/img/ogimage.png">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Ведение исполнительной документации в строительстве — ПТО Онлайн">
  <meta name="twitter:description" content="Программа для подготовки и ведения ИД: объекты, шаблоны, журналы и готовый комплект в одном окне.">
  <meta name="twitter:image" content="https://isipto.ru/assets/css/img/ogimage.png">
  <meta name="theme-color" content="#000">
  <link rel="icon" href="https://isipto.ru/assets/css/img/favicon.svg" type="image/x-icon">
  <link rel="stylesheet" href="https://isipto.ru/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://isipto.ru/assets/css/style_new.css">
  <link rel="stylesheet" href="../../assets/css/page-shared.css?v=38">
  <script type="text/javascript">(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};m[i].l=1*new Date();for(var j=0;j<document.scripts.length;j++){if(document.scripts[j].src===r){return;}}k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})(window,document,"script","https://mc.yandex.ru/metrika/tag.js","ym");ym(48630242,"init",{clickmap:true,trackLinks:true,accurateTrackBounce:true,webvisor:true});</script>
  <noscript><div><img src="https://mc.yandex.ru/watch/48630242" style="position:absolute;left:-9999px;" alt=""></div></noscript>
</head>
"@

$header = Get-Content "$root\partials\header.html" -Raw -Encoding UTF8
$main = Get-Content "$root\partials\pto-program-main.html" -Raw -Encoding UTF8
$footer = Get-Content "$root\partials\footer.html" -Raw -Encoding UTF8
$modals = Get-Content "$root\partials\modals.html" -Raw -Encoding UTF8
$scripts = Get-Content "$root\partials\scripts.html" -Raw -Encoding UTF8

$json = Get-Content "$root\docs\pto-program-functionality.json" -Raw -Encoding UTF8
$main = $main -replace '/\*INJECT_FUNCTIONALITY_JSON\*/\[\]', $json.Trim()

$modals = $modals -replace 'value="/vedenie-id/avto-pto/"', 'value="/vedenie-id/pto-program/"'

$out = $head + $header + $main + $footer + $modals + $scripts
$out = $out -replace '<body style="background:rgba\(241, 244, 246, 1\);">', '<body class="pto-program-page" style="background:rgba(241, 244, 246, 1);">'
$out = $out -replace '</body>', "<script src=`"../../assets/js/pto-program.js`"></script>`r`n</body>"

$dest = "$root\vedenie-id\pto-program\index.html"
New-Item -ItemType Directory -Path (Split-Path $dest) -Force | Out-Null
[System.IO.File]::WriteAllText($dest, $out, [System.Text.UTF8Encoding]::new($false))
Write-Host "Wrote $dest"
