@echo off
setlocal
cd /d "%~dp0"
powershell -ExecutionPolicy Bypass -File "%~dp0create_pull_reservations_task.ps1"
endlocal
