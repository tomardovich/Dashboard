<?php
$servername = "localhost";
$username = "root";
$password = "Paocgur1ñ31ñ4";
$database = "dashboard_ventas";

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Error al conectar: " . mysqli_connect_error());
}
?>
