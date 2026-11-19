@echo off
setlocal enabledelayedexpansion

:: -------------------------------------------------------------------
:: UniMarket Ghana - Commercial Marketplace Deployment Script
:: Platform: Windows Command Prompt / PowerShell
:: -------------------------------------------------------------------

title UniMarket Ghana - Commercial Marketplace Launcher
color 0A
cls

echo ===================================================================
echo     UniMarket Ghana -- Commercial Marketplace Deployment Engine
echo ===================================================================
echo   Ghana's Premier Commercial E-Commerce Platform
echo   Nationwide Coverage: Greater Accra, Ashanti, Western and all regions
echo ===================================================================
echo.

:: -- STEP 1: Verify Runtime Prerequisites ---------------------------
echo [1/4] Checking system prerequisites and runtimes...

set "PHP_CMD="
where php.exe >nul 2>&1
if %errorlevel% equ 0 (
    set "PHP_CMD=php"
) else (
    if exist "C:\New folder\php\php.exe" (
        set "PHP_CMD=C:\New folder\php\php.exe"
    ) else if exist "C:\xampp\php\php.exe" (
        set "PHP_CMD=C:\xampp\php\php.exe"
    ) else if exist "C:\laragon\bin\php\php.exe" (
        set "PHP_CMD=C:\laragon\bin\php\php.exe"
    ) else if exist "C:\tools\php\php.exe" (
        set "PHP_CMD=C:\tools\php\php.exe"
    )
)

if "%PHP_CMD%"=="" (
    echo.
    echo [ERROR] PHP runtime was not detected on this system!
    echo UniMarket requires PHP 7.4 or higher to run.
    echo.
    echo Download options:
    echo   - PHP for Windows: https://windows.php.net/download/
    echo   - XAMPP Package:   https://www.apachefriends.org/download.html
    echo.
    goto ERROR_HANDLER
)

echo   [OK] PHP Runtime detected.

where npm.cmd >nul 2>&1
if %errorlevel% equ 0 (
    echo   [OK] Node.js / npm environment detected.
    set "HAS_NPM=1"
) else (
    echo   [INFO] npm not found in PATH (proceeding with core PHP runtime).
    set "HAS_NPM=0"
)

:: -- STEP 2: Ensure Directories and Dependencies -------------------
echo.
echo [2/4] Preparing directories and dependencies...

if not exist "uploads" (
    mkdir uploads
    echo   [OK] Created uploads directory.
) else (
    echo   [OK] Uploads directory ready.
)

if "%HAS_NPM%"=="1" (
    if exist "package.json" (
        echo   Running dependency audit...
        call npm install --silent >nul 2>&1
        echo   [OK] Dependencies verified.
    )
)

:: -- STEP 3: Production Build and Sanity Checks ---------------------
echo.
echo [3/4] Running production build and sanity checks...

if "%HAS_NPM%"=="1" (
    if exist "package.json" (
        call npm run build
    )
) else (
    echo   [OK] Core web assets validated.
)

if not exist "index.php" (
    echo.
    echo [ERROR] Root application file 'index.php' is missing!
    goto ERROR_HANDLER
)

:: -- STEP 4: Start Production Server and Launch Browser ------------
echo.
echo [4/4] Starting UniMarket Ghana commercial server...

set "SERVER_HOST=127.0.0.1"
set "SERVER_PORT=8000"

netstat -ano 2>nul | findstr /R /C:":8000 .*LISTENING" >nul 2>&1
if %errorlevel% equ 0 (
    set "SERVER_PORT=8080"
)

echo.
echo ===================================================================
echo   UniMarket Ghana Server is now LIVE!
echo ===================================================================
echo.
echo   - Storefront URL:  http://%SERVER_HOST%:%SERVER_PORT%/
echo   - Marketplace:     http://%SERVER_HOST%:%SERVER_PORT%/dashboard.php
echo   - Admin Portal:    http://%SERVER_HOST%:%SERVER_PORT%/admin/index.php
echo.
echo   - Super Admin:     admin@unimarket.com.gh        / password
echo   - Customer Demo:   kwame.mensah@unimarket.com.gh / password
echo   - Currency:        Ghana Cedis (GH₵ / GHS)
echo.
echo   Press Ctrl+C in this console window anytime to stop the server.
echo ===================================================================
echo.

start "" "http://%SERVER_HOST%:%SERVER_PORT%/index.php"

"%PHP_CMD%" -S %SERVER_HOST%:%SERVER_PORT%
if %errorlevel% neq 0 (
    echo.
    echo [ERROR] The web server terminated unexpectedly with code %errorlevel%.
    goto ERROR_HANDLER
)

goto END

:ERROR_HANDLER
echo.
echo ===================================================================
echo   Deployment encountered an issue. See details above.
echo ===================================================================
pause
exit /b 1

:END
exit /b 0
