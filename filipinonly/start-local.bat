@echo off
cd /d "%~dp0"
where php >nul 2>nul
if errorlevel 1 (
  echo PHP was not found in PATH.
  echo If XAMPP is installed, run this command instead:
  echo C:\xampp\php\php.exe -S localhost:8000
  pause
  exit /b 1
)
php -S localhost:8000
