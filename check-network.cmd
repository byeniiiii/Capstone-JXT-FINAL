@echo off
echo ------------- JX Tailoring API Network Checker -------------
echo.
echo Current Network Information:
ipconfig | findstr /i "IPv4"
echo.
echo ------------- Checking XAMPP Status -------------
echo.
sc query Apache | findstr STATE
sc query MySQL | findstr STATE
echo.
echo ------------- Testing API Connection -------------
echo.
curl -s http://localhost/capstone_jxt/jx-tailoring-mobile/test_api.php
echo.
echo.
echo ------------- API Connection Status -------------
echo.
echo If you see JSON output above with "status":"success", your API is working!
echo.
echo Recommended API configuration:
echo - If using Android emulator: http://10.0.2.2/capstone_jxt/jx-tailoring-mobile
echo - If using physical device: http://YOUR-MACHINE-IP/capstone_jxt/jx-tailoring-mobile
echo - If using iOS simulator: http://localhost/capstone_jxt/jx-tailoring-mobile
echo.
echo Make sure to update the IP address in src/api/api.js with your actual IP address
echo if you're testing on a physical mobile device.
echo.
pause
