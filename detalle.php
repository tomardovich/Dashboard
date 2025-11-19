<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

include("conexion.php");

$id_empresa = $_GET['id_empresa'] ?? null;
$id_sucursal = $_GET['id_sucursal'] ?? null;

// Nivel 3 — Por Vendedor
if ($id_sucursal) {
    $query = "SELECT CONCAT(ve.nombre, ' ', ve.apellido) AS vendedor, SUM(v.total) AS total
              FROM venta v
              JOIN vendedor ve ON v.id_vendedor = ve.id_vendedor
              WHERE ve.id_sucursal = $id_sucursal
              GROUP BY ve.id_vendedor";

    $queryVentas = "SELECT v.id_venta, v.total, v.fecha
                    FROM venta v
                    JOIN vendedor ve ON v.id_vendedor = ve.id_vendedor
                    WHERE ve.id_sucursal = $id_sucursal
                    ORDER BY v.fecha DESC";

    $titulo = "Nivel 3 — Ventas por Vendedor";
    $back = "detalle.php?id_empresa=(SELECT id_empresa FROM sucursal WHERE id_sucursal=$id_sucursal)";
} else {
    // Nivel 2 — Por Sucursal
    $query = "SELECT s.id_sucursal, s.nombre AS sucursal, SUM(v.total) AS total
              FROM sucursal s
              LEFT JOIN vendedor ve ON s.id_sucursal = ve.id_sucursal
              LEFT JOIN venta v ON ve.id_vendedor = v.id_vendedor
              WHERE s.id_empresa = $id_empresa
              GROUP BY s.id_sucursal, s.nombre";

    $queryVentas = "SELECT v.id_venta, v.total, v.fecha
                    FROM venta v
                    JOIN vendedor ve ON v.id_vendedor = ve.id_vendedor
                    JOIN sucursal s ON ve.id_sucursal = s.id_sucursal
                    WHERE s.id_empresa = $id_empresa
                    ORDER BY v.fecha DESC";

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

$resultVentas = mysqli_query($conn, $queryVentas);
$ventas = [];
while ($row = mysqli_fetch_assoc($resultVentas)) {
    $ventas[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $titulo ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        h3 { color: #333; }
        .sem-box { transition: transform 0.2s ease; }
        .sem-box:hover { transform: scale(1.02); }
        .shadow-md { box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-light">
<div class="d-flex">

    <!-- Sidebar -->
    <div class="bg-dark text-white p-3" style="min-width: 220px; height: 100vh; position: fixed;">
        <h4 class="mb-4">Menú</h4>
        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="dashboard.php">🏠 Volver al Dashboard</a>
            </li>
            <li class="nav-item mt-4">
                <a class="btn btn-outline-light w-100" href="logout.php">Cerrar sesión</a>
            </li>
        </ul>
    </div>

    <!-- Contenido -->
    <div class="p-4" style="margin-left: 220px; width: 100%;">
        <h3 class="text-center mb-4"><?= $titulo ?></h3>
        <a href="<?= $back ?>" class="btn btn-secondary mb-3">⬅ Volver</a>

        <!-- Gráfico -->
        <canvas id="chartDetalle"></canvas>

        <!-- Semáforo -->
        <div class="mt-4">
            <h5>Indicador de rendimiento (Semáforo)</h5>
            <?php foreach($labels as $i => $nombre):
                $ventasTotales = $totales[$i];
                $color = $ventasTotales >= 400000 ? 'bg-success' : ($ventasTotales >= 150000 ? 'bg-warning' : 'bg-danger');
            ?>
                <?php if ($id_sucursal): ?>
                    <div class="p-2 rounded text-white mb-2 <?= $color; ?>">
                        <?= $nombre . " — $" . number_format($ventasTotales, 0, ',', '.'); ?>
                    </div>
                <?php else: ?>
                    <a href="detalle.php?id_sucursal=<?= $ids[$i]; ?>" class="text-decoration-none">
                        <div class="p-2 rounded text-white mb-2 <?= $color; ?>">
                            <?= $nombre . " — $" . number_format($ventasTotales, 0, ',', '.'); ?>
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
</div>

<!-- Script Chart.js -->
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
