<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_module_access('reportes_gerenciales');

redirect(app_url('modulos/stock/reportes/index.php?reporte=gerencial'));
