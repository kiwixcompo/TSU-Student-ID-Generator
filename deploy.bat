@echo off
chcp 65001 >nul
title TSU Student ID Generator - Sync and Deploy
color 0A
echo.
echo  ╔══════════════════════════════════════════════════════════════╗
echo  ║         TSU STUDENT ID GENERATOR                             ║
echo  ║         SYNC AND DEPLOY TOOL                                 ║
echo  ╚══════════════════════════════════════════════════════════════╝
echo.
echo  Repository: https://github.com/kiwixcompo/TSU-Student-ID-Generator
echo  Live Site:  https://sig.tsuniversity.ng
echo  ══════════════════════════════════════════════════════════════
echo.

cd /d "%~dp0"

REM ── Check git ────────────────────────────────────────────────────────────────
git --version >nul 2>&1
if errorlevel 1 (
    echo  [ERROR] Git is not installed or not in PATH.
    pause
    exit /b 1
)

REM ── Check curl ───────────────────────────────────────────────────────────────
curl --version >nul 2>&1
if errorlevel 1 (
    echo  [ERROR] curl not found. Install Git for Windows which includes curl.
    pause
    exit /b 1
)

REM ════════════════════════════════════════════════════════════════════════════
REM  STEP 1: Commit local changes
REM ════════════════════════════════════════════════════════════════════════════
echo  [1/3] Checking for local changes...
git add .
git diff --cached --quiet
if errorlevel 1 (
    for /f "tokens=2 delims==" %%a in ('wmic OS Get localdatetime /value 2^>nul') do set "dt=%%a"
    set "timestamp=%dt:~0,4%-%dt:~4,2%-%dt:~6,2% %dt:~8,2%:%dt:~10,2%"
    echo  Committing changes...
    git commit -m "Auto-update: %timestamp%"
    if errorlevel 1 (
        echo  [ERROR] Commit failed.
        pause
        exit /b 1
    )
    echo  [OK] Changes committed.
) else (
    echo  [OK] No local changes to commit.
)
echo.

REM ════════════════════════════════════════════════════════════════════════════
REM  STEP 2: Push to GitHub
REM ════════════════════════════════════════════════════════════════════════════
echo  [2/3] Pushing to GitHub...
git push origin main
if errorlevel 1 (
    echo  [ERROR] Push to GitHub failed.
    pause
    exit /b 1
)
echo  [OK] Pushed to GitHub.
echo.

REM ════════════════════════════════════════════════════════════════════════════
REM  STEP 3: Trigger live server deploy via git_pull.php
REM  This script on the server pulls the latest code and syncs
REM  files to the web root, preserving the live config.php
REM ════════════════════════════════════════════════════════════════════════════
echo  [3/3] Deploying to sig.tsuniversity.ng...
echo.
curl -s --max-time 60 "https://sig.tsuniversity.ng/git_pull.php?key=DEPLOY_SIG_2026"
echo.
echo.
echo  ══════════════════════════════════════════════════════════════
echo  Done! Live site: https://sig.tsuniversity.ng
echo  ══════════════════════════════════════════════════════════════
echo.
pause
