<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_auth();

redirect(app_url('modulos/stock/reportes/index.php?reporte=gerencial'));
