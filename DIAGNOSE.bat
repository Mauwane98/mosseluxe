@echo off
color 0A
echo ========================================
echo MOBILE ACCESS DIAGNOSTIC TOOL
echo ========================================
echo.
echo Running diagnostics...
echo.

echo [1/6] Checking Apache Status...
netstat -an | findstr ":80 " | findstr "LISTENING" >nul
if %errorlevel%==0 (
    echo [OK] Apache is running on port 80
) else (
    echo [FAIL] Apache is NOT running on port 80
    echo       Please start Apache in XAMPP Control Panel
    pause
    exit
)
echo.

echo [2/6] Getting your IP address...
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4 Address"') do (
    set IP=%%a
    goto :found
)
:found
set IP=%IP:~1%
echo [OK] Your IP: %IP%
echo.

echo [3/6] Testing local access...
curl -s -o nul -w "%%{http_code}" http://localhost/ >nul 2>&1
if %errorlevel%==0 (
    echo [OK] Apache responds to localhost
) else (
    echo [FAIL] Apache not responding
)
echo.

echo [4/6] Checking firewall rules...
netsh advfirewall firewall show rule name=all | findstr /i "apache" >nul
if %errorlevel%==0 (
    echo [OK] Firewall rules exist
) else (
    echo [WARN] No Apache firewall rules found
    echo       Adding firewall rule now...
    netsh advfirewall firewall add rule name="Apache Port 80" dir=in action=allow protocol=TCP localport=80 >nul 2>&1
    echo [OK] Firewall rule added
)
echo.

echo [5/6] Testing external access...
powershell -Command "try { $r = Invoke-WebRequest -Uri 'http://%IP%/' -UseBasicParsing -TimeoutSec 5; if($r.StatusCode -eq 200) { Write-Host '[OK] Site accessible from IP' } else { Write-Host '[FAIL] Got status:' $r.StatusCode } } catch { Write-Host '[FAIL] Cannot access site:' $_.Exception.Message }"
echo.

echo [6/6] Network Information...
echo Your computer name: %COMPUTERNAME%
echo Your IP address: %IP%
echo.

echo ========================================
echo MOBILE ACCESS URLS
echo ========================================
echo.
echo Use these URLs on your mobile:
echo.
echo   Homepage:  http://%IP%/
echo   Shop:      http://%IP%/shop.php
echo   Admin:     http://%IP%/admin/
echo.
echo ========================================
echo INSTRUCTIONS FOR MOBILE
echo ========================================
echo.
echo 1. Connect mobile to SAME Wi-Fi as this computer
echo 2. Open browser on mobile
echo 3. Type: http://%IP%/
echo 4. Press Go
echo.
echo If it still doesn't work:
echo   - Check both devices show same Wi-Fi name
echo   - Turn off mobile data (use only Wi-Fi)
echo   - Try in incognito/private mode
echo   - Restart your router
echo.
echo ========================================
pause
