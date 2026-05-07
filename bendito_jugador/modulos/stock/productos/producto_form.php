<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">Codigo *</label>
            <input type="text" name="codigo" class="form-control" value="<?= e($p['codigo'] ?? ''); ?>" readonly required>
        </div>
    </div>
    <div class="col-md-8">
        <div class="form-group">
            <label class="form-label">Nombre *</label>
            <input type="text" name="nombre" class="form-control" value="<?= e($p['nombre'] ?? ''); ?>" required>
        </div>
    </div>
</div>

<div class="form-group">
    <label class="form-label">Descripcion</label>
    <textarea name="descripcion" class="form-control" rows="2"><?= e($p['descripcion'] ?? ''); ?></textarea>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">Precio de referencia</label>
            <input
                type="number"
                name="precio_referencia"
                class="form-control"
                step="0.01"
                min="0"
                value="<?= e((string) ($p['precio_referencia'] ?? 0)); ?>"
                required
            >
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">Stock actual</label>
            <input
                type="number"
                name="stock_actual"
                class="form-control"
                min="0"
                value="<?= e((string) ($p['stock_actual'] ?? 0)); ?>"
                required
            >
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">Stock minimo</label>
            <input
                type="number"
                name="stock_minimo"
                class="form-control"
                min="0"
                value="<?= e((string) ($p['stock_minimo'] ?? 0)); ?>"
                required
            >
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label class="form-label">Categoria *</label>
            <select name="id_categoria" class="form-control" required>
                <option value="">Seleccionar</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= (int) $categoria['id_categoria']; ?>" <?= selected($p['id_categoria'] ?? '', $categoria['id_categoria']); ?>>
                        <?= e($categoria['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label class="form-label">Marca *</label>
            <select name="id_marca" class="form-control" required>
                <option value="">Seleccionar</option>
                <?php foreach ($marcas as $marca): ?>
                    <option value="<?= (int) $marca['id_marca']; ?>" <?= selected($p['id_marca'] ?? '', $marca['id_marca']); ?>>
                        <?= e($marca['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">Unidad de medida *</label>
            <select name="id_unidad_medida" class="form-control" required>
                <option value="">Seleccionar</option>
                <?php foreach ($unidades as $unidad): ?>
                    <option value="<?= (int) $unidad['id_unidad_medida']; ?>" <?= selected($p['id_unidad_medida'] ?? '', $unidad['id_unidad_medida']); ?>>
                        <?= e($unidad['nombre']); ?><?= !empty($unidad['abreviatura']) ? ' / ' . e($unidad['abreviatura']) : ''; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">Estado *</label>
            <select name="id_estado_producto" class="form-control" required>
                <option value="">Seleccionar</option>
                <?php foreach ($estados as $estado): ?>
                    <option value="<?= (int) $estado['id_estado_producto']; ?>" <?= selected($p['id_estado_producto'] ?? '', $estado['id_estado_producto']); ?>>
                        <?= e($estado['nombre_estado']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">Almacen *</label>
            <select name="id_almacen" class="form-control" required>
                <option value="">Seleccionar</option>
                <?php foreach ($almacenes as $almacen): ?>
                    <option value="<?= (int) $almacen['id_almacen']; ?>" <?= selected($p['id_almacen'] ?? '', $almacen['id_almacen']); ?>>
                        <?= e($almacen['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>
