$ErrorActionPreference = 'Stop'
$projectRoot = $PSScriptRoot
$workspaceRoot = (Resolve-Path (Join-Path $projectRoot '../..')).Path
$dbConfig = Join-Path $workspaceRoot 'work/mysql/my.ini'
$phpPath = 'C:/xampp/php/php.exe'
$mysqlPath = 'C:/xampp/mysql/bin/mysqld.exe'
$storagePath = Join-Path $projectRoot 'app/storage'
$uploadPath = Join-Path $storagePath 'tmp'
New-Item -ItemType Directory -Path $uploadPath -Force | Out-Null
if (-not (Test-Path -LiteralPath $dbConfig)) { throw 'No se encontró la base local. Sigue README.md para instalar la aplicación en tu servidor.' }
if (-not (Get-NetTCPConnection -LocalPort 3308 -State Listen -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath $mysqlPath -ArgumentList "--defaults-file=`"$dbConfig`" --bind-address=127.0.0.1 --console" -WindowStyle Hidden -RedirectStandardOutput (Join-Path $storagePath 'mysql.log') -RedirectStandardError (Join-Path $storagePath 'mysql-errors.log')
}
if (-not (Get-NetTCPConnection -LocalPort 8097 -State Listen -ErrorAction SilentlyContinue)) {
    $publicPath = Join-Path $projectRoot 'public'
    Start-Process -FilePath $phpPath -ArgumentList "-d display_errors=0 -d upload_max_filesize=5M -d post_max_size=6M -d `"upload_tmp_dir=$uploadPath`" -S 127.0.0.1:8097 -t `"$publicPath`"" -WindowStyle Hidden -RedirectStandardOutput (Join-Path $storagePath 'php.log') -RedirectStandardError (Join-Path $storagePath 'php-errors.log')
}
Write-Output 'IBG: http://127.0.0.1:8097/'
Write-Output 'Panel: http://127.0.0.1:8097/admin.php'
Write-Output 'Acceso privado: ACCESO-LOCAL.txt'
