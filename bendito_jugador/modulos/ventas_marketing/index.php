<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

require_auth();

$modulo_activo = 'ventas_marketing';
$submodulo_activo = '';
$breadcrumb = 'Ventas y Marketing';
$breadcrumb_link = app_url('modulos/ventas_marketing/index.php');
$tituloModulo = 'Módulo de Ventas y Marketing';
$iconoModulo = 'fa-shopping-cart';
$secciones = [
    ['icono' => 'fa-user-tie', 'titulo' => 'Clientes', 'descripcion' => 'Gestión futura de clientes.'],
    ['icono' => 'fa-clipboard-list', 'titulo' => 'Pedidos', 'descripcion' => 'Registro y seguimiento de pedidos.'],
    ['icono' => 'fa-bullhorn', 'titulo' => 'Campañas', 'descripcion' => 'Acciones comerciales y promociones.'],
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tituloModulo); ?> - Bendito Jugador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= app_url('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../../includes/header.php'; ?>

            <div class="content-area">
                <h2 class="page-title">
                    <i class="fas <?= e($iconoModulo); ?>"></i>
                    <?= e($tituloModulo); ?>
                </h2>
                <p class="text-muted mb-4">Sección preparada para desarrollo futuro.</p>

                <div class="row g-3">
                    <?php foreach ($secciones as $seccion): ?>
                        <div class="col-lg-4 col-md-6 col-12">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="card-body">
                                    <div class="card-icon blue mb-3">
                                        <i class="fas <?= e($seccion['icono']); ?>"></i>
                                    </div>
                                    <h5 class="card-title mb-2"><?= e($seccion['titulo']); ?></h5>
                                    <p class="card-text text-muted mb-0"><?= e($seccion['descripcion']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php include __DIR__ . '/../../includes/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_url('js/main.js'); ?>"></script>
</body>
</html>
