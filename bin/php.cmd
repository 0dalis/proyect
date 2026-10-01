@echo off
rem PHP 8.3 de Laragon con zip activado solo para este proyecto (no modifica php.ini).
rem PHP_INI_SCAN_DIR lo heredan los procesos hijos (artisan test, artisan serve).
set "PHP_INI_SCAN_DIR=%~dp0php-ini"
"C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" %*
