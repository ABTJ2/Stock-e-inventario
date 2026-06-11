<input type="hidden" name="cbu" value="<?= e($p['cbu'] ?? ''); ?>">
<input type="hidden" name="alias" value="<?= e($p['alias'] ?? ''); ?>">

<div class="row g-3 proveedores-form-grid">
    <div class="col-md-4">
        <label class="form-label">CUIT *</label>
        <input type="text" name="cuit" class="form-control" maxlength="20" inputmode="numeric" pattern="[0-9]{2}-?[0-9]{8}-?[0-9]{1}" placeholder="20-12345678-5" value="<?= e($p['cuit'] ?? ''); ?>" required>
    </div>
    <div class="col-md-8">
        <label class="form-label">Razón social *</label>
        <input type="text" name="razon_social" class="form-control" maxlength="100" value="<?= e($p['razon_social'] ?? ''); ?>" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Nombre fantasía</label>
        <input type="text" name="nombre_fantasia" class="form-control" maxlength="150" value="<?= e($p['nombre_fantasia'] ?? ''); ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label">Estado *</label>
        <select name="id_estado_proveedor" class="form-select" required>
            <option value="">Seleccionar</option>
            <?php foreach ($estados as $estado): ?>
                <option value="<?= (int) $estado['id_estado_proveedor']; ?>" <?= proveedor_selected($p['id_estado_proveedor'] ?? '', $estado['id_estado_proveedor']); ?>>
                    <?= e($estado['nombre_estado']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Rubro *</label>
        <select name="id_rubro_proveedor" class="form-select" required>
            <option value="">Seleccionar</option>
            <?php foreach ($rubros as $rubro): ?>
                <option value="<?= (int) $rubro['id_rubro_proveedor']; ?>" <?= proveedor_selected($p['id_rubro_proveedor'] ?? '', $rubro['id_rubro_proveedor']); ?>>
                    <?= e($rubro['nombre']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Condición IVA *</label>
        <select name="id_condicion_iva" class="form-select" required>
            <option value="">Seleccionar</option>
            <?php foreach ($condicionesIva as $condicion): ?>
                <option value="<?= (int) $condicion['id_condicion_iva']; ?>" <?= proveedor_selected($p['id_condicion_iva'] ?? '', $condicion['id_condicion_iva']); ?>>
                    <?= e($condicion['nombre']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">Dirección</label>
        <textarea name="direccion" class="form-control" rows="2"><?= e($p['direccion'] ?? ''); ?></textarea>
    </div>
    <div class="col-md-4">
        <label class="form-label">País *</label>
        <select name="id_pais" class="form-select" required>
            <option value="">Seleccionar</option>
            <?php foreach ($paises as $pais): ?>
                <option value="<?= (int) $pais['id_pais']; ?>" <?= proveedor_selected($p['id_pais'] ?? '', $pais['id_pais']); ?>>
                    <?= e($pais['nombre']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Provincia *</label>
        <select name="id_provincia" class="form-select" required>
            <option value="">Seleccionar</option>
            <?php foreach ($provincias as $provincia): ?>
                <option value="<?= (int) $provincia['id_provincia']; ?>" <?= proveedor_selected($p['id_provincia'] ?? '', $provincia['id_provincia']); ?>>
                    <?= e($provincia['nombre']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Localidad *</label>
        <select name="id_localidad" class="form-select" required>
            <option value="">Seleccionar</option>
            <?php foreach ($localidades as $localidad): ?>
                <option value="<?= (int) $localidad['id_localidad']; ?>" <?= proveedor_selected($p['id_localidad'] ?? '', $localidad['id_localidad']); ?>>
                    <?= e($localidad['nombre']); ?><?= !empty($localidad['provincia_nombre']) ? ' / ' . e($localidad['provincia_nombre']) : ''; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Código postal</label>
        <input type="text" name="codigo_postal" class="form-control" maxlength="20" value="<?= e($p['codigo_postal'] ?? ''); ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Teléfono</label>
        <input type="text" name="telefono" class="form-control" maxlength="20" value="<?= e($p['telefono'] ?? ''); ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" maxlength="100" value="<?= e($p['email'] ?? ''); ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label">Sitio web</label>
        <input type="url" name="sitio_web" class="form-control" maxlength="150" value="<?= e($p['sitio_web'] ?? ''); ?>" placeholder="https://">
    </div>
    <div class="col-md-6">
        <label class="form-label">Plazo de pago</label>
        <input type="text" name="plazo_pago" class="form-control" maxlength="100" value="<?= e($p['plazo_pago'] ?? ''); ?>" placeholder="Ej: 30 días">
    </div>
    <div class="col-12">
        <label class="form-label">Datos bancarios</label>
        <textarea name="datos_bancarios" class="form-control" rows="2"><?= e($p['datos_bancarios'] ?? ''); ?></textarea>
    </div>
    <div class="col-12">
        <label class="form-label">Observaciones</label>
        <textarea name="observaciones" class="form-control" rows="2"><?= e($p['observaciones'] ?? ''); ?></textarea>
    </div>
</div>
