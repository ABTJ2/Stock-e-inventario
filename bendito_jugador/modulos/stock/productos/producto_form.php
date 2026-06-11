<?php $codigoReadonly = !empty($codigoReadonly); ?>
<div class="productos-form-grid">
    <div class="row g-3">
        <div class="col-lg-4 col-md-5">
            <div class="form-group productos-form-group">
                <label class="form-label">Codigo *</label>
                <input
                    type="text"
                    name="codigo"
                    class="form-control <?= $codigoReadonly ? 'productos-code-readonly' : ''; ?>"
                    maxlength="50"
                    inputmode="numeric"
                    pattern="[0-9]+"
                    value="<?= e($p['codigo'] ?? ''); ?>"
                    <?= $codigoReadonly ? 'readonly aria-readonly="true"' : ''; ?>
                    required
                >
                <small class="form-text text-muted">El código es numérico y se genera automáticamente.</small>
            </div>
        </div>
        <div class="col-lg-8 col-md-7">
            <div class="form-group productos-form-group">
                <label class="form-label">Nombre *</label>
                <input type="text" name="nombre" class="form-control" maxlength="150" value="<?= e($p['nombre'] ?? ''); ?>" required>
            </div>
        </div>

        <div class="col-12">
            <div class="form-group productos-form-group">
                <label class="form-label">Descripcion</label>
                <textarea name="descripcion" class="form-control" rows="2"><?= e($p['descripcion'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="col-lg-4 col-md-4">
            <div class="form-group productos-form-group">
                <label class="form-label">Precio *</label>
                <input
                    type="number"
                    name="precio_referencia"
                    class="form-control"
                    step="0.01"
                    min="0"
                    inputmode="decimal"
                    data-select-zero
                    value="<?= e((string) ($p['precio_referencia'] ?? 0)); ?>"
                    required
                >
            </div>
        </div>
        <div class="col-lg-4 col-md-4">
            <div class="form-group productos-form-group">
                <label class="form-label"><?= e($stockLabel ?? 'Stock actual'); ?> *</label>
                <input
                    type="number"
                    name="stock_actual"
                    class="form-control"
                    min="0"
                    step="1"
                    inputmode="numeric"
                    data-select-zero
                    value="<?= e((string) ($p['stock_actual'] ?? 0)); ?>"
                    required
                >
            </div>
        </div>
        <div class="col-lg-4 col-md-4">
            <div class="form-group productos-form-group">
                <label class="form-label">Stock minimo *</label>
                <input
                    type="number"
                    name="stock_minimo"
                    class="form-control"
                    min="0"
                    step="1"
                    inputmode="numeric"
                    data-select-zero
                    value="<?= e((string) ($p['stock_minimo'] ?? 0)); ?>"
                    required
                >
            </div>
        </div>

        <div class="col-lg-6 col-md-6">
            <div class="form-group productos-form-group">
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
        <div class="col-lg-6 col-md-6">
            <div class="form-group productos-form-group">
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

        <div class="col-lg-4 col-md-4">
            <div class="form-group productos-form-group">
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
        <div class="col-lg-4 col-md-4">
            <div class="form-group productos-form-group">
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
        <div class="col-lg-4 col-md-4">
            <div class="form-group productos-form-group">
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
</div>
