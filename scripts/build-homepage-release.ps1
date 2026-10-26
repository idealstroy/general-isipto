param([string]$LocalUrl = 'http://localhost:3000')
$ErrorActionPreference = 'Stop'
$root = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$source = Join-Path $root 'modx-isipto'
$specPath = Join-Path $root 'deployment/homepage/release-spec.json'
$spec = Get-Content -LiteralPath $specPath -Raw | ConvertFrom-Json
if ((Get-Content -LiteralPath (Join-Path $source $spec.template.static_file) -Raw) -match 'bitrix24_hooks' -and 'assets/snippets/bitrix24_hooks.php' -notin $spec.files) {
    throw 'Old homepage-only package is incomplete for direct CRM delivery. Use deployment/homepage/build-direct-release.mjs and DIRECT-RELEASE.md.'
}
$dist = Join-Path $root 'dist'
New-Item -ItemType Directory -Path $dist -Force | Out-Null
$out = Join-Path $dist ($spec.release + '-' + [DateTime]::UtcNow.ToString('yyyyMMddTHHmmssfffZ'))
New-Item -ItemType Directory -Path $out | Out-Null
$manifest = @()
foreach ($relative in $spec.files) {
    if ($relative -match '(^/|\.\.|\\|:)') { throw "Unsafe package path: $relative" }
    $file = Join-Path $source $relative
    if (!(Test-Path -LiteralPath $file -PathType Leaf)) { throw "Missing asset: $relative" }
    if ([IO.Path]::GetExtension($relative) -in @('.html','.css','.js')) {
        $text = Get-Content -LiteralPath $file -Raw
        if ($text -match 'localhost|127\.0\.0\.1|/_preview/|source-home\.php|home_solution_form|replica-fields') { throw "Development residue: $relative" }
    }
    $dest = Join-Path (Join-Path $out 'payload') $relative
    New-Item -ItemType Directory -Path (Split-Path $dest) -Force | Out-Null
    Copy-Item -LiteralPath $file -Destination $dest
    $manifest += [ordered]@{path=$relative;bytes=(Get-Item -LiteralPath $file).Length;sha256=(Get-FileHash -LiteralPath $file -Algorithm SHA256).Hash.ToLowerInvariant()}
}
$homeHtml = (Invoke-WebRequest -Uri ($LocalUrl.TrimEnd('/')+'/')).Content
if ($homeHtml -match '\[\[|home_solution_form|name="scenario"|name="deadline"') { throw 'Unresolved tags or removed custom form on homepage' }
if ([regex]::Matches($homeHtml,'<h1\b').Count -ne 1) { throw 'Expected exactly one H1' }
if ($homeHtml -notmatch 'id="form_up"' -or $homeHtml -notmatch 'form-footer__form ajax_form') { throw 'Native forms missing' }
$assets = @()
$template = Get-Content -LiteralPath (Join-Path $source $spec.template.static_file) -Raw
foreach ($m in [regex]::Matches($template,'(?:src|href)="(/(?:assets|images)/[^"#?]+)|url\(["'']?(/(?:assets|images)/[^)"''?#]+)')) {
    $asset = if ($m.Groups[1].Success) {$m.Groups[1].Value} else {$m.Groups[2].Value}
    if ($asset -notin $assets) {$assets += $asset}
}
foreach ($asset in $assets) { $r=Invoke-WebRequest -Uri ($LocalUrl.TrimEnd('/')+$asset); if ($r.StatusCode -ne 200) {throw "Asset unavailable: $asset"} }
$baselinePath = Join-Path $root '_backups/homepage-before-modx-transfer-20261020/baseline.json'
if (!(Test-Path -LiteralPath $baselinePath)) {throw 'Regression baseline missing'}
$checks=@()
foreach ($b in (Get-Content -LiteralPath $baselinePath -Raw | ConvertFrom-Json)) {
    $r=Invoke-WebRequest -Uri ($LocalUrl.TrimEnd('/')+$b.path) -SkipHttpErrorCheck
    if ([int]$r.StatusCode -ne [int]$b.status) {throw "Changed HTTP status: $($b.path)"}
    if ($b.path -ne '/' -and $r.StatusCode -eq 200) {
        $title=[Net.WebUtility]::HtmlDecode([regex]::Match($r.Content,'(?is)<title[^>]*>(.*?)</title>').Groups[1].Value).Trim()
        if ($title -ne $b.title) {throw "Changed title: $($b.path)"}
    }
    $checks += [ordered]@{path=$b.path;status=[int]$r.StatusCode}
}
Copy-Item -LiteralPath $specPath -Destination (Join-Path $out 'release-spec.json')
Copy-Item -LiteralPath (Join-Path $root 'deployment/homepage/README.md') -Destination (Join-Path $out 'README.md')
Copy-Item -LiteralPath (Join-Path $root 'deployment/homepage/preflight.php') -Destination (Join-Path $out 'preflight.php')
Copy-Item -LiteralPath (Join-Path $root 'docs/form-delivery-audit-20261021.md') -Destination (Join-Path $out 'form-delivery-audit.md')
$manifest | ConvertTo-Json -Depth 8 | Set-Content -LiteralPath (Join-Path $out 'manifest.json') -Encoding utf8NoBOM
[ordered]@{builtAtUtc=[DateTime]::UtcNow.ToString('o');localUrl=$LocalUrl;checkedAssets=$assets;pages=$checks;productionChanged=$false;crmDeliveryVerified=$false;productionBackupVerified=$false;status='PACKAGE_BUILT_NOT_AUTHORIZED_FOR_PRODUCTION_ACTIVATION'} | ConvertTo-Json -Depth 8 | Set-Content -LiteralPath (Join-Path $out 'verification.json') -Encoding utf8NoBOM
$zip=$out+'.zip'
Compress-Archive -LiteralPath (Join-Path $out 'payload'),(Join-Path $out 'release-spec.json'),(Join-Path $out 'manifest.json'),(Join-Path $out 'README.md'),(Join-Path $out 'preflight.php'),(Join-Path $out 'verification.json'),(Join-Path $out 'form-delivery-audit.md') -DestinationPath $zip -CompressionLevel Optimal
$zipHash=(Get-FileHash -LiteralPath $zip -Algorithm SHA256).Hash.ToLowerInvariant()
"$zipHash  $([IO.Path]::GetFileName($zip))" | Set-Content -LiteralPath ($zip+'.sha256') -Encoding ascii
[ordered]@{directory=$out;archive=$zip;sha256=$zipHash;files=$manifest.Count;pages=$checks.Count;assets=$assets.Count;bytes=(Get-Item -LiteralPath $zip).Length} | ConvertTo-Json
