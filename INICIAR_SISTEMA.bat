@echo off
TITLE INICIANDO ELIANS STORE (AUTO-ARRANQUE)
COLOR 0A
CLS

ECHO ===================================================
ECHO      VERIFICANDO ESTADO DEL SISTEMA...
ECHO ===================================================

:: 1. Verificamos si Docker ya esta corriendo
docker info >nul 2>&1
IF %ERRORLEVEL% EQU 0 GOTO :START_SYSTEM

:: 2. Si no esta corriendo, lo arrancamos MINIMIZADO
ECHO.
ECHO [!] Docker Desktop esta apagado. Iniciando motor...
ECHO     (La ventana se abrira minimizada, por favor espere)

IF EXIST "C:\Program Files\Docker\Docker\Docker Desktop.exe" (
    start /min "" "C:\Program Files\Docker\Docker\Docker Desktop.exe"
) ELSE (
    ECHO.
    ECHO [ALERTA] No encontre Docker. Por favor abrelo manualmente.
)

:: 3. Bucle de espera hasta que la ballenita este lista
:WAIT_LOOP
timeout /t 5 >nul
docker info >nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
    ECHO ... Calentando motores (esto puede tardar 1 min) ...
    GOTO :WAIT_LOOP
)

:START_SYSTEM
ECHO.
ECHO ===================================================
ECHO      MOTOR LISTO. LEVANTANDO ELIANS STORE...
ECHO ===================================================
ECHO.

:: 4. Levantamos el servidor
docker-compose up -d

ECHO.
ECHO [EXITO] Sistema operativo.
ECHO Abriendo navegador...
timeout /t 3 >nul
start http://localhost:8080

EXIT