<?php
session_start();

// Verifica si hay sesión iniciada, si no, redirige al login
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

include("conexion.php");

$id_empresa = $_GET['id_empresa'] ?? null;
$id_sucursal = $_GET['id_sucursal'] ?? null;

if ($id_sucursal) {
    // Nivel 3 — Ventas por Vendedor
    $query = "SELECT CONCAT(ve.nombre, ' ', ve.apellido) AS vendedor, SUM(v.total) AS total
              FROM venta v
              JOIN vendedor ve ON v.id_vendedor = ve.id_vendedor
              WHERE ve.id_sucursal = $id_sucursal
              GROUP BY ve.id_vendedor";

    $queryVentasVendedor = "SELECT v.id_venta, v.total, v.fecha
                             FROM venta v
                             JOIN vendedor ve ON v.id_vendedor = ve.id_vendedor
                             WHERE ve.id_sucursal = $id_sucursal
                             ORDER BY v.fecha DESC";

    $resultVentas = mysqli_query($conn, $queryVentasVendedor);
    $ventas = mysqli_fetch_all($resultVentas, MYSQLI_ASSOC);

    $titulo = "Nivel 3 — Ventas por Vendedor";
    $back = "dashboard.php";
} else {
    // Nivel 2 — Ventas por Sucursal
    $query = "SELECT s.id_sucursal, s.nombre AS sucursal, SUM(v.total) AS total
              FROM sucursal s
              LEFT JOIN vendedor ve ON s.id_sucursal = ve.id_sucursal
              LEFT JOIN venta v ON ve.id_vendedor = v.id_vendedor
              WHERE s.id_empresa = $id_empresa
              GROUP BY s.id_sucursal, s.nombre";

    $queryVentasSucursal = "SELECT v.id_venta, v.total, v.fecha
                             FROM venta v
                             JOIN vendedor ve ON v.id_vendedor = ve.id_vendedor
                             JOIN sucursal s ON ve.id_sucursal = s.id_sucursal
                             WHERE s.id_empresa = $id_empresa
                             ORDER BY v.fecha DESC";

    $resultVentas = mysqli_query($conn, $queryVentasSucursal);
    $ventas = mysqli_fetch_all($resultVentas, MYSQLI_ASSOC);

    $titulo = "Nivel 2 — Ventas por Sucursal";
    $back = "dashboard.php";
}

$result = mysqli_query($conn, $query);
$labels = $totales = $ids = [];
while ($row = mysqli_fetch_assoc($result)) {
    if (isset($row['id_sucursal'])) $ids[] = $row['id_sucursal'];
    $labels[] = $row[array_keys($row)[0]];
    $totales[] = $row['total'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $titulo; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="p-5 bg-light">
<div class="container">
    <h3 class="text-center mb-4"><?= $titulo; ?></h3>
    <a href="<?= $back; ?>" class="btn btn-secondary mb-3">⬅ Volver</a>

    <canvas id="chartDetalle"></canvas>

    <!-- Semáforo de Rendimiento -->
    <div class="mt-4">
        <h5>Indicador de rendimiento (Semáforo)</h5>
        <?php foreach ($labels as $i => $nombre):
            $ventasTot = $totales[$i];
            $color = $ventasTot >= 400000 ? 'bg-success' : ($ventasTot >= 150000 ? 'bg-warning' : 'bg-danger');
        ?>
            <?php if ($id_sucursal): ?>
                <div class="p-2 rounded text-white mb-2 <?= $color; ?>">
                    <?= $nombre . " — $" . number_format($ventasTot, 0, ',', '.'); ?>
                </div>
            <?php else: ?>
                <a href="detalle.php?id_sucursal=<?= $ids[$i]; ?>" class="text-decoration-none">
                    <div class="p-2 rounded text-white mb-2 <?= $color; ?>">
                        <?= $nombre . " — $" . number_format($ventasTot, 0, ',', '.'); ?>
                    </div>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- Ventas individuales -->
    <h5 class="mt-4">Ventas individuales:</h5>
    <?php foreach ($ventas as $venta):
        $color = $venta['total'] >= 400000 ? 'bg-success' : ($venta['total'] >= 150000 ? 'bg-warning' : 'bg-danger');
    ?>
        <div class="p-2 rounded text-white mb-2 <?= $color; ?>">
            Venta <?= $venta['id_venta']; ?> — $<?= number_format($venta['total'], 0, ',', '.'); ?> (<?= $venta['fecha']; ?>)
        </div>
    <?php endforeach; ?>
</div>

<script>
const ctx = document.getElementById('chartDetalle');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels); ?>,
        datasets: [{
            label: 'Ventas Totales',
            data: <?= json_encode($totales); ?>,
            backgroundColor: 'rgba(255,99,132,0.6)',
            borderWidth: 1
        }]
    },
    options: {
        onClick: (evt, elements) => {
            if (elements.length > 0 && <?= $id_sucursal ? 'false' : 'true'; ?>) {
                const index = elements[0].index;
                const id = <?= json_encode($ids); ?>[index];
                window.location.href = "detalle.php?id_sucursal=" + id;
            }
        }
    }
});
</script>
</body>
</html>
