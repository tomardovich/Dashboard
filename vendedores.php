<?php
session_start();
// 1. Seguridad: Verificar Login
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

include("conexion.php");

// 2. Consulta con JOINs para traer nombres reales
// Donde se unen Vendedor -> Sucursal -> Empresa
$queryVendedores = "
SELECT v.id_vendedor, v.nombre, v.apellido, s.nombre AS sucursal, e.nombre AS empresa
FROM vendedor v
LEFT JOIN sucursal s ON v.id_sucursal = s.id_sucursal
LEFT JOIN empresa e ON s.id_empresa = e.id_empresa
ORDER BY e.nombre, s.nombre, v.apellido
";

$resultVendedores = mysqli_query($conn, $queryVendedores);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Vendedores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="d-flex">
    <?php include("sidebar.php"); ?>

    <div class="p-4" style="margin-left: 220px; width: 100%;">
        <h3 class="text-center mb-4">Staff de Vendedores</h3>
        
        <div class="card shadow-sm">
            <div class="card-body">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre Completo</th>
                            <th>Sucursal Asignada</th>
                            <th>Empresa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($resultVendedores)): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($row['id_vendedor']); ?></td>
                                
                                <td class="fw-bold">
                                    <?= htmlspecialchars($row['nombre'] . " " . $row['apellido']); ?>
                                </td>
                                
                                <td>
                                    <span class="badge bg-info text-dark">
                                        <?= htmlspecialchars($row['sucursal'] ?? 'Sin asignar'); ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <small class="text-muted text-uppercase fw-bold">
                                        <?= htmlspecialchars($row['empresa'] ?? '-'); ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="mt-3 text-end text-muted">
            Total de vendedores: <strong><?= mysqli_num_rows($resultVendedores); ?></strong>
        </div>

    </div>
</div>
</body>
</html>