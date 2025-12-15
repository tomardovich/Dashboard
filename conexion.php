<?php
$servername = "localhost";
$username = "root";
$password = "123456"; // Recuerda cambiar en producción
$database = "dashboard_ventas";

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Error al conectar: " . mysqli_connect_error());
}

// Forzamos UTF-8
mysqli_set_charset($conn, "utf8");

// --- CONFIGURACIÓN GLOBAL (KPIs) ---
// Definimos aquí las metas para todo el sistema
$meta_alta = 400000;   // Verde
$meta_media = 150000;  // Amarillo
// Rojo es todo lo que esté por debajo de media
?>