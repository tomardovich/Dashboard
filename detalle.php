<?php
session_start();

// Redirecciona si no hay sesión activa
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

include("conexion.php");

$id_empresa = isset($_GET['id_empresa']) ? $_GET['id_empresa'] : null;
$id_sucursal = isset($_GET['id_sucursal']) ? $_GET['id_sucursal'] : null;

// Nivel 3 — Ventas por Vendedor
if ($id_sucursal) {
    // Consulta para gráfico y semáforo por vendedor
    $query = "SELECT CONCAT(ve.nombre, ' ', ve.apellido) AS vendedor, SUM(v.total) AS total
              FROM venta v
              JOIN vendedor ve ON v.id_vendedor = ve.id_vendedor
              WHERE ve.id_sucursal = $id_sucursal
              GROUP BY ve.id_vendedor";

    // Consulta para mostrar ventas individuales
    $queryVentasVendedor = "SELECT v.id_venta, v.total, v.fecha
                            FROM venta v
                            JOIN vendedor ve ON v.id_vendedor = ve.id_vendedor
                            WHERE ve.id_sucursal = $id_sucursal
                            ORDER BY v.fecha DESC";

    $resultVentasVendedor = mysqli_query($conn, $queryVentasVendedor);
    $ventasVendedor = [];

    while ($row = mysqli_fetch_assoc($resultVentasVendedor)) {
        $ventasVendedor[] = $row;
    }

    $titulo = "Nivel 3 — Ventas por Vendedor";
    $back = "detalle.php?id_empresa=(SELECT id_empresa FROM sucursal WHERE id_sucursal=$id_sucursal)";

} 
else {
    // Nivel 2 — Ventas por Sucursal
    $query = "SELECT s.id_sucursal, s.nombre AS sucursal, SUM(v.total) AS total
              FROM sucursal s
              LEFT JOIN vendedor ve ON s.id_sucursal = ve.id_sucursal
              LEFT JOIN venta v ON ve.id_vendedor = v.id_vendedor
              WHERE s.id_empresa = $id_empresa
              GROUP BY s.id_sucursal, s.nombre";

    // Consulta para mostrar ventas individuales
    $queryVentasSucursal = "SELECT v.id_venta, v.total, v.fecha
                            FROM venta v
                            JOIN vendedor ve ON v.id_vendedor = ve.id_vendedor
                            JOIN sucursal s ON ve.id_sucursal = s.id_sucursal
                            WHERE s.id_empresa = $id_empresa
                            ORDER BY v.fecha DESC";

    $resultVentasSucursal = mysqli_query($conn, $queryVentasSucursal);
    $ventasSucursal = [];
    while ($row = mysqli_fetch_assoc($resultVentasSucursal)) {
        $ventasSucursal[] = $row;
    }

    $titulo = "Nivel 2 — Ventas por Sucursal";
    $back = "dashboard.php";
}

// Consulta principal para el gráfico
$result = mysqli_query($conn, $query);
$labels = [];
$totales = [];
$ids = [];

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
    <title><?php echo $titulo; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="p-5 bg-light">
<div class="container">    
    <h3 class="text-center mb-4"><?php echo $titulo; ?></h3>
    
    <a href="<?php echo $back; ?>" class="btn btn-secondary mb-3">⬅ Volver</a>

    <!-- Gráfico de barras -->
    <canvas id="chartDetalle"></canvas>

    <!-- Semáforo de rendimiento (por vendedor o sucursal) -->
    <div class="mt-4">
        <h5>Indicador de rendimiento (Semáforo)</h5>
        <?php foreach($labels as $i => $nombre): 
            $ventas = $totales[$i];
            if ($ventas >= 400000) $color = 'bg-success';
            elseif ($ventas >= 150000) $color = 'bg-warning';
            else $color = 'bg-danger';
        ?>
            <?php if($id_sucursal): ?>
                <div class="p-2 rounded text-white mb-2 <?= $color; ?>">
                    <?= $nombre . " — $" . number_format($ventas, 0, ',', '.'); ?>
                </div>
            <?php else: ?>
                <a href="detalle.php?id_sucursal=<?= $ids[$i]; ?>" class="text-decoration-none">
                    <div class="p-2 rounded text-white mb-2 <?= $color; ?>">
                        <?= $nombre . " — $" . number_format($ventas, 0, ',', '.'); ?>
                    </div>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- Ventas individuales (por venta) -->
    <h5 class="mt-4">Ventas individuales:</h5>
    <?php
    $ventas = $id_sucursal ? $ventasVendedor : $ventasSucursal;
    foreach ($ventas as $venta):
        $color = ($venta['total'] >= 400000) ? 'bg-success' : (($venta['total'] >= 150000) ? 'bg-warning' : 'bg-danger');
    ?>
        <div class="p-2 rounded text-white mb-2 <?= $color; ?>">
            Venta <?= $venta['id_venta']; ?> — $<?= number_format($venta['total'], 0, ',', '.'); ?> (<?= $venta['fecha']; ?>)
        </div>
    <?php endforeach; ?>
</div>

<!-- Script del gráfico -->
<script>
const ctx = document.getElementById('chartDetalle');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            label: 'Ventas Totales',
            data: <?php echo json_encode($totales); ?>,
            borderWidth: 1,
            backgroundColor: 'rgba(255,99,132,0.6)'
        }]
    },
    options: {
        onClick: (evt, elements) => {
            if (elements.length > 0 && <?php echo $id_sucursal ? 'false' : 'true'; ?>) {
                const index = elements[0].index;
                const id = <?php echo json_encode($ids); ?>[index];
                window.location.href = "detalle.php?id_sucursal=" + id;
            }
        }
    }
});
</script>
</body>
</html>
