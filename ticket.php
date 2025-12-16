<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

include("conexion.php");

// 1. Recibir ID de venta de forma segura
$id_venta = (int) ($_GET['id_venta'] ?? 0);

if ($id_venta == 0) {
    echo "Error: ID de venta no válido.";
    exit;
}

// 2. Obtener datos de cabecera (Fecha, Vendedor, Total)
$queryCabecera = "
    SELECT v.fecha, v.total, v.id_venta, 
           CONCAT(ve.nombre, ' ', ve.apellido) as vendedor,
           s.nombre as sucursal,
           e.nombre as empresa
    FROM venta v
    JOIN vendedor ve ON v.id_vendedor = ve.id_vendedor
    JOIN sucursal s ON ve.id_sucursal = s.id_sucursal
    JOIN empresa e ON s.id_empresa = e.id_empresa
    WHERE v.id_venta = $id_venta
";
$resultCabecera = mysqli_query($conn, $queryCabecera);

if (!$resultCabecera) {
    die("Error en la consulta SQL Cabecera: " . mysqli_error($conn));
}

$cabecera = mysqli_fetch_assoc($resultCabecera);

// --- PROTECCIÓN: O sea, si no se encuentra la venta ---
if (!$cabecera) {
    die("<div class='alert alert-danger m-5'>Error: No se encontraron datos para la Venta #$id_venta. <br> Verifica que la venta exista y tenga asignado un vendedor, sucursal y empresa correctamente.</div>");
}

// 3. Obtener el DETALLE
$queryDetalle = "
    SELECT p.nombre as producto, p.categoria, 
           dv.cantidad, dv.precio_unitario, dv.subtotal
    FROM detalle_venta dv
    JOIN producto p ON dv.id_producto = p.id_producto
    WHERE dv.id_venta = $id_venta
";
$resultDetalle = mysqli_query($conn, $queryDetalle);

if (!$resultDetalle) {
    die("Error en la consulta SQL Detalle: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket #<?= $id_venta; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="d-flex">
    <?php include("sidebar.php"); ?>

    <div class="p-4" style="margin-left: 220px; width: 100%;">
        
        <div class="container" style="max-width: 800px;">
            <a href="javascript:history.back()" class="btn btn-secondary mb-3">⬅ Volver</a>
            
            <div class="card shadow">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Ticket de Venta #<?= $cabecera['id_venta']; ?></h5>
                    <span><?= $cabecera['fecha']; ?></span>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <strong>Empresa:</strong> <?= htmlspecialchars($cabecera['empresa']); ?><br>
                            <strong>Sucursal:</strong> <?= htmlspecialchars($cabecera['sucursal']); ?>
                        </div>
                        <div class="col-md-6 text-end">
                            <strong>Vendedor:</strong> <?= htmlspecialchars($cabecera['vendedor']); ?>
                        </div>
                    </div>

                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th class="text-center">Cant.</th>
                                <th class="text-end">Precio Unit.</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($resultDetalle) > 0): ?>
                                <?php while($item = mysqli_fetch_assoc($resultDetalle)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['producto']); ?></td>
                                    <td><small class="text-muted"><?= htmlspecialchars($item['categoria']); ?></small></td>
                                    <td class="text-center"><?= $item['cantidad']; ?></td>
                                    <td class="text-end">$<?= number_format($item['precio_unitario'], 0, ',', '.'); ?></td>
                                    <td class="text-end fw-bold">$<?= number_format($item['subtotal'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No hay items en esta venta.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-group-divider">
                            <tr>
                                <td colspan="4" class="text-end fw-bold fs-5">TOTAL</td>
                                <td class="text-end fw-bold fs-5 text-success">$<?= number_format($cabecera['total'], 0, ',', '.'); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
</body>
</html>