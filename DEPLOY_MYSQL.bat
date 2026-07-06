@echo off
cd /d "%~dp0"
echo === MySQL TAM KURULUM (tek SQL dosyasi) ===
echo.
echo 1) Veritabani olustur:
echo    mysql -u root -p ^< database\mysql_provision.sql
echo.
echo 2) TUM tablolar + veriler (clone backup):
echo    mysql -u root -p alisulasyon ^< database\sql\full_database.sql
echo    veya phpMyAdmin: database\sql\full_database.sql import
echo.
echo 3) .env ayarla + frontend:
echo    php artisan key:generate
echo    php artisan platform:install
echo.
echo === Icerik ===
echo - 47 tablo (sema)
echo - 1132 kullanici (sifre: Test123.)
echo - 5 admin, banner, sponsor, market, turnuva, wheel...
echo.
echo Admin panel: /panel/login
echo   test@gmail.com / testtest
echo   adminadminadminadminadmin@gmail.com / adminadminadminadminadmin
echo.
pause
