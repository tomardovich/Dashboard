<?php
session_start();

// Si el usuario NO está logueado, volver al login
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

include("conexion.php");

// CONSULTA PARA VENTAS POR EMPRESA
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

$empresas = [];
$totales = [];
$ids = [];

while ($row = mysqli_fetch_assoc($result)) {
    $ids[] = $row['id_empresa'];
    $empresas[] = $row['empresa'];
    $totales[] = $row['total_ventas'];
}

// CONSULTA PARA TOP 5 PRODUCTOS VENDIDOS
$queryProductos = "SELECT p.nombre AS producto, SUM(dv.cantidad) AS total_vendidos
                   FROM detalle_venta dv
                   JOIN producto p ON dv.id_producto = p.id_producto
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

// --- CONSULTA PARA LISTAR TODOS LOS PRODUCTOS ---
$queryTablaProductos = "
    SELECT p.id_producto, p.nombre, p.categoria, p.precio,
           COALESCE(SUM(dv.cantidad), 0) AS cantidad_vendida
    FROM producto p
    LEFT JOIN detalle_venta dv ON p.id_producto = dv.id_producto
    GROUP BY p.id_producto, p.nombre, p.categoria, p.precio
    ORDER BY p.id_producto;
";

$resultTablaProductos = mysqli_query($conn, $queryTablaProductos);

// --- CONSULTA PARA USUARIOS ---
$queryUsuarios = "SELECT id_usuario, nombre, apellido, username, email, rol, fecha_creacion, activo FROM usuario ORDER BY id_usuario";
$resultUsuarios = mysqli_query($conn, $queryUsuarios);

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

<hr class="my-5">

<h3 class="text-center mb-4">Listado Completo de Productos</h3>

<div class="container">
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
            <?php while($row = mysqli_fetch_assoc($resultTablaProductos)): ?>
                <tr>
                    <td><?php echo $row['id_producto']; ?></td>
                    <td><?php echo $row['nombre']; ?></td>
                    <td><?php echo $row['categoria']; ?></td>
                    <td><?php echo number_format($row['precio'], 0, ',', '.'); ?></td>
                    <td><?php echo $row['cantidad_vendida']; ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<hr class="my-5">

<h3 class="text-center mb-4">Usuarios del Sistema</h3>

<div class="container">
    <table id="tablaUsuarios" class="table table-striped table-bordered">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Usuario</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Fecha Creación</th>
                <th>Activo</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($resultUsuarios)): ?>
            <tr>
                <td><?= $row['id_usuario']; ?></td>
                <td><?= $row['nombre']; ?></td>
                <td><?= $row['apellido']; ?></td>
                <td><?= $row['username']; ?></td>
                <td><?= $row['email']; ?></td>
                <td><?= $row['rol']; ?></td>
                <td><?= $row['fecha_creacion']; ?></td>
                <td><?= $row['activo'] ? 'Sí' : 'No'; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<script>
$(document).ready(function() {
    $('#tablaUsuarios').DataTable({
        "pageLength": 5,
        "language": {
            "search": "Buscar:",
            "paginate": {
                "next": "Siguiente",
                "previous": "Anterior"
            }
        }
    });
});
</script>
</body>
</html>
