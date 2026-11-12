param([Parameter(ValueFromRemainingArguments=$true)][string[]]$WpArgs)
$ErrorActionPreference = 'Stop'
$php = 'C:\OUT_OF_ONEDRIVE\Local\resources\extraResources\lightning-services\php-8.2.29+0\bin\win64\php.exe'
$extensions = 'C:\OUT_OF_ONEDRIVE\Local\resources\extraResources\lightning-services\php-8.2.29+0\bin\win64\ext'
$cli = 'C:\OUT_OF_ONEDRIVE\Local\resources\extraResources\bin\wp-cli\wp-cli.phar'
$root = 'C:\Users\Dhananjay\Local Sites\lrdn\app\public'
$phpArgs = @('-n','-d',"extension_dir=$extensions",'-d','mysqli.default_port=10029','-d','memory_limit=512M')
foreach ($extension in @('mysqli','mbstring','openssl','curl','fileinfo','gd','zip','exif','intl')) {
    $phpArgs += @('-d',"extension=php_$extension.dll")
}
& $php @phpArgs $cli "--path=$root" @WpArgs
if ($LASTEXITCODE -ne 0) { throw "WordPress CLI failed (exit $LASTEXITCODE)." }
