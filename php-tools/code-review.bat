@echo off
setlocal enabledelayedexpansion

echo PHP CodeSniffer Code Review Tool
echo ==============================

if "%1"=="" (
    echo Usage: code-review.bat [file_or_directory] [options]
    echo.
    echo Options:
    echo   --fix     : Automatically fix issues where possible
    echo   --verbose : Show detailed error messages
    echo   --strict  : Use strict PSR-12 rules
    echo.
    echo Examples:
    echo   code-review.bat lib/security_headers.php
    echo   code-review.bat lib/ --fix
    echo   code-review.bat pages/ --verbose
    exit /b 1
)

set "TARGET=%1"
set "OPTIONS="
set "FIX_MODE="

:parse_args
if "%2"=="" goto :end_parse
if "%2"=="--fix" (
    set "FIX_MODE=1"
    set "OPTIONS=!OPTIONS! --standard=PSR12"
) else if "%2"=="--verbose" (
    set "OPTIONS=!OPTIONS! --report=full"
) else if "%2"=="--strict" (
    set "OPTIONS=!OPTIONS! --standard=PSR12"
) else (
    echo Unknown option: %2
    exit /b 1
)
shift
goto :parse_args

:end_parse
if "%OPTIONS%"=="" (
    set "OPTIONS=--standard=PSR12 --colors --report=summary"
)

echo Running code review on: %TARGET%
echo.

if defined FIX_MODE (
    echo Running PHP Code Beautifier and Fixer...
    php php-tools/phpcbf.phar %OPTIONS% %TARGET%
    if errorlevel 1 (
        echo.
        echo Some issues could not be automatically fixed.
        echo Running PHP CodeSniffer to show remaining issues...
        echo.
    )
)

php php-tools/phpcs.phar %OPTIONS% %TARGET%
set "EXIT_CODE=%ERRORLEVEL%"

if %EXIT_CODE% EQU 0 (
    echo.
    echo No code style issues found!
) else (
    echo.
    echo Code style issues found. To automatically fix some issues, run:
    echo code-review.bat %TARGET% --fix
)

exit /b %EXIT_CODE% 