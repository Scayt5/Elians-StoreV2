<?php
// 1. CALIBRACIÓN DE ZONA HORARIA Y LOCALE (IMPORTANTE PARA PERÚ)
date_default_timezone_set('America/Lima');
setlocale(LC_TIME, 'es_PE.UTF-8', 'es_ES.UTF-8', 'es_PE', 'es_ES');

// 2. DATOS DE CONEXIÓN
// Se intenta leer de variables de entorno, si no existen se usan los valores por defecto (Docker)
$host = getenv('DB_HOST') ?: 'db';
$db   = getenv('DB_NAME') ?: 'elians_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'root';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $conn = new PDO($dsn, $user, $pass, $options);

    // 3. CALIBRACIÓN DE MYSQL (EL TRUCO DEL MILÍMETRO)
    $conn->exec("SET time_zone = '-05:00';");
    $conn->exec("SET lc_time_names = 'es_ES';"); // Idioma español en fechas MySQL

} catch (\PDOException $e) {
    // Si falla, mostramos el error
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>