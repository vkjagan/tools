@echo off
echo Starting Global Sync ...
echo.
PowerShell -ExecutionPolicy Bypass -File sftp.ps1
echo.
echo Sync operation complete.
pause
