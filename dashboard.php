<?php
include("conexion.php");

// Consulta de ventas por empresa
$query = "SELECT e.nombre AS empresa, COALESCE(SUM(v.total), 0) AS total_ventas
          FROM empresa e
          LEFT JOIN sucursal s ON e.id_empresa = s.id_empresa
          LEFT JOIN vendedor ve ON s.id_sucursal = ve.id_sucursal
          LEFT JOIN venta v ON ve.id_vendedor = v.id_vendedor
          GROUP BY e.nombre";
$result = mysqli_query($conn, $query);

$empresas = [];
$totales = [];
while ($row = mysqli_fetch_assoc($result)) {
    $empresas[] = $row['empresa'];
    $totales[] = $row['total_ventas'];
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
<h3 class="text-center mb-4">Dashboard de Ventas por Empresa</h3>

<div class="container">
    <canvas id="chartEmpresas"></canvas>

    <div class="mt-4">
        <h5>Indicador de rendimiento (Semáforo)</h5>
        <?php foreach($empresas as $i => $nombre): 
            if ($totales[$i] >= 400000) {
                $color = 'bg-success'; // verde
                } elseif ($totales[$i] >= 150000) {
                $color = 'bg-warning'; // amarillo
                } else {
                $color = 'bg-danger'; // rojo
                }
        ?>
            <div class="p-2 rounded text-white mb-2 <?php echo $color; ?>">
                <?php echo $nombre . " — $" . number_format($totales[$i], 2); ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
const ctx = document.getElementById('chartEmpresas');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($empresas); ?>,
        datasets: [{
            label: 'Ventas Totales',
            data: <?php echo json_encode($totales); ?>,
            borderWidth: 1
        }]
    }
});
</script>
</body>
</html>
