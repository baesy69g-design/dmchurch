@echo off
chcp 65001 >nul
setlocal EnableDelayedExpansion

rem VPS에서 즉시 백업 실행 후 최신 파일만 PC로 다운로드
rem ※ scp는 한글 경로를 못 읽어서 ASCII 경로 사용
set "VPS=root@49.247.205.159"
set "REMOTE=/root/backups/church"
set "LOCAL=D:\mypython\vps2_backup"

if not exist "%LOCAL%" mkdir "%LOCAL%"

echo === 1/2 VPS 백업 실행 ===
ssh %VPS% "bash /root/church-web/scripts/church_backup.sh"
if errorlevel 1 (
  echo [오류] VPS 백업 실패
  exit /b 1
)

echo.
echo === 2/2 최신 백업 다운로드 ===
set "LATEST="
for /f "delims=" %%F in ('ssh %VPS% "ls -t %REMOTE%/church_backup_*.tar.gz 2>/dev/null | head -1"') do set "LATEST=%%F"

if "!LATEST!"=="" (
  echo [오류] 백업 파일을 찾을 수 없습니다.
  exit /b 1
)

for %%A in ("!LATEST!") do set "FNAME=%%~nxA"
set "DEST=%LOCAL%\!FNAME!"

echo 원격: !LATEST!
echo 로컬: !DEST!

scp "%VPS%:!LATEST!" "!DEST!"
if errorlevel 1 (
  echo [오류] 다운로드 실패
  exit /b 1
)

echo.
echo backupnow 완료: !DEST!
endlocal
