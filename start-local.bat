@echo off
cd /d "%~dp0"

if not exist "C:\xampp\php\php.exe" (
    echo PHP bawaan XAMPP tidak ditemukan di C:\xampp\php.
    pause
    exit /b 1
)

echo Aplikasi Stock Barang tersedia di http://127.0.0.1:8000
echo Tekan Ctrl+C untuk menghentikan server.
start "" "http://127.0.0.1:8000"
"C:\xampp\php\php.exe" artisan serve --host=127.0.0.1 --port=8000
