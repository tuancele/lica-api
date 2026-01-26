@echo off
echo ========================================
echo LICA - Prepare Git Commit for Phase 1
echo ========================================
echo.

echo Adding Phase 1 documentation files...
git add PHASE1_*.md

echo Adding test scripts...
git add scripts/test-redis.php
git add scripts/test-queue.bat
git add scripts/start-redis-and-test.bat
git add scripts/complete-phase1-final.bat
git add scripts/verify-cicd.md

echo Adding test queue job...
git add app/Jobs/TestQueueJob.php

echo.
echo ========================================
echo Files Added Successfully
echo ========================================
echo.
echo Review changes with: git status
echo.
echo To commit:
echo   git commit -m "Phase 1: Complete setup - Redis, Queue, CI/CD"
echo.
echo To push:
echo   git push origin main
echo.
pause

