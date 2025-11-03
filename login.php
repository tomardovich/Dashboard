<?php
include("conexion.php");

// Consulta de usuarios
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $clave = base64_encode($_POST['clave']);
    $sql = "SELECT * FROM usuario WHERE username='$usuario' AND password_encriptado='$clave'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "¡Usuario o contraseña incorrectos!";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <form method="POST" class="p-4 bg-white shadow rounded" style="width: 300px;">
        <h4 class="text-center mb-3">Iniciar sesión</h4>
        <input type="text" name="usuario" class="form-control mb-2" placeholder="Usuario" required>
        <input type="password" name="clave" class="form-control mb-3" placeholder="Contraseña" required>
        <button class="btn btn-primary w-100">Ingresar</button>
        <?php if(isset($error)) echo "<p class='text-danger mt-3'>$error</p>"; ?>
    </form>
</body>
</html>
