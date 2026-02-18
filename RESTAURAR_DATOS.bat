@echo off
cd /d "%~dp0"
color 4f
echo ==================================================
echo      PELIGRO: RESTAURACION DE SISTEMA
echo ==================================================
echo.
echo ADVERTENCIA:
echo Al continuar, se BORRARAN todos los datos actuales
echo y se reemplazaran con los de su copia de seguridad.
echo.
echo INSTRUCCIONES:
echo 1. Busque su archivo de respaldo (ej: RESPALDO_2026...).
echo 2. Cambiele el nombre a: importar.sql
echo 3. Pongalo en esta misma carpeta.
echo.
echo Presione una tecla SOLO si esta seguro de continuar...
pause >nul

echo.
echo Buscando archivo "importar.sql"...

if exist "importar.sql" (
    echo Archivo encontrado. Restaurando base de datos...
    docker-compose exec -T db mysql -u root -proot elians_store < importar.sql
    echo.
    echo [EXITO] !El sistema ha vuelto al pasado!
    echo Los datos han sido recuperados exitosamente.
) else (
    echo.
    echo [ERROR] No encuentro el archivo "importar.sql".
    echo Por favor, cambie el nombre de su respaldo y vuelva a intentar.
)

echo.
pause