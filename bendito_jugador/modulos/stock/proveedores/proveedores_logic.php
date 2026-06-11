<?php
declare(strict_types=1);

function proveedor_estado_sql(array $estado): string
{
    return strcasecmp((string) ($estado['nombre_estado'] ?? ''), 'Activo') === 0 ? 'activo' : 'inactivo';
}

function proveedor_estado_por_id(PDO $db, int $idEstado): ?array
{
    $stmt = $db->prepare('SELECT * FROM estados_proveedor WHERE id_estado_proveedor = ?');
    $stmt->execute([$idEstado]);
    $estado = $stmt->fetch();

    return $estado ?: null;
}

function proveedor_estado_nombre_id(PDO $db, string $nombre): ?int
{
    $stmt = $db->prepare('SELECT id_estado_proveedor FROM estados_proveedor WHERE LOWER(nombre_estado) = LOWER(?) LIMIT 1');
    $stmt->execute([$nombre]);
    $id = $stmt->fetchColumn();

    return $id !== false ? (int) $id : null;
}

function proveedor_catalogos(PDO $db): array
{
    $stmtRubros = $db->prepare('SELECT * FROM rubros_proveedor WHERE estado = 1 ORDER BY nombre');
    $stmtRubros->execute();

    $stmtCondiciones = $db->prepare('SELECT * FROM condiciones_iva WHERE estado = 1 ORDER BY nombre');
    $stmtCondiciones->execute();

    $stmtPaises = $db->prepare('SELECT * FROM paises WHERE estado = 1 ORDER BY nombre');
    $stmtPaises->execute();

    $stmtProvincias = $db->prepare(
        'SELECT pr.*, pa.nombre AS pais_nombre
         FROM provincias pr
         INNER JOIN paises pa ON pa.id_pais = pr.id_pais
         WHERE pr.estado = 1
         ORDER BY pa.nombre, pr.nombre'
    );
    $stmtProvincias->execute();

    $stmtLocalidades = $db->prepare(
        'SELECT l.*, pr.nombre AS provincia_nombre
         FROM localidades l
         INNER JOIN provincias pr ON pr.id_provincia = l.id_provincia
         WHERE l.estado = 1
         ORDER BY pr.nombre, l.nombre'
    );
    $stmtLocalidades->execute();

    $stmtEstados = $db->prepare('SELECT * FROM estados_proveedor ORDER BY id_estado_proveedor');
    $stmtEstados->execute();

    return [
        'rubros' => $stmtRubros->fetchAll(),
        'condiciones_iva' => $stmtCondiciones->fetchAll(),
        'paises' => $stmtPaises->fetchAll(),
        'provincias' => $stmtProvincias->fetchAll(),
        'localidades' => $stmtLocalidades->fetchAll(),
        'estados' => $stmtEstados->fetchAll(),
    ];
}

function proveedor_post_data(array $source): array
{
    return [
        'cuit' => trim((string) ($source['cuit'] ?? '')),
        'razon_social' => trim((string) ($source['razon_social'] ?? '')),
        'nombre_fantasia' => trim((string) ($source['nombre_fantasia'] ?? '')),
        'direccion' => trim((string) ($source['direccion'] ?? '')),
        'id_pais' => (int) ($source['id_pais'] ?? 0),
        'id_provincia' => (int) ($source['id_provincia'] ?? 0),
        'id_localidad' => (int) ($source['id_localidad'] ?? 0),
        'codigo_postal' => trim((string) ($source['codigo_postal'] ?? '')),
        'telefono' => trim((string) ($source['telefono'] ?? '')),
        'email' => trim((string) ($source['email'] ?? '')),
        'contacto' => trim((string) ($source['contacto'] ?? '')),
        'sitio_web' => trim((string) ($source['sitio_web'] ?? '')),
        'id_condicion_iva' => (int) ($source['id_condicion_iva'] ?? 0),
        'id_rubro_proveedor' => (int) ($source['id_rubro_proveedor'] ?? 0),
        'plazo_pago' => trim((string) ($source['plazo_pago'] ?? '')),
        'cbu' => trim((string) ($source['cbu'] ?? '')),
        'alias' => trim((string) ($source['alias'] ?? '')),
        'datos_bancarios' => trim((string) ($source['datos_bancarios'] ?? '')),
        'id_estado_proveedor' => (int) ($source['id_estado_proveedor'] ?? 0),
        'observaciones' => trim((string) ($source['observaciones'] ?? '')),
    ];
}

function proveedor_null_if_empty(string $value): ?string
{
    return $value === '' ? null : $value;
}

function proveedor_opcion_existe(PDO $db, string $table, string $idColumn, int $id, bool $activeOnly = true): bool
{
    $allowed = [
        'rubros_proveedor' => 'id_rubro_proveedor',
        'condiciones_iva' => 'id_condicion_iva',
        'paises' => 'id_pais',
        'provincias' => 'id_provincia',
        'localidades' => 'id_localidad',
        'estados_proveedor' => 'id_estado_proveedor',
    ];

    if (($allowed[$table] ?? '') !== $idColumn) {
        return false;
    }

    $sql = "SELECT COUNT(*) FROM {$table} WHERE {$idColumn} = ?";
    if ($activeOnly && $table !== 'estados_proveedor') {
        $sql .= ' AND estado = 1';
    }

    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);

    return (int) $stmt->fetchColumn() > 0;
}

function proveedor_validar(PDO $db, array $data, int $idProveedor = 0): array
{
    $errors = [];

    if ($data['cuit'] === '') {
        $errors[] = 'El CUIT es obligatorio.';
    } else {
        $cuitLimpio = preg_replace('/\D/', '', $data['cuit']);
        if (strlen((string) $cuitLimpio) !== 11) {
            $errors[] = 'El CUIT debe tener 11 numeros. Ejemplo: 20-12345678-5.';
        } else {
            $stmt = $db->prepare('SELECT COUNT(*) FROM proveedores WHERE cuit = ? AND id_proveedor <> ?');
            $stmt->execute([$data['cuit'], $idProveedor]);
            if ((int) $stmt->fetchColumn() > 0) {
                $errors[] = 'Ya existe un proveedor con ese CUIT.';
            }
        }
    }

    if ($data['razon_social'] === '') {
        $errors[] = 'La razon social es obligatoria.';
    }

    if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email ingresado no es valido.';
    }

    $requiredChecks = [
        ['id_rubro_proveedor', 'rubros_proveedor', 'id_rubro_proveedor', 'rubro'],
        ['id_condicion_iva', 'condiciones_iva', 'id_condicion_iva', 'condicion IVA'],
        ['id_pais', 'paises', 'id_pais', 'pais'],
        ['id_provincia', 'provincias', 'id_provincia', 'provincia'],
        ['id_localidad', 'localidades', 'id_localidad', 'localidad'],
        ['id_estado_proveedor', 'estados_proveedor', 'id_estado_proveedor', 'estado'],
    ];

    foreach ($requiredChecks as [$field, $table, $idColumn, $label]) {
        if ((int) $data[$field] <= 0 || !proveedor_opcion_existe($db, $table, $idColumn, (int) $data[$field], $table !== 'estados_proveedor')) {
            $errors[] = 'Debe seleccionar un valor valido para ' . $label . '.';
        }
    }

    if ($data['id_pais'] > 0 && $data['id_provincia'] > 0) {
        $stmt = $db->prepare('SELECT COUNT(*) FROM provincias WHERE id_provincia = ? AND id_pais = ? AND estado = 1');
        $stmt->execute([$data['id_provincia'], $data['id_pais']]);
        if ((int) $stmt->fetchColumn() === 0) {
            $errors[] = 'La provincia no corresponde al pais seleccionado.';
        }
    }

    if ($data['id_provincia'] > 0 && $data['id_localidad'] > 0) {
        $stmt = $db->prepare('SELECT COUNT(*) FROM localidades WHERE id_localidad = ? AND id_provincia = ? AND estado = 1');
        $stmt->execute([$data['id_localidad'], $data['id_provincia']]);
        if ((int) $stmt->fetchColumn() === 0) {
            $errors[] = 'La localidad no corresponde a la provincia seleccionada.';
        }
    }

    return $errors;
}

function proveedor_guardar_parametros(array $data, array $estado): array
{
    return [
        proveedor_null_if_empty($data['cuit']),
        $data['razon_social'],
        proveedor_null_if_empty($data['nombre_fantasia']),
        proveedor_null_if_empty($data['direccion']),
        $data['id_pais'] > 0 ? $data['id_pais'] : null,
        $data['id_provincia'] > 0 ? $data['id_provincia'] : null,
        $data['id_localidad'] > 0 ? $data['id_localidad'] : null,
        proveedor_null_if_empty($data['codigo_postal']),
        proveedor_null_if_empty($data['telefono']),
        proveedor_null_if_empty($data['email']),
        proveedor_null_if_empty($data['contacto']),
        proveedor_null_if_empty($data['sitio_web']),
        $data['id_condicion_iva'] > 0 ? $data['id_condicion_iva'] : null,
        $data['id_rubro_proveedor'] > 0 ? $data['id_rubro_proveedor'] : null,
        proveedor_null_if_empty($data['plazo_pago']),
        proveedor_null_if_empty($data['cbu']),
        proveedor_null_if_empty($data['alias']),
        proveedor_null_if_empty($data['datos_bancarios']),
        (int) $data['id_estado_proveedor'],
        proveedor_estado_sql($estado),
        proveedor_null_if_empty($data['observaciones']),
    ];
}

function proveedor_crear(PDO $db, array $data): int
{
    $estado = proveedor_estado_por_id($db, (int) $data['id_estado_proveedor']);
    if ($estado === null) {
        throw new RuntimeException('Estado invalido.');
    }

    $db->beginTransaction();

    try {
        $stmt = $db->prepare(
            'INSERT INTO proveedores (
                cuit, razon_social, nombre_fantasia, direccion, id_pais, id_provincia, id_localidad,
                codigo_postal, telefono, email, contacto, sitio_web, id_condicion_iva, id_rubro_proveedor,
                plazo_pago, cbu, alias, datos_bancarios, id_estado_proveedor, estado, observaciones
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute(proveedor_guardar_parametros($data, $estado));

        $idProveedor = (int) $db->lastInsertId();
        audit_event('alta', 'proveedores', (int) (current_user()['id'] ?? 0), 'Alta de proveedor ' . $data['razon_social']);
        $db->commit();

        return $idProveedor;
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        throw $exception;
    }
}

function proveedor_editar(PDO $db, int $idProveedor, array $data): void
{
    $estado = proveedor_estado_por_id($db, (int) $data['id_estado_proveedor']);
    if ($estado === null) {
        throw new RuntimeException('Estado invalido.');
    }

    $stmt = $db->prepare('SELECT id_proveedor FROM proveedores WHERE id_proveedor = ?');
    $stmt->execute([$idProveedor]);
    if (!$stmt->fetch()) {
        throw new RuntimeException('Proveedor inexistente.');
    }

    $db->beginTransaction();

    try {
        $stmt = $db->prepare(
            'UPDATE proveedores
             SET cuit = ?, razon_social = ?, nombre_fantasia = ?, direccion = ?, id_pais = ?,
                 id_provincia = ?, id_localidad = ?, codigo_postal = ?, telefono = ?, email = ?,
                 contacto = ?, sitio_web = ?, id_condicion_iva = ?, id_rubro_proveedor = ?, plazo_pago = ?,
                 cbu = ?, alias = ?, datos_bancarios = ?, id_estado_proveedor = ?, estado = ?,
                 observaciones = ?, fecha_actualizacion = NOW()
             WHERE id_proveedor = ?'
        );
        $params = proveedor_guardar_parametros($data, $estado);
        $params[] = $idProveedor;
        $stmt->execute($params);

        audit_event('edicion', 'proveedores', (int) (current_user()['id'] ?? 0), 'Edicion de proveedor ' . $data['razon_social']);
        $db->commit();
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        throw $exception;
    }
}

function proveedor_desactivar(PDO $db, int $idProveedor): void
{
    $idEstadoInactivo = proveedor_estado_nombre_id($db, 'Inactivo');
    if ($idEstadoInactivo === null) {
        throw new RuntimeException('No existe el estado Inactivo en estados_proveedor.');
    }

    $stmt = $db->prepare('SELECT razon_social, cuit FROM proveedores WHERE id_proveedor = ?');
    $stmt->execute([$idProveedor]);
    $proveedor = $stmt->fetch();
    if (!$proveedor) {
        throw new RuntimeException('Proveedor inexistente.');
    }

    $stmt = $db->prepare(
        "UPDATE proveedores
         SET estado = 'inactivo', id_estado_proveedor = ?, fecha_actualizacion = NOW()
         WHERE id_proveedor = ?"
    );
    $stmt->execute([$idEstadoInactivo, $idProveedor]);

    audit_event(
        'desactivacion',
        'proveedores',
        (int) (current_user()['id'] ?? 0),
        'Desactivacion de proveedor ' . ($proveedor['cuit'] ? $proveedor['cuit'] . ' - ' : '') . $proveedor['razon_social']
    );
}

function proveedor_procesar_post(PDO $db): array
{
    if (!request_method_is('POST')) {
        return ['type' => '', 'message' => ''];
    }

    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'crear' || $action === 'editar') {
            $idProveedor = (int) ($_POST['id_proveedor'] ?? 0);
            $data = proveedor_post_data($_POST);
            $errors = proveedor_validar($db, $data, $action === 'editar' ? $idProveedor : 0);

            if ($action === 'editar' && $idProveedor <= 0) {
                $errors[] = 'Proveedor invalido.';
            }

            if ($errors) {
                return ['type' => 'danger', 'message' => implode(' ', $errors)];
            }

            if ($action === 'crear') {
                proveedor_crear($db, $data);
                return ['type' => 'success', 'message' => 'Proveedor creado correctamente.'];
            }

            proveedor_editar($db, $idProveedor, $data);
            return ['type' => 'success', 'message' => 'Proveedor actualizado correctamente.'];
        }

        if ($action === 'desactivar') {
            $idProveedor = (int) ($_POST['id_proveedor'] ?? 0);
            if ($idProveedor <= 0) {
                return ['type' => 'danger', 'message' => 'Proveedor invalido.'];
            }

            proveedor_desactivar($db, $idProveedor);
            return ['type' => 'success', 'message' => 'Proveedor desactivado correctamente.'];
        }
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            return ['type' => 'danger', 'message' => 'No se pudo guardar: CUIT duplicado o relacion invalida.'];
        }

        return ['type' => 'danger', 'message' => 'No se pudo completar la operacion en la base de datos.'];
    } catch (Throwable $exception) {
        return ['type' => 'danger', 'message' => $exception->getMessage() ?: 'No se pudo completar la operacion.'];
    }

    return ['type' => '', 'message' => ''];
}

function proveedor_filtros(array $source): array
{
    return [
        'q' => trim((string) ($source['q'] ?? '')),
        'rubro' => (int) ($source['rubro'] ?? 0),
        'condicion_iva' => (int) ($source['condicion_iva'] ?? 0),
        'localidad' => (int) ($source['localidad'] ?? 0),
        'estado' => (int) ($source['estado'] ?? 0),
    ];
}

function proveedores_listar(PDO $db, array $filters): array
{
    $where = [];
    $params = [];

    if ($filters['q'] !== '') {
        $where[] = '(p.razon_social LIKE ? OR p.cuit LIKE ? OR p.nombre_fantasia LIKE ? OR p.email LIKE ?)';
        for ($i = 0; $i < 4; $i++) {
            $params[] = '%' . $filters['q'] . '%';
        }
    }

    if ($filters['rubro'] > 0) {
        $where[] = 'p.id_rubro_proveedor = ?';
        $params[] = $filters['rubro'];
    }

    if ($filters['condicion_iva'] > 0) {
        $where[] = 'p.id_condicion_iva = ?';
        $params[] = $filters['condicion_iva'];
    }

    if ($filters['localidad'] > 0) {
        $where[] = 'p.id_localidad = ?';
        $params[] = $filters['localidad'];
    }

    if ($filters['estado'] > 0) {
        $where[] = 'p.id_estado_proveedor = ?';
        $params[] = $filters['estado'];
    }

    $sql = 'SELECT
                p.*,
                ep.nombre_estado,
                rp.nombre AS rubro_nombre,
                ci.nombre AS condicion_iva_nombre,
                pa.nombre AS pais_nombre,
                pr.nombre AS provincia_nombre,
                l.nombre AS localidad_nombre
            FROM proveedores p
            LEFT JOIN estados_proveedor ep ON ep.id_estado_proveedor = p.id_estado_proveedor
            LEFT JOIN rubros_proveedor rp ON rp.id_rubro_proveedor = p.id_rubro_proveedor
            LEFT JOIN condiciones_iva ci ON ci.id_condicion_iva = p.id_condicion_iva
            LEFT JOIN paises pa ON pa.id_pais = p.id_pais
            LEFT JOIN provincias pr ON pr.id_provincia = p.id_provincia
            LEFT JOIN localidades l ON l.id_localidad = p.id_localidad';

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY p.razon_social ASC';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function proveedor_selected($current, $expected): string
{
    return (string) $current === (string) $expected ? 'selected' : '';
}
