@echo off
chcp 65001 >nul
setlocal EnableDelayedExpansion

rem ============================================================
rem  동명교회 커스텀 소스 → GitHub 업로드
rem  저장소: https://github.com/baesy69g-design/dmchurch
rem  사용: 더블클릭 또는 cmd 에서 syncup.bat [커밋메시지]
rem ============================================================

set "REPO=https://github.com/baesy69g-design/dmchurch.git"
set "BRANCH=main"
cd /d "%~dp0"

where git >nul 2>&1
if errorlevel 1 (
  echo [오류] Git 이 설치되어 있지 않습니다. https://git-scm.com/download/win
  exit /b 1
)

if not exist ".git" (
  echo === Git 저장소 최초 초기화 ===
  git init
  git branch -M %BRANCH%
  git remote add origin %REPO%
  echo.
  echo [안내] 최초 1회 GitHub 로그인이 필요할 수 있습니다.
  echo        Personal Access Token: GitHub Settings - Developer settings - Tokens
  echo.
)

git remote get-url origin >nul 2>&1
if errorlevel 1 (
  git remote add origin %REPO%
) else (
  git remote set-url origin %REPO%
)

set "MSG=%~1"
if "%MSG%"=="" set "MSG=sync %date% %time%"

echo === 변경 사항 확인 ===
git status -sb
echo.

git add -A
git diff --cached --quiet
if not errorlevel 1 (
  echo [안내] 커밋할 변경 사항이 없습니다.
  echo        VPS 반영만 필요하면 pulldeploy.bat 를 실행하세요.
  goto :done
)

echo === 커밋: %MSG% ===
git commit -m "%MSG%"
if errorlevel 1 (
  echo [오류] 커밋 실패
  exit /b 1
)

echo === GitHub push ===
git push -u origin %BRANCH%
if errorlevel 1 (
  echo.
  echo [오류] push 실패. GitHub 인증을 확인하세요.
  echo   git config --global user.name "이름"
  echo   git config --global user.email "이메일"
  echo   push 시 비밀번호 대신 Personal Access Token 사용
  exit /b 1
)

echo.
echo [완료] GitHub 업로드 성공: %REPO%
echo.
echo 다음 단계: pulldeploy.bat 실행 → VPS 반영

:done
endlocal
exit /b 0
