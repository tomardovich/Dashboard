<?php
session_start();

// 1. Verificamos que esté logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// 2. VERIFICACIÓN DE ROL (¡CRÍTICO!)
// Si el rol NO es Administrador, lo mandamos de vuelta al inicio.
if ($_SESSION['rol'] != 'Administrador') {
    // Opcional: Podrías mandarlo a una página de "Acceso Denegado"
    header("Location: inicio.php"); 
    exit;
}

include("conexion.php");

// --- USUARIOS ---
// No hay input de usuario aquí, así que el query simple es seguro contra SQLi.
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
                        <td><?= htmlspecialchars($row['id_usuario']); ?></td>
                        <td><?= htmlspecialchars($row['nombre']); ?></td>
                        <td><?= htmlspecialchars($row['apellido']); ?></td>
                        <td><?= htmlspecialchars($row['username']); ?></td>
                        <td><?= htmlspecialchars($row['email']); ?></td>
                        
                        <td>
                            <?php if($row['rol'] == 'Administrador'): ?>
                                <span class="badge bg-danger">Administrador</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= htmlspecialchars($row['rol']); ?></span>
                            <?php endif; ?>
                        </td>
                        
                        <td><?= htmlspecialchars($row['fecha_creacion']); ?></td>
                        
                        <td>
                            <?php if($row['activo']): ?>
                                <span class="text-success fw-bold">Sí</span>
                            <?php else: ?>
                                <span class="text-danger fw-bold">No</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>