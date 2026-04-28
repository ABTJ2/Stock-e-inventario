# SKILL — BENDITO JUGADOR

## Propósito
Esta skill define el contexto permanente del proyecto “Bendito Jugador”, para asegurar que arquitectura, frontend y backend trabajen alineados con el mismo sistema.

## Descripción del proyecto
Bendito Jugador es un sistema web de gestión empresarial con varios módulos generales. Solo el Módulo de Stock y Control de Inventario será desarrollado completamente.

## Módulos generales del sistema
- Módulo Financiero-Contable
- Módulo de Ventas y Marketing
- Módulo de Producción
- Módulo de Compras y Logística
- Módulo de Recursos Humanos
- Módulo de Stock y Control de Inventario

## Módulo desarrollado principal
- Productos (ABM)
- Proveedores (ABM)
- Usuarios (ABM)
- Ingreso de mercadería
- Movimientos de stock
- Consulta de stock
- Reportes de auditoría
- Reportes gerenciales
- Traspaso entre almacenes
- Ajuste de inventario

## Base visual obligatoria
El sistema debe incluir:
- login profesional
- modal obligatorio de primer ingreso
- dashboard con sidebar
- todos los módulos visibles en menú lateral
- módulo de stock expandido
- otros módulos con opciones genéricas
- diseño profesional tipo ERP

## Reglas funcionales obligatorias
- acceso por usuario y contraseña
- validación de credenciales
- control de sesiones
- control por roles
- primer ingreso con cambio obligatorio de contraseña
- trazabilidad de movimientos
- no stock negativo salvo administrador

## Roles del sistema
- Administrador
- Empleado / Operario
- Supervisor Administrativo
- Supervisor Auditor
- Gerente Zonal

## Base de datos mínima
- roles
- usuarios
- productos
- proveedores
- ingresos_mercaderia
- detalle_ingreso
- movimientos_stock
- auditoria_inventario
- traspasos
- ajustes_inventario
- almacenes

## Reglas visuales
- sidebar oscuro
- contenido claro
- cards limpias
- formularios consistentes
- tablas legibles
- header superior claro
- diseño ordenado
- estilo empresarial

## Flujo obligatorio
Login → validación → primer ingreso si corresponde → dashboard → navegación por módulos

## Prioridades de trabajo
1. estructura del proyecto
2. setup.sql
3. login
4. modal primer ingreso
5. dashboard
6. sidebar
7. usuarios
8. productos
9. proveedores
10. ingresos
11. movimientos
12. consulta stock
13. reportes
14. traspasos
15. ajustes

## Uso esperado
Esta skill debe usarse para:
- validar coherencia del proyecto
- guiar el diseño visual
- guiar la lógica funcional
- evitar desvíos del objetivo académico
- mantener consistencia entre agentes