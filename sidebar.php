<div class="bg-dark text-white p-3" style="min-width: 220px; height: 100vh; position: fixed;">
    <h4 class="mb-4">Menú</h4>
    <ul class="nav flex-column">
        <li class="nav-item mb-2">
            <a class="nav-link text-white" href="inicio.php">🏠 Inicio</a>
        </li>
        
        <li class="nav-item mb-2">
            <a class="nav-link text-white" href="productos.php">📦 Productos</a>
        </li>

        <li class="nav-item mb-2">
            <a class="nav-link text-white" href="vendedores.php">👔 Vendedores</a>
        </li>
        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'Administrador'): ?>
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="usuarios.php">👥 Usuarios</a>
            </li>
        <?php endif; ?>
        
        <li class="nav-item mt-4">
            <a class="btn btn-outline-light w-100" href="logout.php">Cerrar sesión</a>
        </li>
    </ul>
    
    <div class="mt-4 pt-3 border-top text-center text-white-50" style="font-size: 0.9rem;">
        Logueado como:<br>
        <strong class="text-white"><?= htmlspecialchars($_SESSION['usuario'] ?? 'Invitado'); ?></strong>
    </div>
</div>