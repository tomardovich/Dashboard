<?php
// inicio.php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}
include("conexion.php");

// --- VENTAS POR EMPRESA ---
$query = "
SELECT e.id_empresa, e.nombre AS empresa, COALESCE(SUM(dv.cantidad * dv.precio_unitario), 0) AS total_ventas
FROM empresa e
LEFT JOIN sucursal s ON e.id_empresa = s.id_empresa
LEFT JOIN vendedor ve ON s.id_sucursal = ve.id_sucursal
LEFT JOIN venta v ON ve.id_vendedor = v.id_vendedor
LEFT JOIN detalle_venta dv ON v.id_venta = dv.id_venta
GROUP BY e.id_empresa, e.nombre
";
$result = mysqli_query($conn, $query);
$empresas = $totales = $ids = [];
while ($row = mysqli_fetch_assoc($result)) {
    $ids[] = $row['id_empresa'];
    $empresas[] = $row['empresa'];
    $totales[] = $row['total_ventas'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
<div class="d-flex">
    <?php include("sidebar.php"); ?>

    <div class="p-4" style="margin-left: 220px; width: 100%;">
        <h3 class="text-center mb-4">Nivel 1 — Ventas por Empresa</h3>
        <canvas id="chartEmpresas"></canvas>

        <div class="mt-4">
            
        <!-- Leyenda de colores del semáforo -->
        <div class="mb-3">
            <span class="badge bg-success me-2">Verde: $400.000 o más</span>
            <span class="badge bg-warning text-dark me-2">Amarillo: entre $399.999 y $150.000</span>
            <span class="badge bg-danger">Rojo: menos de $150.000</span>
        </div>

            <h5>Indicador de rendimiento (Semáforo)</h5>
            <?php foreach ($empresas as $i => $nombre):
                $ventas = $totales[$i];
                $color = $ventas >= 400000 ? 'bg-success' : ($ventas >= 150000 ? 'bg-warning' : 'bg-danger');
            ?>
                <a href="detalle.php?id_empresa=<?= $ids[$i]; ?>" class="text-decoration-none">
                    <div class="p-2 rounded text-white mb-2 <?= $color; ?>">
                        <?= $nombre . " — $" . number_format($ventas, 0, ',', '.'); ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<script>
const ctx = document.getElementById('chartEmpresas');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($empresas); ?>,
        datasets: [{
            label: 'Ventas Totales',
            data: <?= json_encode($totales); ?>,
            backgroundColor: 'rgba(54,162,235,0.6)',
            borderWidth: 1
        }]
    },
    options: {
        onClick: (evt, elements) => {
            if (elements.length > 0) {
                const index = elements[0].index;
                const id = <?= json_encode($ids); ?>[index];
                window.location.href = "detalle.php?id_empresa=" + id;
            }
        }
    }
});
</script>
</body>
</html>
