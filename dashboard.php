<?php
include("conexion.php");

// CONSULTA PARA VENTAS POR EMPRESA
$query = "SELECT e.id_empresa, e.nombre AS empresa, COALESCE(SUM(v.total), 0) AS total_ventas
          FROM empresa e
          LEFT JOIN sucursal s ON e.id_empresa = s.id_empresa
          LEFT JOIN vendedor ve ON s.id_sucursal = ve.id_sucursal
          LEFT JOIN venta v ON ve.id_vendedor = v.id_vendedor
          GROUP BY e.id_empresa, e.nombre";
$result = mysqli_query($conn, $query);

$empresas = [];
$totales = [];
$ids = [];

while ($row = mysqli_fetch_assoc($result)) {
    $ids[] = $row['id_empresa'];
    $empresas[] = $row['empresa'];
    $totales[] = $row['total_ventas'];
}

// CONSULTA PARA TOP 5 PRODUCTOS VENDIDOS
$queryProductos = "SELECT p.nombre AS producto, SUM(v.cantidad) AS total_vendidos
                   FROM venta v
                   JOIN producto p ON v.id_producto = p.id_producto
                   GROUP BY p.id_producto
                   ORDER BY total_vendidos DESC
                   LIMIT 5";

$resultProductos = mysqli_query($conn, $queryProductos);

$productos = [];
$vendidos = [];

while ($row = mysqli_fetch_assoc($resultProductos)) {
    $productos[] = $row['producto'];
    $vendidos[] = $row['total_vendidos'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Dashboard de Ventas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="p-5 bg-light">
    
<hr class="my-5">
<h3 class="text-center mb-4">Top 5 Productos más Vendidos</h3>
<div class="container">
    <canvas id="chartProductos"></canvas>
</div>

<script>
// Gráfico de barras para productos más vendidos
const ctxProd = document.getElementById('chartProductos');
new Chart(ctxProd, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($productos); ?>,
        datasets: [{
            label: 'Unidades Vendidas',
            data: <?php echo json_encode($vendidos); ?>,
            borderWidth: 1,
            backgroundColor: 'rgba(255, 206, 86, 0.6)'
        }]
    },
    options: {
        plugins: {
            legend: { display: false },
            title: {
                display: true,
                text: 'Productos más vendidos (por cantidad)'
            }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
// Fin gráfico productos más vendidos
</script>

<h3 class="text-center mb-4">Nivel 1 — Ventas por Empresa</h3>

<div class="container">
    <canvas id="chartEmpresas"></canvas>
    <div class="mt-4"> 
        <h5>Indicador de rendimiento (Semáforo)</h5> 
        <?php foreach($empresas as $i => $nombre): 
            $ventas = $totales[$i];
            if ($ventas >= 400000) {
                $color = 'bg-success'; // Verde
            } elseif ($ventas >= 150000) {
                $color = 'bg-warning'; // Amarillo
            } else {
                $color = 'bg-danger'; // Rojo
            }
        ?>
            <a href="detalle.php?id_empresa=<?php echo $ids[$i]; ?>" class="text-decoration-none">
                <div class="p-2 rounded text-white mb-2 <?php echo $color; ?>">
                    <?php echo $nombre . " — $" . number_format($ventas, 0, ',', '.'); ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<script>
// Gráfico de barras para ventas por empresa
const ctx = document.getElementById('chartEmpresas');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($empresas); ?>,
        datasets: [{
            label: 'Ventas Totales',
            data: <?php echo json_encode($totales); ?>,
            borderWidth: 1,
            backgroundColor: 'rgba(54,162,235,0.6)'
        }]
    },
    options: {
        onClick: (evt, elements) => {
            if (elements.length > 0) {
                const index = elements[0].index;
                const id = <?php echo json_encode($ids); ?>[index];
                window.location.href = "detalle.php?id_empresa=" + id;
            }
        }
    }
});
</script>
</body>
</html>
