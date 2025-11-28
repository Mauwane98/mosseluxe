@echo off
echo ========================================
echo COMPLETE MOBILE ACCESS FIX
echo ========================================
echo.
echo Step 1: Removing old firewall rules...
netsh advfirewall firewall delete rule name="Apache HTTP Port 80" >nul 2>&1
netsh advfirewall firewall delete rule name="Apache HTTP" >nul 2>&1
netsh advfirewall firewall delete rule name="XAMPP Apache" >nul 2>&1
netsh advfirewall firewall delete rule name="XAMPP" >nul 2>&1
echo Done.
echo.
echo Step 2: Adding new firewall rule for port 80...
netsh advfirewall firewall add rule name="XAMPP Apache Port 80" dir=in action=allow protocol=TCP localport=80 profile=any
echo Done.
echo.
echo Step 3: Checking if Apache is running...
netstat -an | findstr ":80 " | findstr "LISTENING" >nul
if %errorlevel%==0 (
    echo Apache is running on port 80 - Good!
) else (
    echo WARNING: Apache is not running on port 80!
    echo Please start Apache in XAMPP Control Panel
)
echo.
echo ========================================
echo SETUP COMPLETE!
echo ========================================
echo.
echo Your site should now be accessible at:
echo.
echo   From this computer:
echo   http://localhost/mosseluxe/
echo   http://192.168.1.110/mosseluxe/
echo.
echo   From mobile (same Wi-Fi):
echo   http://192.168.1.110/mosseluxe/
echo.
echo ========================================
echo TESTING...
echo ========================================
echo.
echo Testing local access...
curl -s -o nul -w "Status: %%{http_code}\n" http://localhost/mosseluxe/ 2>nul
if %errorlevel%==0 (
    echo Local access: OK
) else (
    echo Local access: Check if Apache is running
)
echo.
echo ========================================
echo.
echo Next steps:
echo 1. Make sure your mobile is on the SAME Wi-Fi
echo 2. Open browser on mobile
echo 3. Go to: http://192.168.1.110/mosseluxe/
echo.
pause
