@echo off
set PATH=C:\Users\denem\php;%PATH%
cd /d "%~dp0"
echo === Alisulasyon Tek Kurulum ===

php scripts\setup_db.php
if errorlevel 1 exit /b 1

php artisan platform:install --fresh --seed --storage --wheel
if errorlevel 1 exit /b 1

echo.
echo === Hazir ===
echo Site:  http://127.0.0.1:8000
echo Panel: http://127.0.0.1:8000/panel/login
echo Admin: test@gmail.com / testtest
echo User:  aslangeliyor / Test123.
echo.
php artisan serve --host=127.0.0.1 --port=8000
