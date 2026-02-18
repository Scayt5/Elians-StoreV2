@echo off
TITLE INSTALADOR ELIANS STORE (MODO SEGURO)
COLOR 0E
CLS

:: 1. VERIFICACION DE MOTOR
docker info >nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
    COLOR 0C
    ECHO ===================================================
    ECHO [ERROR] DOCKER ESTA APAGADO
    ECHO ===================================================
    ECHO Por favor, abre Docker Desktop primero y espera a que cargue.
    ECHO Luego vuelve a ejecutar este instalador.
    ECHO.
    PAUSE
    EXIT
)

ECHO ===================================================
ECHO    INSTALANDO SISTEMA ELIANS STORE - DESDE CERO
ECHO ===================================================
ECHO.
ECHO  ADVERTENCIA:
ECHO  Esta opcion BORRARA TODA LA BASE DE DATOS actual.
ECHO  Se usa solo para la primera instalacion o para reiniciar todo de fabrica.
ECHO.
ECHO  Si tienes datos importantes, cierra esto y usa GENERAR_RESPALDO.bat
ECHO.
SET /P AREYOUSURE=Estas seguro de continuar? (S/N):
IF /I "%AREYOUSURE%" NEQ "S" GOTO END

CLS
COLOR 0A
ECHO.
ECHO 1. Limpiando sistema anterior...
docker-compose down -v

ECHO.
ECHO 2. Construyendo contenedores (Esto tomara unos minutos)...
docker-compose up -d --build

ECHO.
ECHO ===================================================
ECHO    !INSTALACION COMPLETADA CON EXITO!
ECHO ===================================================
ECHO.
ECHO Esperando 15 segundos para inicializar Base de Datos...
timeout /t 15 >nul

ECHO.
ECHO Abriendo el sistema...
start http://localhost:8080

:END
EXIT