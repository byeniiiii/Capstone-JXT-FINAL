@echo off
echo Stopping any running Expo processes...
taskkill /f /im node.exe >nul 2>&1

echo Clearing Metro bundler cache...
cd jx-tailoring-mobile
npx expo start --clear

echo JX Tailoring Mobile app restarted successfully!
