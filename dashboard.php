<?php
session_start();

// Verificación de sesión
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

// --- TOP 5 PRODUCTOS MÁS VENDIDOS ---
$queryProductos = "
SELECT p.nombre AS producto, SUM(dv.cantidad) AS total_vendidos
FROM detalle_venta dv
JOIN producto p ON dv.id_producto = p.id_producto
GROUP BY p.id_producto
ORDER BY total_vendidos DESC
LIMIT 5
";
$resultProductos = mysqli_query($conn, $queryProductos);
$productos = $vendidos = [];
while ($row = mysqli_fetch_assoc($resultProductos)) {
    $productos[] = $row['producto'];
    $vendidos[] = $row['total_vendidos'];
}

// --- LISTADO COMPLETO DE PRODUCTOS ---
$queryTablaProductos = "
SELECT p.id_producto, p.nombre, p.categoria, p.precio,
       COALESCE(SUM(dv.cantidad), 0) AS cantidad_vendida
FROM producto p
LEFT JOIN detalle_venta dv ON p.id_producto = dv.id_producto
GROUP BY p.id_producto, p.nombre, p.categoria, p.precio
ORDER BY p.id_producto
";
$resultTablaProductos = mysqli_query($conn, $queryTablaProductos);

// --- USUARIOS ---
$queryUsuarios = "
SELECT id_usuario, nombre, apellido, username, email, rol, fecha_creacion, activo
FROM usuario
ORDER BY id_usuario
";
$resultUsuarios = mysqli_query($conn, $queryUsuarios);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard de Ventas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        h3 { color: #333; }
        .sem-box { transition: transform 0.2s ease; }
        .sem-box:hover { transform: scale(1.02); }
        .shadow-md { box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="p-4 bg-light">
<div class="container">

    <!-- Top 5 Productos -->
    <section class="my-5">
        <h3 class="text-center mb-4">Top 5 Productos más Vendidos</h3>
        <canvas id="chartProductos"></canvas>
    </section>

    <!-- Ventas por Empresa -->
    <section class="my-5">
        <h3 class="text-center mb-4">Nivel 1 — Ventas por Empresa</h3>
        <canvas id="chartEmpresas"></canvas>
        <div class="mt-4">
            <h5 class="mb-3">Indicador de rendimiento (Semáforo)</h5>
            <?php foreach ($empresas as $i => $nombre):
                $ventas = $totales[$i];
                $color = $ventas >= 400000 ? 'bg-success' : ($ventas >= 150000 ? 'bg-warning' : 'bg-danger');
            ?>
            <a href="detalle.php?id_empresa=<?= $ids[$i]; ?>" class="text-decoration-none">
                <div class="p-2 rounded text-white mb-2 sem-box <?= $color; ?>">
                    <?= $nombre . " — $" . number_format($ventas, 0, ',', '.'); ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Tabla de Productos -->
    <section class="my-5">
        <h3 class="text-center mb-4">Listado Completo de Productos</h3>
        <div class="table-responsive">
            <table class="table table-striped table-bordered shadow-md">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th><th>Producto</th><th>Categoría</th><th>Precio ($)</th><th>Cantidad Vendida</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = mysqli_fetch_assoc($resultTablaProductos)): ?>
                    <tr>
                        <td><?= $row['id_producto']; ?></td>
                        <td><?= $row['nombre']; ?></td>
                        <td><?= $row['categoria']; ?></td>
                        <td><?= number_format($row['precio'], 0, ',', '.'); ?></td>
                        <td><?= $row['cantidad_vendida']; ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Tabla de Usuarios -->
    <section class="my-5">
        <h3 class="text-center mb-4">Usuarios del Sistema</h3>
        <div class="table-responsive">
            <table class="table table-striped table-bordered shadow-md">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th><th>Nombre</th><th>Apellido</th><th>Usuario</th><th>Email</th><th>Rol</th><th>Fecha Creación</th><th>Activo</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = mysqli_fetch_assoc($resultUsuarios)): ?>
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
    </section>
</div>

<script>
// Gráfico de Productos más Vendidos
new Chart(document.getElementById('chartProductos'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($productos); ?>,
        datasets: [{
            label: 'Unidades Vendidas',
            data: <?= json_encode($vendidos); ?>,
            backgroundColor: 'rgba(255, 206, 86, 0.6)',
            borderWidth: 1
        }]
    },
    options: {
        plugins: {
            legend: { display: false },
            title: { display: true, text: 'Productos más vendidos (por cantidad)' }
        },
        scales: { y: { beginAtZero: true } }
    }
});

// Gráfico de Ventas por Empresa
new Chart(document.getElementById('chartEmpresas'), {
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