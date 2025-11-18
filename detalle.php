<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

include("conexion.php");

$id_empresa = isset($_GET['id_empresa']) ? $_GET['id_empresa'] : null;
$id_sucursal = isset($_GET['id_sucursal']) ? $_GET['id_sucursal'] : null;

// Determinar el nivel de detalle
if ($id_sucursal) {
    // Nivel 3 - VENTAS POR VENDEDOR
    // CONSULTA PARA VENTAS POR VENDEDOR
    $query = "SELECT CONCAT(ve.nombre, ' ', ve.apellido) AS vendedor, 
                 COALESCE(SUM(dv.cantidad * dv.precio_unitario), 0) AS total
          FROM vendedor ve
          LEFT JOIN venta v ON ve.id_vendedor = v.id_vendedor
          LEFT JOIN detalle_venta dv ON v.id_venta = dv.id_venta
          WHERE ve.id_sucursal = $id_sucursal
          GROUP BY ve.id_vendedor";

    $titulo = "Nivel 3 — Ventas por Vendedor";
    $back = "detalle.php?id_empresa=(SELECT id_empresa FROM sucursal WHERE id_sucursal=$id_sucursal)";
} else {
    // Nivel 2 - VENTAS POR SUCURSAL
    // CONSULTA PARA VENTAS POR SUCURSAL
    $query = "SELECT s.id_sucursal, s.nombre AS sucursal, 
                 COALESCE(SUM(dv.cantidad * dv.precio_unitario), 0) AS total
          FROM sucursal s
          LEFT JOIN vendedor ve ON s.id_sucursal = ve.id_sucursal
          LEFT JOIN venta v ON ve.id_vendedor = v.id_vendedor
          LEFT JOIN detalle_venta dv ON v.id_venta = dv.id_venta
          WHERE s.id_empresa = $id_empresa
          GROUP BY s.id_sucursal, s.nombre";

    $titulo = "Nivel 2 — Ventas por Sucursal";
    $back = "dashboard.php";
}

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

    <canvas id="chartDetalle"></canvas>
    
    <div class="mt-4"> 
        <?php foreach($labels as $i => $nombre): 
            $ventas = $totales[$i];
            if ($ventas >= 400000) $color = 'bg-success'; // Verde
            elseif ($ventas >= 150000) $color = 'bg-warning'; // Amarillo
            else $color = 'bg-danger'; // Rojo
        ?>
            <?php if($id_sucursal): ?>
                <div class="p-2 rounded text-white mb-2 <?php echo $color; ?>">
                    <?php echo $nombre . " — $" . number_format($ventas, 0, ',', '.'); ?>
                </div>
            <?php else: ?>
                <a href="detalle.php?id_sucursal=<?php echo $ids[$i]; ?>" class="text-decoration-none">
                    <div class="p-2 rounded text-white mb-2 <?php echo $color; ?>">
                        <?php echo $nombre . " — $" . number_format($ventas, 0, ',', '.'); ?>
                    </div>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

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
