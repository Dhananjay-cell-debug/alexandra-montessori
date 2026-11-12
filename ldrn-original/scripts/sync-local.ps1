param([switch]$NoBuild)
$ErrorActionPreference = 'Stop'
$project = Split-Path $PSScriptRoot -Parent
$wpRoot = 'C:\Users\Dhananjay\Local Sites\lrdn\app\public'
if (-not (Test-Path -LiteralPath (Join-Path $wpRoot 'wp-load.php'))) { throw 'LDRN WordPress was not found.' }
if (-not $NoBuild) {
    Push-Location (Join-Path $project 'frontend')
    try { npm run build:theme; if ($LASTEXITCODE -ne 0) { throw 'Build failed.' } } finally { Pop-Location }
}
function Copy-LdrnTree([string]$source,[string]$destination) {
    if (-not $destination.StartsWith($wpRoot, [StringComparison]::OrdinalIgnoreCase)) { throw 'Destination outside the LDRN installation.' }
    New-Item -ItemType Directory -Path $destination -Force | Out-Null
    robocopy $source $destination /E /NFL /NDL /NJH /NJS /NP | Out-Null
    if ($LASTEXITCODE -ge 8) { throw "Copy failed: $source" }
}
Copy-LdrnTree (Join-Path $project 'wordpress\theme\ldrn-theme') (Join-Path $wpRoot 'wp-content\themes\ldrn-theme')
$mu = Join-Path $wpRoot 'wp-content\mu-plugins'
New-Item -ItemType Directory -Path $mu -Force | Out-Null
# The legacy editor is kept in source for migration reference. Only one
# canvas runtime is installed, to avoid competing published overrides.
Get-ChildItem -LiteralPath (Join-Path $project 'wordpress\mu-plugins') | Where-Object { $_.Name -notin @('ldrn-visual-builder','ldrn-visual-builder.php') } | ForEach-Object {
    $destination = Join-Path $mu $_.Name
    if ($_.PSIsContainer) { Copy-LdrnTree $_.FullName $destination } else { Copy-Item -LiteralPath $_.FullName -Destination $destination -Force }
}
Copy-LdrnTree (Join-Path $project 'wordpress\plugins\ldrn-workspace') (Join-Path $wpRoot 'wp-content\plugins\ldrn-workspace')
# A few legacy source references still use root-relative public assets.
foreach ($folder in @('assets','fonts')) {
    Copy-LdrnTree (Join-Path $project "frontend\public\$folder") (Join-Path $wpRoot $folder)
}
& (Join-Path $PSScriptRoot 'wp-local.ps1') theme activate ldrn-theme
& (Join-Path $PSScriptRoot 'wp-local.ps1') plugin activate ldrn-workspace
$global:LASTEXITCODE = 0
Write-Output 'LDRN theme and workspace synced to http://lrdn.local/.'
