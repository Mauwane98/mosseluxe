@echo off
echo ========================================
echo RESTARTING APACHE FOR MOBILE ACCESS
echo ========================================
echo.
echo Stopping Apache...
C:\xamppp\apache\bin\httpd.exe -k stop
timeout /t 3 /nobreak >nul
echo.
echo Starting Apache...
C:\xamppp\apache\bin\httpd.exe -k start
timeout /t 2 /nobreak >nul
echo.
echo ========================================
echo APACHE RESTARTED!
echo ========================================
echo.
echo Your site is now accessible at:
echo.
echo   http://192.168.1.110/mosseluxe/
echo.
echo From mobile (same Wi-Fi):
echo   http://192.168.1.110/mosseluxe/
echo.
echo ========================================
echo.
pause
