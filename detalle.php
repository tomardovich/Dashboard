<?php
// inicio.php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

include("conexion.php");

// 1. Sanitización y validación de IDs (IMPORTANTE)
// Forzamos a que sean enteros y establecemos un valor por defecto seguro (0)
$id_empresa = (int) ($_GET['id_empresa'] ?? 0);
$id_sucursal = (int) ($_GET['id_sucursal'] ?? 0);

// Inicialización de variables
$query = $queryVentas = $titulo = $back = null;
$labels = $totales = $ids = [];
$ventas = [];
$param_id = 0; // ID a usar en los prepared statements

// Definir metas al inicio para fácil configuración (Semáforo)
$meta_alta = 400000;
$meta_media = 150000;

// --- Lógica del Dashboard por Nivel ---

// Nivel 3 — Vendedor (Si tenemos un ID de sucursal válido)
if ($id_sucursal > 0) {
    $param_id = $id_sucursal;
    $titulo = "Nivel 3 — Ventas por Vendedor";
    
    // *** SEGURIDAD Y LÓGICA PARA EL BOTÓN VOLVER ***
    // Primero obtenemos el ID de la empresa padre de forma SEGURA
    $stmt_back = mysqli_prepare($conn, "SELECT id_empresa FROM sucursal WHERE id_sucursal = ?");
    mysqli_stmt_bind_param($stmt_back, "i", $param_id);
    mysqli_stmt_execute($stmt_back);
    $result_back = mysqli_stmt_get_result($stmt_back);
    $row_back = mysqli_fetch_assoc($result_back);
    mysqli_stmt_close($stmt_back);
    
    // Usamos el ID de empresa obtenido de forma segura para construir el link
    $id_empresa_for_back = $row_back['id_empresa'] ?? 0;
    $back = "detalle.php?id_empresa=" . $id_empresa_for_back;

    // CONSULTA PRINCIPAL (Agregado de Ventas por Vendedor)
    $query = "SELECT CONCAT(ve.nombre, ' ', ve.apellido) AS vendedor, COALESCE(SUM(v.total), 0) AS total
              FROM vendedor ve
              LEFT JOIN venta v ON v.id_vendedor = ve.id_vendedor
              WHERE ve.id_sucursal = ?
              GROUP BY ve.id_vendedor, ve.nombre, ve.apellido";

    // CONSULTA DE VENTAS INDIVIDUALES
    $queryVentas = "SELECT v.id_venta, v.total, v.fecha
                    FROM venta v
                    JOIN vendedor ve ON v.id_vendedor = ve.id_vendedor
                    WHERE ve.id_sucursal = ?
                    ORDER BY v.fecha DESC";
    
} 
// Nivel 2 — Sucursal (Si tenemos un ID de empresa válido)
else if ($id_empresa > 0) {
    $param_id = $id_empresa;
    $titulo = "Nivel 2 — Ventas por Sucursal";
    $back = "inicio.php";

    // CONSULTA PRINCIPAL (Agregado de Ventas por Sucursal)
    $query = "SELECT s.id_sucursal, s.nombre AS sucursal, COALESCE(SUM(v.total), 0) AS total
              FROM sucursal s
              LEFT JOIN vendedor ve ON s.id_sucursal = ve.id_sucursal
              LEFT JOIN venta v ON ve.id_vendedor = v.id_vendedor
              WHERE s.id_empresa = ?
              GROUP BY s.id_sucursal, s.nombre";

    // CONSULTA DE VENTAS INDIVIDUALES
    $queryVentas = "SELECT v.id_venta, v.total, v.fecha
                    FROM venta v
                    JOIN vendedor ve ON v.id_vendedor = ve.id_vendedor
                    JOIN sucursal s ON ve.id_sucursal = s.id_sucursal
                    WHERE s.id_empresa = ?
                    ORDER BY v.fecha DESC";
} else {
    // Si no hay ID en la URL, redirigir por seguridad
    header("Location: inicio.php");
    exit;
}

// --- Ejecución de Consultas con Sentencias Preparadas ---

// 1. Ejecución de la Consulta Principal ($query)
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $param_id); 
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Recolección de datos para el gráfico
while ($row = mysqli_fetch_assoc($result)) {
    // Si es Nivel 2, guardamos el ID de sucursal para el drill-down
    if (isset($row['id_sucursal'])) $ids[] = $row['id_sucursal'];
    
    // Obtenemos la etiqueta (nombre de sucursal o nombre de vendedor)
    $labels[] = $row[array_keys($row)[0]]; 
    $totales[] = $row['total'];
}

// 2. Ejecución de la Consulta de Ventas Individuales ($queryVentas)
$stmt_ventas = mysqli_prepare($conn, $queryVentas);
mysqli_stmt_bind_param($stmt_ventas, "i", $param_id); 
mysqli_stmt_execute($stmt_ventas);
$resultVentas = mysqli_stmt_get_result($stmt_ventas);
mysqli_stmt_close($stmt_ventas);

$ventas = mysqli_fetch_all($resultVentas, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($titulo); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
<div class="d-flex">
    <?php include("sidebar.php"); ?>

    <div class="p-4" style="margin-left: 220px; width: 100%;">
        <h3 class="text-center mb-4"><?= htmlspecialchars($titulo); ?></h3>
        
        <a href="<?= htmlspecialchars($back); ?>" class="btn btn-secondary mb-3">⬅ Volver</a>

        <canvas id="chartDetalle"></canvas>

        <div class="mt-4">
            <div class="mb-3">
                <span class="badge bg-success me-2">Verde: $<?= number_format($meta_alta, 0, ',', '.'); ?> o más</span>
                <span class="badge bg-warning text-dark me-2">Amarillo: entre $<?= number_format($meta_media, 0, ',', '.'); ?> y $<?= number_format($meta_alta - 1, 0, ',', '.'); ?></span>
                <span class="badge bg-danger">Rojo: menos de $<?= number_format($meta_media, 0, ',', '.'); ?></span>
            </div>
        
            <h5>Indicador de rendimiento (Semáforo)</h5>
            <?php foreach($labels as $i => $nombre): 
                $ventasTot = $totales[$i];
                $color = $ventasTot >= $meta_alta ? 'bg-success' : ($ventasTot >= $meta_media ? 'bg-warning' : 'bg-danger');
            ?>
                <?php if ($id_sucursal > 0): // Estamos en Nivel 3 (Vendedores), no hay link más abajo ?>
                    <div class="p-2 rounded text-white mb-2 <?= $color; ?>">
                        <?= htmlspecialchars($nombre) . " — $" . number_format($ventasTot, 0, ',', '.'); ?>
                    </div>
                <?php else: // Estamos en Nivel 2 (Sucursales), link a vendedores ?>
                    <a href="detalle.php?id_sucursal=<?= $ids[$i]; ?>" class="text-decoration-none">
                        <div class="p-2 rounded text-white mb-2 <?= $color; ?>">
                            <?= htmlspecialchars($nombre) . " — $" . number_format($ventasTot, 0, ',', '.'); ?>
                        </div>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <h5 class="mt-4">Ventas individuales:</h5>
        <?php foreach ($ventas as $venta): 
            $color = $venta['total'] >= $meta_alta ? 'bg-success' : ($venta['total'] >= $meta_media ? 'bg-warning' : 'bg-danger');
        ?>
            <div class="p-2 rounded text-white mb-2 <?= $color; ?>">
                Venta #<?= $venta['id_venta']; ?> — $<?= number_format($venta['total'], 0, ',', '.'); ?> (<?= $venta['fecha']; ?>)
            </div>
        <?php endforeach; ?>
    </div>
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
            // Solo activar click si estamos en nivel sucursal (para ir a vendedores)
            // Si $id_sucursal es 0 (falso), significa que estamos viendo sucursales y podemos hacer click
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