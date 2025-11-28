@echo off
echo ========================================
echo ADDING FIREWALL RULE FOR APACHE
echo ========================================
echo.
echo This will allow mobile devices to access your site
echo.
echo Adding firewall rule...
netsh advfirewall firewall add rule name="Apache HTTP Port 80" dir=in action=allow protocol=TCP localport=80
echo.
echo ========================================
echo FIREWALL RULE ADDED!
echo ========================================
echo.
echo Your site should now be accessible at:
echo   http://192.168.1.110/mosseluxe/
echo.
echo Test from mobile (same Wi-Fi network)
echo.
pause
