@echo off
chcp 65001 >nul
setlocal

rem ============================================================
rem  VPS 에서 GitHub pull → 교회 홈페이지 반영 (한 번에)
rem  사용: pulldeploy.bat
rem ============================================================

set "VPS=root@49.247.205.159"
set "DEPLOY_SCRIPT=/root/church-web/scripts/vps_pull_deploy.sh"

echo === VPS 배포 시작: %VPS% ===
ssh %VPS% "test -x %DEPLOY_SCRIPT% || bash /root/dmchurch-git/scripts/vps_git_setup.sh; bash %DEPLOY_SCRIPT%"
if errorlevel 1 (
  echo.
  echo [오류] VPS 배포 실패
  echo   최초 1회 VPS 설정: ssh %VPS% "bash /root/dmchurch-git/scripts/vps_git_setup.sh"
  exit /b 1
)

echo.
echo [완료] VPS 반영 완료. 브라우저에서 Ctrl+F5 로 확인하세요.
echo   https://dmchurch.kr
endlocal
