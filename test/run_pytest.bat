@echo off
echo.
echo ========================================
echo BRACULA Python Test Runner
echo ========================================
echo.

REM Change to the test directory
cd /d "%~dp0"

echo Running pytest...
echo.

IF "%~1"=="" (
    echo Running all tests...
    python -m pytest -v
) ELSE (
    echo Running test: %~1
    python -m pytest "%~1" -v
)

echo.
echo ========================================
echo Test execution complete
echo ========================================
echo.

pause 