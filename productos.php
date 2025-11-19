<?php
// productos.php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}
include("conexion.php");

// --- TOP 5 PRODUCTOS MÁS VENDIDOS ---
$queryProductos = "
SELECT p.nombre AS producto, SUM(dv.cantidad) AS total_vendidos
FROM detalle_venta dv
JOIN producto p ON dv.id_producto = p.id_producto
GROUP BY p.id_producto
ORDER BY total_vendidos DESC
LIMIT 5
";
$resultProductos = mysqli_query($conn, $queryProductos);
$productos = $vendidos = [];
while ($row = mysqli_fetch_assoc($resultProductos)) {
    $productos[] = $row['producto'];
    $vendidos[] = $row['total_vendidos'];
}

// --- LISTADO COMPLETO DE PRODUCTOS ---
$queryTablaProductos = "
SELECT p.id_producto, p.nombre, p.categoria, p.precio,
       COALESCE(SUM(dv.cantidad), 0) AS cantidad_vendida
FROM producto p
LEFT JOIN detalle_venta dv ON p.id_producto = dv.id_producto
GROUP BY p.id_producto, p.nombre, p.categoria, p.precio
ORDER BY p.id_producto
";
$resultTablaProductos = mysqli_query($conn, $queryTablaProductos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
<div class="d-flex">
    <?php include("sidebar.php"); ?>

    <div class="p-4" style="margin-left: 220px; width: 100%;">
        <h3 class="text-center mb-4">Top 5 Productos más Vendidos</h3>
        <canvas id="chartProductos"></canvas>

        <hr class="my-5">
        <h3 class="text-center mb-4">Listado Completo de Productos</h3>
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Precio ($)</th>
                    <th>Cantidad Vendida</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($resultTablaProductos)): ?>
                    <tr>
                        <td><?= $row['id_producto']; ?></td>
                        <td><?= $row['nombre']; ?></td>
                        <td><?= $row['categoria']; ?></td>
                        <td><?= number_format($row['precio'], 0, ',', '.'); ?></td>
                        <td><?= $row['cantidad_vendida']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
const ctxProd = document.getElementById('chartProductos');
new Chart(ctxProd, {
    type: 'bar',
    data: {
        labels: <?= json_encode($productos); ?>,
        datasets: [{
            label: 'Unidades Vendidas',
            data: <?= json_encode($vendidos); ?>,
            backgroundColor: 'rgba(255, 206, 86, 0.6)',
            borderWidth: 1
        }]
    },
    options: {
        plugins: {
            legend: { display: false },
            title: { display: true, text: 'Productos más vendidos (por cantidad)' }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
</body>
</html>
