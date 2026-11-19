@echo off
setlocal enabledelayedexpansion

:: -------------------------------------------------------------------
:: UniMarket Ghana - Build Standalone Deployment Executable (.exe)
:: Compiles UniMarket-Deploy.exe using Windows Built-in C# Compiler
:: -------------------------------------------------------------------

title Build UniMarket Windows Executable
color 0E
cls

echo ===================================================================
echo     UniMarket Ghana -- Windows Executable Builder (.exe)
echo ===================================================================
echo.

set "CSC_EXE="
if exist "%SystemRoot%\Microsoft.NET\Framework64\v4.0.30319\csc.exe" (
    set "CSC_EXE=%SystemRoot%\Microsoft.NET\Framework64\v4.0.30319\csc.exe"
) else if exist "%SystemRoot%\Microsoft.NET\Framework\v4.0.30319\csc.exe" (
    set "CSC_EXE=%SystemRoot%\Microsoft.NET\Framework\v4.0.30319\csc.exe"
)

if "%CSC_EXE%"=="" (
    echo [ERROR] Windows .NET Framework C# compiler was not found.
    echo Please ensure Microsoft .NET Framework 4.0 or higher is installed.
    echo.
    pause
    exit /b 1
)

echo [1/2] Compiling UniMarket-Deploy.exe with Windows C# compiler...
echo Target: UniMarket-Deploy.exe

"%CSC_EXE%" /nologo /target:exe /out:UniMarket-Deploy.exe UniMarketLauncher.cs
if %errorlevel% neq 0 (
    echo.
    echo [ERROR] Compilation failed.
    pause
    exit /b %errorlevel%
)

echo.
echo [2/2] Verification...
if exist "UniMarket-Deploy.exe" (
    echo ===================================================================
    echo   SUCCESS: Standalone executable created successfully!
    echo   File: UniMarket-Deploy.exe
    echo.
    echo   You can now double-click UniMarket-Deploy.exe on any Windows PC
    echo   to launch and deploy UniMarket Ghana in one click.
    echo ===================================================================
) else (
    echo [ERROR] Output file UniMarket-Deploy.exe not found.
    pause
    exit /b 1
)

echo.
exit /b 0
