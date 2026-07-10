@echo off
chcp 65001 >nul
setlocal

rem 동명교회 VPS 백업 파일 → 로컬 PC 다운로드
rem ※ scp는 한글 경로를 못 읽어서 ASCII 경로 사용
set "VPS=root@49.247.205.159"
set "REMOTE=/root/backups/church"
set "LOCAL=D:\mypython\vps2_backup"

if not exist "%LOCAL%" mkdir "%LOCAL%"

echo === VPS 백업 다운로드 ===
echo 원격: %VPS%:%REMOTE%
echo 로컬: %LOCAL%
echo.

scp "%VPS%:%REMOTE%/church_backup_*.tar.gz" "%LOCAL%"
if errorlevel 1 (
  echo [오류] 다운로드 실패. VPS에 백업이 있는지 확인하세요.
  echo   ssh %VPS% "ls -lh %REMOTE%/"
  exit /b 1
)

echo.
echo 다운로드 완료:
dir /o-d "%LOCAL%\church_backup_*.tar.gz" 2>nul
echo.
echo 저장 위치: %LOCAL%
echo 로컬 보관도 오래되면 직접 정리하세요 ^(VPS는 10일 자동 삭제^).
endlocal
