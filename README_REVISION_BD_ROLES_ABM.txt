REVISION REALIZADA - BASE DE DATOS, ROLES, PRODUCTOS ABM Y PROVEEDORES ABM

Proyecto: Sistema de Stock e Inventario - Bendito Jugador
Tecnologias mantenidas: PHP, MySQL, HTML, CSS, Bootstrap y JavaScript simple.

1) Base de datos
- Se corrigio el modelo para que productos.codigo sea solo numerico.
- Se cambiaron los productos iniciales de PROD001/PROD002/etc. a 1/2/3/etc.
- Se agregaron restricciones simples de integridad:
  * codigo de producto numerico.
  * precios y stocks no negativos.
  * CUIT de proveedor con formato valido.
  * cantidad de movimientos y traspasos mayor a cero.
  * traspasos entre almacenes distintos.
- Se agregaron indices simples para busquedas por productos y proveedores.
- Se agrego una migracion opcional:
  bendito_jugador/database/migrations/2026_06_11_revision_bd_roles_abm.sql

2) Roles y permisos
- Se agrego includes/permissions.php.
- Los permisos se manejan con un arreglo multidimensional facil de explicar.
- Se usan funciones y bucles para filtrar el menu segun rol.
- Se agrego control real de acceso con require_module_access().
- La pantalla Roles ahora muestra:
  * roles del sistema,
  * usuarios asignados,
  * modulos habilitados,
  * matriz de permisos por rol.

3) Productos ABM
- El codigo se genera automaticamente como el numero siguiente al mayor codigo existente.
- El codigo queda bloqueado en nuevo producto y editar producto.
- Aunque alguien modifique el HTML, al crear se ignora el codigo enviado y se vuelve a generar desde PHP.
- Se valida que el codigo sea numerico.
- Se mantiene el ABM con listar, buscar, crear, editar, ver detalle y eliminar.
- Al editar stock desde ABM se registra movimiento de ajuste positivo o negativo para no perder trazabilidad.

4) Proveedores ABM
- CUIT ahora es obligatorio y se valida con 11 numeros.
- Razon social sigue siendo obligatoria.
- Rubro, condicion IVA, pais, provincia, localidad y estado ahora son obligatorios.
- Se agrego el campo Contacto en el formulario y en el detalle.
- Se mantiene baja logica de proveedor: pasa a estado inactivo sin borrar historial.

5) Archivos principales modificados
- bendito_jugador/includes/bootstrap.php
- bendito_jugador/includes/permissions.php
- bendito_jugador/includes/sidebar.php
- bendito_jugador/includes/navigation.php
- bendito_jugador/modulos/stock/admin/roles.php
- bendito_jugador/modulos/stock/productos/index.php
- bendito_jugador/modulos/stock/productos/producto_form.php
- bendito_jugador/modulos/stock/productos/productos_logic.php
- bendito_jugador/modulos/stock/proveedores/index.php
- bendito_jugador/modulos/stock/proveedores/proveedor_form.php
- bendito_jugador/modulos/stock/proveedores/proveedores_logic.php
- bendito_jugador/modulos/stock/usuarios/index.php
- bendito_jugador/database/schema.sql
- bendito_jugador/database/seeds.sql
- bendito_jugador/setup.sql

6) Como probar rapido
- Importar setup.sql en MySQL para una base nueva.
- Entrar a /bendito_jugador/index.php.
- Usuario inicial: admin
- Clave inicial: admin123
- Probar Productos:
  * Nuevo producto debe mostrar codigo numerico automatico.
  * Editar producto no debe permitir cambiar codigo.
  * Si cambia stock, debe quedar registrado en movimientos_stock.
- Probar Proveedores:
  * No debe dejar guardar sin CUIT, razon social, rubro, condicion IVA, pais, provincia, localidad y estado.
  * El detalle debe mostrar contacto.
- Probar Roles:
  * Entrar a Roles y permisos con admin.
  * Verificar la matriz de modulos por rol.

7) Nota para defensa
La parte de roles usa arreglos multidimensionales, funciones y bucles. Esto cumple con una solucion simple y explicable en clase, sin agregar frameworks ni arquitectura complicada.
