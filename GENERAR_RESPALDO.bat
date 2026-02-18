@echo off
cd /d "%~dp0"
echo ==================================================
echo      SISTEMA ELIANS STORE - COPIA DE SEGURIDAD
echo ==================================================
echo.
echo Generando respaldo de la base de datos...
echo Por favor espere...

:: COMANDO DE RESPALDO (Corregido: elians_db)
docker-compose exec -T db mysqldump -u root -proot elians_db > "RESPALDO_%date:~-4,4%-%date:~-7,2%-%date:~-10,2%.sql"

echo.
if exist "RESPALDO_%date:~-4,4%-%date:~-7,2%-%date:~-10,2%.sql" (
    echo [EXITO] El archivo se creo correctamente.
    echo Busque el archivo .sql en esta misma carpeta.
) else (
    echo [ERROR] No se pudo crear el respaldo.
    echo Verifique que el sistema este ENCENDIDO.
)
echo.
pause