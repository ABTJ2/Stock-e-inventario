<?php
require_once __DIR__ . '/bootstrap.php';

$breadcrumb = $breadcrumb ?? 'Dashboard';
$breadcrumb_link = $breadcrumb_link ?? app_url('dashboard.php');

$userName = current_user()['full_name'] ?? $_SESSION['nombre_completo'] ?? 'Usuario';
$userRole = current_user()['role_name'] ?? $_SESSION['nombre_rol'] ?? 'Usuario';
?>

<header class="header">
    <div class="header-left">
        <nav class="breadcrumb">
            <a href="<?= e($breadcrumb_link); ?>"><?= e($breadcrumb); ?></a>
        </nav>
    </div>

    <div class="header-right">
        <div class="user-info">
            <div class="user-avatar">
                <?= e(strtoupper(substr((string) $userName, 0, 1))); ?>
            </div>
            <div class="user-details">
                <span class="user-name"><?= e($userName); ?></span>
                <span class="user-role"><?= e($userRole); ?></span>
            </div>
        </div>

        <a href="<?= app_url('logout.php'); ?>" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i>
            Cerrar sesión
        </a>
    </div>
</header>
