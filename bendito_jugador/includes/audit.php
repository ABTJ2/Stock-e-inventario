<?php
declare(strict_types=1);

if (!function_exists('audit_event')) {
    function audit_event(string $accion, string $modulo, ?int $usuarioId = null, ?string $detalle = null, ?string $entidad = null, ?int $entidadId = null): void
    {
        try {
            $database = db();

            if (!db_table_exists($database, 'auditoria_sistema')) {
                return;
            }

            $statement = $database->prepare(
                'INSERT INTO auditoria_sistema (id_usuario, accion, modulo, entidad, id_entidad, detalle, ip, user_agent)
                 VALUES (:id_usuario, :accion, :modulo, :entidad, :id_entidad, :detalle, :ip, :user_agent)'
            );
            $statement->execute([
                'id_usuario' => $usuarioId,
                'accion' => $accion,
                'modulo' => $modulo,
                'entidad' => $entidad,
                'id_entidad' => $entidadId,
                'detalle' => $detalle,
                'ip' => client_ip(),
                'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
        }
    }
}
