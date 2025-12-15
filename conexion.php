<?php
$servername = "localhost";
$username = "root";
$password = "123456"; // Recuerda cambiar esto en producción
$database = "dashboard_ventas";

$conn = mysqli_connect($servername, $username, $password, $database);

// Verificar conexión
if (!$conn) {
    die("Error al conectar: " . mysqli_connect_error());
}

// ESTO ES LO NUEVO E IMPORTANTE:
// Forzamos a que los datos viajen en UTF-8 para que se vean bien los acentos y ñ
mysqli_set_charset($conn, "utf8");

?>