# Sistema Minimarket

Aplicación web para gestionar productos, inventario y ventas de un minimarket de barrio.

- **Equipo:** Laura Ximena Osorio, Maria Camila Melo, Sofia Soto Mejia.
- **Materia:** Ingeniería de Software II.
- **Tecnologías:** PHP 8, PDO, MariaDB/MySQL, Bootstrap 5 (framework de interfaz) y JavaScript.
- **Arquitectura:** MVC por capas (controladores, servicios, repositorios, modelos y vistas).

## Funcionalidades

| Perfil | Puede hacer |
|---|---|
| Administrador | Registrar, editar y eliminar productos (solo si no tienen ventas), marcarlos como descontinuados o reactivarlos, reponer productos agotados, consultar inventario e historial y buscar ventas. |
| Cajero | Registrar ventas, guardarlas en espera, retomarlas, consultar inventario e historial. |

## Estructura del proyecto

```
minimarket/                     ← carpeta que se publica en Apache
├── index.php                   ← Front Controller: única puerta de entrada
├── config/
│   ├── config.php              ← datos de conexión y zona horaria
│   └── rutas.php               ← tabla de rutas y permisos por rol
├── app/
│   ├── bootstrap.php           ← autoloader, configuración y sesión
│   ├── Core/                   ← Database (Singleton), Router, Contenedor, Controller base,
│   │                             Transaccion, Sesion, Vista y funciones de ayuda
│   ├── Models/                 ← Producto, Venta, ItemCarrito, Cliente, Factura, Usuario, VentaPendiente
│   ├── Repositories/           ← SQL con PDO (una clase por tabla)
│   │   └── Contracts/          ← interfaces de los repositorios
│   ├── Services/               ← reglas de negocio
│   │   └── Pagos/              ← patrones Strategy y Factory para los métodos de pago
│   ├── Validators/             ← validación de clientes y productos
│   ├── Exceptions/             ← errores del negocio
│   ├── Controllers/            ← un controlador por módulo
│   └── Views/                  ← vistas con Bootstrap (layouts, partials y una carpeta por módulo)
├── assets/js/pago.js           ← ayudas del formulario de pago
└── database/
    ├── database.sql            ← creación de la base de datos
    ├── migracion_v0_2.sql      ← migración para bases creadas antes de la v0.2
    └── migracion_v0_3.sql      ← migración que agrega el estado "descontinuado"
pruebas/                        ← pruebas automáticas
docs/                           ← diagnóstico y evidencias del taller de refactorización
ARQUITECTURA_C4.md              ← diagramas C4, patrones de diseño y principios SOLID
```

## Cómo viaja una solicitud

```
Navegador → index.php → Router (revisa el rol) → Controller → Service → Repository → MariaDB
                                                     ↓
                                                   View (Bootstrap) → Navegador
```

Ejemplo: al finalizar una venta, el formulario envía `POST index.php?ruta=ventas/finalizar`.
El `Router` comprueba que el usuario sea administrador o cajero, llama a
`VentaController::finalizar()`, que valida al cliente y le pide a
`VentaService::registrar()` guardar la venta dentro de una transacción. Al final
el controlador muestra la vista `ventas/factura.php`.

## Instalación (Ubuntu con Apache y MariaDB)

1. Instalar los paquetes: `sudo apt install apache2 php libapache2-mod-php php-mysql mariadb-server`
2. Crear la base de datos: `sudo mysql < minimarket/database/database.sql`
   (si la base ya existía, ejecutar en orden `migracion_v0_2.sql` y `migracion_v0_3.sql`)
3. Crear el usuario de la base de datos:
   ```sql
   CREATE USER 'minimarket_user'@'localhost' IDENTIFIED BY 'Minimarket123*';
   GRANT ALL PRIVILEGES ON minimarket_db.* TO 'minimarket_user'@'localhost';
   FLUSH PRIVILEGES;
   ```
4. Copiar la carpeta `minimarket` a `/var/www/html/`.
5. Abrir `http://IP-del-servidor/minimarket/` en el navegador.

Los datos de conexión se cambian en `minimarket/config/config.php`.

## Pruebas automáticas

Desde la raíz del repositorio:

```
php pruebas/ejecutar_pruebas.php
```

Las pruebas usan repositorios falsos en memoria (`pruebas/dobles.php`), así que no necesitan la base de datos.
