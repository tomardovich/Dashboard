<?php
// usuarios.php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}
include("conexion.php");

// --- USUARIOS ---
$queryUsuarios = "
SELECT id_usuario, nombre, apellido, username, email, rol, fecha_creacion, activo
FROM usuario
ORDER BY id_usuario
";
$resultUsuarios = mysqli_query($conn, $queryUsuarios);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="d-flex">
    <?php include("sidebar.php"); ?>

    <div class="p-4" style="margin-left: 220px; width: 100%;">
        <h3 class="text-center mb-4">Usuarios del Sistema</h3>
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Fecha Creación</th>
                    <th>Activo</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($resultUsuarios)): ?>
                    <tr>
                        <td><?= $row['id_usuario']; ?></td>
                        <td><?= $row['nombre']; ?></td>
                        <td><?= $row['apellido']; ?></td>
                        <td><?= $row['username']; ?></td>
                        <td><?= $row['email']; ?></td>
                        <td><?= $row['rol']; ?></td>
                        <td><?= $row['fecha_creacion']; ?></td>
                        <td><?= $row['activo'] ? 'Sí' : 'No'; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
