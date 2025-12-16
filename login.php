<?php
include("conexion.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $clave = $_POST['clave'];

    // 1. Preparamos la consulta con un marcador de posición
    $sql = "SELECT id_usuario, username, password_encriptado, rol FROM usuario WHERE username = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);

    // 2. Vinculamos el parámetro
    mysqli_stmt_bind_param($stmt, "s", $usuario);

    // 3. Ejecutamos
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        // Verificar la contraseña con el hash
        if (password_verify($clave, $row['password_encriptado'])) {
            // Login exitoso
            $_SESSION['usuario'] = $row['username'];
            $_SESSION['rol'] = $row['rol']; 
            $_SESSION['id_usuario'] = $row['id_usuario']; 
            
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Usuario o contraseña incorrectos";
        }
    } else {
        $error = "Usuario o contraseña incorrectos";
    }
    
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Sistema de Ventas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #e9ecef;
            font-family: system-ui, sans-serif;
        }
        .login-box {
            width: 380px;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
        }
        .form-label {
            font-weight: 500;
        }
        .btn-primary {
            background-color: #0d6efd;
            border: none;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
        }
    </style>
</head>
<body class="d-flex justify-content-center align-items-center vh-100">

    <div class="login-box">
        <h4 class="text-center mb-4">Iniciar sesión</h4>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Usuario</label>
                <input type="text" name="usuario" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="clave" class="form-control" required>
            </div>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger py-2 text-center mb-3"><?= $error; ?></div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary w-100">Ingresar</button>
        </form>
    </div>

</body>
</html>