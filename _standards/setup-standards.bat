@echo off
REM Development Standards Setup Script (Windows)
REM Usage: setup-standards.bat [target_path]

set TARGET_PATH=%~1
if "%TARGET_PATH%"=="" set TARGET_PATH=%CD%

echo Setting up development standards...
echo Target: %TARGET_PATH%
echo.

REM Copy .cursorrules
if not exist "%TARGET_PATH%\.cursorrules" (
    copy ".cursorrules-template" "%TARGET_PATH%\.cursorrules" >nul
    echo Copied: .cursorrules
) else (
    echo Skipped: .cursorrules (already exists)
)

REM Create _standards folder
if not exist "%TARGET_PATH%\_standards" mkdir "%TARGET_PATH%\_standards"

REM Copy standards files
for %%f in (NAMING_STANDARDS.md COMPONENT_CREATION_PROCEDURE.md) do (
    if not exist "%TARGET_PATH%\_standards\%%f" (
        copy "%%f" "%TARGET_PATH%\_standards\" >nul
        echo Copied: _standards\%%f
    )
)

REM Copy .cursorrules-template to _standards
if not exist "%TARGET_PATH%\_standards\.cursorrules-template" (
    copy ".cursorrules-template" "%TARGET_PATH%\_standards\" >nul
    echo Copied: _standards\.cursorrules-template
)

REM Copy to admin/components if it exists
if exist "%TARGET_PATH%\admin\components" (
    for %%f in (NAMING_STANDARDS.md COMPONENT_CREATION_PROCEDURE.md) do (
        if not exist "%TARGET_PATH%\admin\components\%%f" (
            copy "%%f" "%TARGET_PATH%\admin\components\" >nul
            echo Copied: admin\components\%%f
        )
    )
)

echo.
echo Setup complete!
pause

