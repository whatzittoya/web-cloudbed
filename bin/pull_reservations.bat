@echo off
setlocal
cd /d "%~dp0.."
php bin\pull_reservations.php
endlocal
