@echo off
setlocal enabledelayedexpansion

echo PHP CodeSniffer Auto-Fix Tool
echo ============================
echo.

echo Fixing code style issues in lib/ directory...
php php-tools/phpcbf.phar --standard=PSR12 lib/
if errorlevel 1 (
    echo Some issues in lib/ could not be automatically fixed.
    echo.
)

echo Fixing code style issues in api/ directory...
php php-tools/phpcbf.phar --standard=PSR12 api/
if errorlevel 1 (
    echo Some issues in api/ could not be automatically fixed.
    echo.
)

echo Fixing code style issues in pages/ directory...
php php-tools/phpcbf.phar --standard=PSR12 pages/
if errorlevel 1 (
    echo Some issues in pages/ could not be automatically fixed.
    echo.
)

echo.
echo Running final code review to check remaining issues...
echo.

php php-tools/phpcs.phar --standard=PSR12 --colors --report=summary lib/ api/ pages/

echo.
echo If there are still issues remaining, they will need to be fixed manually.
echo You can use 'php-tools/code-review.bat [file] --verbose' to see detailed information.
echo.

exit /b 0 