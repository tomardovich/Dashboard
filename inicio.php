<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

include("conexion.php"); 
// Al incluir conexion.php, tenemos disponibles $meta_alta y $meta_media

// --- VENTAS POR EMPRESA ---
// Consulta segura
$query = "
SELECT e.id_empresa, e.nombre AS empresa, COALESCE(SUM(v.total), 0) AS total_ventas
FROM empresa e
LEFT JOIN sucursal s ON e.id_empresa = s.id_empresa
LEFT JOIN vendedor ve ON s.id_sucursal = ve.id_sucursal
LEFT JOIN venta v ON ve.id_vendedor = v.id_vendedor
GROUP BY e.id_empresa, e.nombre
";

$result = mysqli_query($conn, $query);
$empresas = $totales = $ids = [];

while ($row = mysqli_fetch_assoc($result)) {
    $ids[] = $row['id_empresa'];
    $empresas[] = $row['empresa']; // Para el gráfico JS
    $totales[] = $row['total_ventas'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
<div class="d-flex">
    <?php include("sidebar.php"); ?>

    <div class="p-4" style="margin-left: 220px; width: 100%;">
        <h3 class="text-center mb-4">Nivel 1 — Ventas por Empresa</h3>
        
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <canvas id="chartEmpresas" style="max-height: 400px;"></canvas>
            </div>
        </div>

        <div class="mt-4">
            <div class="mb-3">
                <span class="badge bg-success me-2">Verde: $<?= number_format($meta_alta, 0, ',', '.'); ?> o más</span>
                <span class="badge bg-warning text-dark me-2">Amarillo: entre $<?= number_format($meta_media, 0, ',', '.'); ?> y $<?= number_format($meta_alta - 1, 0, ',', '.'); ?></span>
                <span class="badge bg-danger">Rojo: menos de $<?= number_format($meta_media, 0, ',', '.'); ?></span>
            </div>

            <h5>Indicador de rendimiento (Semáforo)</h5>
            <div class="row">
            <?php foreach ($empresas as $i => $nombre):
                $ventas = $totales[$i];
                // Lógica centralizada
                $color = $ventas >= $meta_alta ? 'bg-success' : ($ventas >= $meta_media ? 'bg-warning' : 'bg-danger');
            ?>
                <div class="col-md-6 mb-2">
                    <a href="detalle.php?id_empresa=<?= $ids[$i]; ?>" class="text-decoration-none">
                        <div class="p-3 rounded text-white shadow-sm d-flex justify-content-between align-items-center <?= $color; ?> hover-effect">
                            <span class="fw-bold"><?= htmlspecialchars($nombre); ?></span>
                            <span class="badge bg-white text-dark">$<?= number_format($ventas, 0, ',', '.'); ?></span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
            </div>
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
            backgroundColor: 'rgba(54, 162, 235, 0.7)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        onClick: (evt, elements) => {
            if (elements.length > 0) {
                const index = elements[0].index;
                const id = <?= json_encode($ids); ?>[index];
                window.location.href = "detalle.php?id_empresa=" + id;
            }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

<style>
    .hover-effect { transition: transform 0.2s; }
    .hover-effect:hover { transform: scale(1.02); cursor: pointer; }
</style>

</body>
</html>