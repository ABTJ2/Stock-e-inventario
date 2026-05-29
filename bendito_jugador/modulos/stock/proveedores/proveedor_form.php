<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">CUIT</label>
            <input type="text" name="cuit" class="form-control" maxlength="20" value="<?= e($p['cuit'] ?? ''); ?>">
        </div>
    </div>
    <div class="col-md-8">
        <div class="form-group">
            <label class="form-label">Razon social *</label>
            <input type="text" name="razon_social" class="form-control" maxlength="100" value="<?= e($p['razon_social'] ?? ''); ?>" required>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label class="form-label">Nombre fantasia</label>
            <input type="text" name="nombre_fantasia" class="form-control" maxlength="150" value="<?= e($p['nombre_fantasia'] ?? ''); ?>">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label class="form-label">Estado *</label>
            <select name="id_estado_proveedor" class="form-control" required>
                <option value="">Seleccionar</option>
                <?php foreach ($estados as $estado): ?>
                    <option value="<?= (int) $estado['id_estado_proveedor']; ?>" <?= proveedor_selected($p['id_estado_proveedor'] ?? '', $estado['id_estado_proveedor']); ?>>
                        <?= e($estado['nombre_estado']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label class="form-label">Rubro</label>
            <select name="id_rubro_proveedor" class="form-control">
                <option value="">Seleccionar</option>
                <?php foreach ($rubros as $rubro): ?>
                    <option value="<?= (int) $rubro['id_rubro_proveedor']; ?>" <?= proveedor_selected($p['id_rubro_proveedor'] ?? '', $rubro['id_rubro_proveedor']); ?>>
                        <?= e($rubro['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label class="form-label">Condicion IVA</label>
            <select name="id_condicion_iva" class="form-control">
                <option value="">Seleccionar</option>
                <?php foreach ($condicionesIva as $condicion): ?>
                    <option value="<?= (int) $condicion['id_condicion_iva']; ?>" <?= proveedor_selected($p['id_condicion_iva'] ?? '', $condicion['id_condicion_iva']); ?>>
                        <?= e($condicion['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<div class="form-group">
    <label class="form-label">Direccion</label>
    <textarea name="direccion" class="form-control" rows="2"><?= e($p['direccion'] ?? ''); ?></textarea>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label class="form-label">Pais</label>
            <select name="id_pais" class="form-control">
                <option value="">Seleccionar</option>
                <?php foreach ($paises as $pais): ?>
                    <option value="<?= (int) $pais['id_pais']; ?>" <?= proveedor_selected($p['id_pais'] ?? '', $pais['id_pais']); ?>>
                        <?= e($pais['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label class="form-label">Provincia</label>
            <select name="id_provincia" class="form-control">
                <option value="">Seleccionar</option>
                <?php foreach ($provincias as $provincia): ?>
                    <option value="<?= (int) $provincia['id_provincia']; ?>" <?= proveedor_selected($p['id_provincia'] ?? '', $provincia['id_provincia']); ?>>
                        <?= e($provincia['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">Localidad</label>
            <select name="id_localidad" class="form-control">
                <option value="">Seleccionar</option>
                <?php foreach ($localidades as $localidad): ?>
                    <option value="<?= (int) $localidad['id_localidad']; ?>" <?= proveedor_selected($p['id_localidad'] ?? '', $localidad['id_localidad']); ?>>
                        <?= e($localidad['nombre']); ?><?= !empty($localidad['provincia_nombre']) ? ' / ' . e($localidad['provincia_nombre']) : ''; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <label class="form-label">Codigo postal</label>
            <input type="text" name="codigo_postal" class="form-control" maxlength="20" value="<?= e($p['codigo_postal'] ?? ''); ?>">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">Telefono</label>
            <input type="text" name="telefono" class="form-control" maxlength="20" value="<?= e($p['telefono'] ?? ''); ?>">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" maxlength="100" value="<?= e($p['email'] ?? ''); ?>">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">Sitio web</label>
            <input type="url" name="sitio_web" class="form-control" maxlength="150" value="<?= e($p['sitio_web'] ?? ''); ?>" placeholder="https://">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">Plazo de pago</label>
            <input type="text" name="plazo_pago" class="form-control" maxlength="100" value="<?= e($p['plazo_pago'] ?? ''); ?>" placeholder="Ej: 30 dias">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">CBU</label>
            <input type="text" name="cbu" class="form-control" maxlength="30" value="<?= e($p['cbu'] ?? ''); ?>">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">Alias</label>
            <input type="text" name="alias" class="form-control" maxlength="80" value="<?= e($p['alias'] ?? ''); ?>">
        </div>
    </div>
</div>

<div class="form-group">
    <label class="form-label">Datos bancarios</label>
    <textarea name="datos_bancarios" class="form-control" rows="2"><?= e($p['datos_bancarios'] ?? ''); ?></textarea>
</div>

<div class="form-group">
    <label class="form-label">Observaciones</label>
    <textarea name="observaciones" class="form-control" rows="2"><?= e($p['observaciones'] ?? ''); ?></textarea>
</div>
