@echo off
TITLE APAGANDO ELIANS STORE...
COLOR 0C
CLS

ECHO ===================================================
ECHO      DETENIENDO SISTEMA ELIANS STORE
ECHO ===================================================
ECHO.
ECHO Guardando datos y deteniendo servicios...
ECHO Por favor espere...

docker-compose stop

ECHO.
ECHO [OK] El sistema se ha detenido correctamente.
ECHO Ya puede apagar la computadora.
ECHO.
timeout /t 5