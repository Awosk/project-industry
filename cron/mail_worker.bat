@echo off
setlocal enabledelayedexpansion

:: =====================================================
:: Project Oil — Mail Worker (Dinamik Versiyon)
:: =====================================================

:: 1. ADIM: XAMPP Yolunu Belirle (Kullanıcı sadece burayı değiştirebilir)
set "XAMPP_PATH=E:\xampp"

:: 2. ADIM: Scriptin bulunduğu klasörü baz alarak yolları otomatik oluştur
:: %~dp0 -> Bu scriptin bulunduğu klasörün tam yolunu verir (Sondaki \ dahil)
set "CRON_FOLDER=%~dp0"
set "PHP_PATH=%XAMPP_PATH%\php\php.exe"

:: Dosya yollarını scriptin konumuna göre eşitle
set "WORKER_PATH=%CRON_FOLDER%mail_worker.php"
set "LOG_PATH=%CRON_FOLDER%mail_worker.log"

:: Kontrol (Opsiyonel: Hata payını azaltmak için PHP var mı diye bakar)
if not exist "%PHP_PATH%" (
    echo HATA: PHP bulunamadi! Lutfen XAMPP_PATH ayarini kontrol edin: %XAMPP_PATH%
    pause
    exit /b
)

echo Mail worker baslatildi... Log kaydi: %LOG_PATH%

:loop
    :: PHP scriptini calistir ve logla
    "%PHP_PATH%" "%WORKER_PATH%" >> "%LOG_PATH%" 2>&1
    
    :: 60 saniye bekle
    timeout /t 60 /nobreak > nul
goto loop